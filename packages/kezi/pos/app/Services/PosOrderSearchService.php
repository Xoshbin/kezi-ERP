<?php

namespace Kezi\Pos\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Kezi\Pos\DataTransferObjects\SearchPosOrdersDTO;
use Kezi\Pos\Enums\PosOrderStatus;
use Kezi\Pos\Models\PosOrder;

class PosOrderSearchService
{
    /**
     * Search POS orders with multiple criteria
     */
    public function search(SearchPosOrdersDTO $dto): LengthAwarePaginator
    {
        $query = PosOrder::query()
            ->with(['customer', 'lines.product', 'session', 'currency'])
            ->where('company_id', $dto->company_id);

        // Order number search (exact or partial)
        if ($dto->order_number) {
            if ($dto->exact_match) {
                $query->where('order_number', $dto->order_number);
            } else {
                $query->where('order_number', 'like', "%{$dto->order_number}%");
            }
        }

        // Date range filter
        if ($dto->date_from) {
            $query->where('ordered_at', '>=', $dto->date_from);
        }
        if ($dto->date_to) {
            $query->where('ordered_at', '<=', $dto->date_to);
        }

        // Customer filter
        if ($dto->customer_id) {
            $query->where('customer_id', $dto->customer_id);
        }

        // Customer name search (via relationship)
        if ($dto->customer_name) {
            $query->whereHas('customer', function (Builder $q) use ($dto) {
                $q->where('name', 'like', "%{$dto->customer_name}%");
            });
        }

        // Amount range filter
        if ($dto->amount_min) {
            $query->where('total_amount', '>=', $dto->amount_min);
        }
        if ($dto->amount_max) {
            $query->where('total_amount', '<=', $dto->amount_max);
        }

        // Payment method filter
        if ($dto->payment_method) {
            $query->where('payment_method', $dto->payment_method);
        }

        // Status filter (exclude cancelled orders by default)
        if ($dto->status) {
            $query->where('status', $dto->status);
        } else {
            $query->where('status', '!=', PosOrderStatus::Cancelled);
        }

        // Product search (via order lines)
        if ($dto->product_id) {
            $query->whereHas('lines', function (Builder $q) use ($dto) {
                $q->where('product_id', $dto->product_id);
            });
        }

        // Product name or SKU search
        if ($dto->product_search) {
            $query->whereHas('lines.product', function (Builder $q) use ($dto) {
                $q->where('name', 'like', "%{$dto->product_search}%")
                    ->orWhere('sku', 'like', "%{$dto->product_search}%");
            });
        }

        // Session filter (useful for current session returns)
        if ($dto->session_id) {
            $query->where('pos_session_id', $dto->session_id);
        }

        // Sort by most recent by default
        $query->orderBy('ordered_at', 'desc');

        // Paginate results
        return $query->paginate($dto->per_page ?? 20);
    }

    /**
     * Quick search for POS terminal (simplified)
     */
    public function quickSearch(
        int $companyId,
        string $searchTerm,
        ?int $sessionId = null,
        int $limit = 10
    ): \Illuminate\Support\Collection {
        $query = PosOrder::query()
            ->with(['customer', 'lines.product'])
            ->where('company_id', $companyId)
            ->where('status', '!=', PosOrderStatus::Cancelled);

        if ($sessionId) {
            $query->where('pos_session_id', $sessionId);
        }

        // Search in order number or customer name
        $query->where(function (Builder $q) use ($searchTerm) {
            $q->where('order_number', 'like', "%{$searchTerm}%")
                ->orWhereHas('customer', function (Builder $q2) use ($searchTerm) {
                    $q2->where('name', 'like', "%{$searchTerm}%");
                });
        });

        return $query->orderBy('ordered_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if order is eligible for return
     */
    public function isEligibleForReturn(PosOrder $order, array $returnPolicy): array
    {
        $eligible = true;
        $reasons = [];

        // Check time limit
        if (isset($returnPolicy['time_limit_days'])) {
            $daysSincePurchase = $order->ordered_at->diffInDays(now());
            if ($daysSincePurchase > $returnPolicy['time_limit_days']) {
                $eligible = false;
                $reasons[] = "Return period expired ({$returnPolicy['time_limit_days']} days)";
            }
        }

        // Check if order is already returned
        if ($order->returns()->where('status', '!=', 'cancelled')->exists()) {
            $eligible = false;
            $reasons[] = 'Order already has an active return';
        }

        // Check order status
        if ($order->status !== PosOrderStatus::Paid) {
            $eligible = false;
            $reasons[] = 'Only paid orders can be returned';
        }

        return [
            'eligible' => $eligible,
            'reasons' => $reasons,
        ];
    }
}
