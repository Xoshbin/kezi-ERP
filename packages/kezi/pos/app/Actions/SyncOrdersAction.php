<?php

namespace Kezi\Pos\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kezi\Pos\DataTransferObjects\PosOrderData;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosOrderLine;
use Kezi\Pos\Models\PosOrderPayment;
use Kezi\Pos\Models\PosSession;

class SyncOrdersAction
{
    public function __construct(
        protected CreateInvoiceFromPosOrderAction $createInvoiceAction,
    ) {}

    /**
     * @param  Collection<int, PosOrderData>  $ordersData
     */
    public function execute(Collection $ordersData, User $user, int $companyId): array
    {
        \Kezi\Inventory\Services\Inventory\StockQuantService::$allowNegativeStock = true;

        try {
            $synced = [];
            $failed = [];
            $currencies = [];

            foreach ($ordersData as $orderData) {
                try {
                    // Verify the session belongs to the authenticated user
                    $session = PosSession::where('id', $orderData->pos_session_id)
                        ->where('user_id', $user->id)
                        ->first();

                    if (! $session) {
                        throw new \Exception('Invalid or unauthorized session.');
                    }

                    // Idempotency check: If order with this UUID exists for this company, skip processing
                    if (PosOrder::where('uuid', $orderData->uuid)->where('company_id', $companyId)->exists()) {
                        $synced[] = $orderData->uuid;

                        continue;
                    }

                    if (! isset($currencies[$orderData->currency_id])) {
                        $currency = \Kezi\Foundation\Models\Currency::find($orderData->currency_id);
                        if (! $currency) {
                            throw new \Exception("Currency ID {$orderData->currency_id} not found.");
                        }
                        $currencies[$orderData->currency_id] = $currency->code;
                    }
                    $currencyCode = $currencies[$orderData->currency_id];

                    $order = DB::transaction(function () use ($orderData, $companyId, $currencyCode) {
                        $order = PosOrder::create([
                            'uuid' => $orderData->uuid,
                            'company_id' => $companyId,
                            'order_number' => $orderData->order_number,
                            'status' => $orderData->status,
                            'payment_method' => $orderData->payment_method,
                            'ordered_at' => $orderData->ordered_at,
                            'total_amount' => \Brick\Money\Money::ofMinor($orderData->total_amount, $currencyCode),
                            'total_tax' => \Brick\Money\Money::ofMinor($orderData->total_tax, $currencyCode),
                            'discount_amount' => \Brick\Money\Money::ofMinor($orderData->discount_amount, $currencyCode),
                            'notes' => $orderData->notes,
                            'customer_id' => $orderData->customer_id,
                            'currency_id' => $orderData->currency_id,
                            'sector_data' => $orderData->sector_data,
                            'pos_session_id' => $orderData->pos_session_id,
                            // 'user_id' => $user->id,
                            'invoice_id' => null, // Will be set after invoice creation
                        ]);

                        foreach ($orderData->lines as $lineData) {
                            PosOrderLine::create([
                                'pos_order_id' => $order->id,
                                'product_id' => $lineData->product_id,
                                'quantity' => $lineData->quantity,
                                'unit_price' => \Brick\Money\Money::ofMinor($lineData->unit_price, $currencyCode),
                                'discount_amount' => \Brick\Money\Money::ofMinor($lineData->discount_amount, $currencyCode),
                                'tax_amount' => \Brick\Money\Money::ofMinor($lineData->tax_amount, $currencyCode),
                                'total_amount' => \Brick\Money\Money::ofMinor($lineData->total_amount, $currencyCode),
                                'metadata' => $lineData->metadata,
                            ]);
                        }

                        // Persist split payment records
                        if ($orderData->payments->isNotEmpty()) {
                            foreach ($orderData->payments as $paymentData) {
                                PosOrderPayment::create([
                                    'pos_order_id' => $order->id,
                                    'payment_method' => $paymentData->method,
                                    'amount' => $paymentData->amount,
                                    'amount_tendered' => $paymentData->amount_tendered,
                                    'change_given' => $paymentData->change_given,
                                ]);
                            }
                        } else {
                            // Backward-compatibility: synthesise a single payment row from legacy field
                            PosOrderPayment::create([
                                'pos_order_id' => $order->id,
                                'payment_method' => $orderData->payment_method,
                                'amount' => (int) $orderData->total_amount,
                                'amount_tendered' => (int) $orderData->total_amount,
                                'change_given' => 0,
                            ]);
                        }

                        // Create invoice if not already present
                        try {
                            if (! $order->invoice_id) {
                                $this->createInvoiceAction->execute($order);
                            }
                        } catch (\Exception $e) {
                            $strictMode = $order->session?->profile?->settings['strict_stock_check'] ?? false;
                            if ($strictMode) {
                                throw $e;
                            }
                            Log::warning("Invoice creation failed for POS order {$orderData->uuid}: {$e->getMessage()}");
                        }

                        return $order;
                    });

                    \Kezi\Pos\Events\PosOrderSynced::dispatch($order);

                    $synced[] = $orderData->uuid;
                } catch (\Exception $e) {
                    $failed[] = [
                        'uuid' => $orderData->uuid,
                        'error' => $e->getMessage(),
                    ];
                }
            }

            return [
                'synced' => $synced,
                'failed' => $failed,
            ];
        } finally {
            \Kezi\Inventory\Services\Inventory\StockQuantService::$allowNegativeStock = false;
        }
    }
}
