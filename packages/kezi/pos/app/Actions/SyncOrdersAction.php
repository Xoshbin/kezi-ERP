<?php

namespace Kezi\Pos\Actions;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Kezi\Pos\DataTransferObjects\PosOrderData;
use Kezi\Pos\Models\PosOrder;
use Kezi\Pos\Models\PosOrderLine;

class SyncOrdersAction
{
    /**
     * @param  Collection<int, PosOrderData>  $ordersData
     */
    public function execute(Collection $ordersData, User $user, int $companyId): array
    {
        $synced = [];
        $failed = [];
        $currencies = [];

        foreach ($ordersData as $orderData) {
            try {
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

                DB::transaction(function () use ($orderData, $companyId, $currencyCode) {
                    $order = PosOrder::create([
                        'uuid' => $orderData->uuid,
                        'company_id' => $companyId,
                        'order_number' => $orderData->order_number,
                        'status' => $orderData->status,
                        'ordered_at' => $orderData->ordered_at,
                        'total_amount' => \Brick\Money\Money::ofMinor($orderData->total_amount, $currencyCode),
                        'total_tax' => \Brick\Money\Money::ofMinor($orderData->total_tax, $currencyCode),
                        'discount_amount' => \Brick\Money\Money::ofMinor($orderData->discount_amount, $currencyCode),
                        'notes' => $orderData->notes,
                        'customer_id' => $orderData->customer_id,
                        'currency_id' => $orderData->currency_id,
                        'sector_data' => $orderData->sector_data,
                        'pos_session_id' => $orderData->pos_session_id,
                        // 'user_id' => $user->id, // PosOrder doesn't seem to have user_id in fillable/migration yet strictly? Model didn't show it in Step 201.
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
                });

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
    }
}
