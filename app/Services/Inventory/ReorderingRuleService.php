<?php

namespace App\Services\Inventory;

use App\Enums\Inventory\ReorderingRoute;
use App\Models\ReorderingRule;
use App\Models\ReplenishmentSuggestion;
use App\Models\StockQuant;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReorderingRuleService
{
    public function __construct(
        private readonly StockQuantService $stockQuantService
    ) {}

    /**
     * Generate replenishment suggestions for all active reordering rules
     */
    public function generateReplenishmentSuggestions(): int
    {
        $suggestionsCreated = 0;
        $currentDate = Carbon::now();

        $activeRules = ReorderingRule::active()
            ->with(['product', 'location', 'company'])
            ->get();

        foreach ($activeRules as $rule) {
            try {
                if ($this->shouldCreateSuggestion($rule)) {
                    $this->createReplenishmentSuggestion($rule, $currentDate);
                    $suggestionsCreated++;
                }
            } catch (\Exception $e) {
                Log::error("Failed to process reordering rule {$rule->id}: " . $e->getMessage());
            }
        }

        Log::info("Reordering scheduler completed. Created {$suggestionsCreated} suggestions.");
        
        return $suggestionsCreated;
    }

    /**
     * Create MTO suggestion for specific demand
     */
    public function createMTOSuggestion(ReorderingRule $rule, float $demandQty, string $originReference): ReplenishmentSuggestion
    {
        $currentDate = Carbon::now();
        $deliveryDate = $currentDate->copy()->addDays($rule->lead_time_days);

        return ReplenishmentSuggestion::create([
            'company_id' => $rule->company_id,
            'product_id' => $rule->product_id,
            'location_id' => $rule->location_id,
            'reordering_rule_id' => $rule->id,
            'suggested_qty' => $demandQty,
            'priority' => 'urgent',
            'route' => ReorderingRoute::MTO,
            'reason' => "Make-to-Order replenishment for {$originReference}",
            'origin_reference' => $originReference,
            'suggested_order_date' => $currentDate,
            'expected_delivery_date' => $deliveryDate,
        ]);
    }

    /**
     * Check if a suggestion should be created for the given rule
     */
    private function shouldCreateSuggestion(ReorderingRule $rule): bool
    {
        // Skip if there's already an unprocessed suggestion for this rule
        $existingSuggestion = ReplenishmentSuggestion::where('reordering_rule_id', $rule->id)
            ->unprocessed()
            ->exists();

        if ($existingSuggestion) {
            return false;
        }

        if ($rule->route === ReorderingRoute::MTO) {
            // MTO suggestions are created on-demand, not by scheduler
            return false;
        }

        // Get current stock levels
        $currentQty = $this->getCurrentQuantity($rule);
        $availableQty = $this->getAvailableQuantity($rule);

        // Check if below minimum or safety stock
        return $currentQty < $rule->min_qty || $availableQty < $rule->safety_stock;
    }

    /**
     * Create a replenishment suggestion for the given rule
     */
    private function createReplenishmentSuggestion(ReorderingRule $rule, Carbon $currentDate): ReplenishmentSuggestion
    {
        $currentQty = $this->getCurrentQuantity($rule);
        $availableQty = $this->getAvailableQuantity($rule);
        
        $suggestedQty = $rule->calculateSuggestedQuantity($currentQty);
        $priority = $rule->determinePriority($availableQty);
        $reason = $rule->generateReason($currentQty, $availableQty);
        
        $deliveryDate = $currentDate->copy()->addDays($rule->lead_time_days);

        return ReplenishmentSuggestion::create([
            'company_id' => $rule->company_id,
            'product_id' => $rule->product_id,
            'location_id' => $rule->location_id,
            'reordering_rule_id' => $rule->id,
            'suggested_qty' => $suggestedQty,
            'priority' => $priority,
            'route' => $rule->route,
            'reason' => $reason,
            'origin_reference' => null,
            'suggested_order_date' => $currentDate,
            'expected_delivery_date' => $deliveryDate,
        ]);
    }

    /**
     * Get current total quantity for a rule's product and location
     */
    private function getCurrentQuantity(ReorderingRule $rule): float
    {
        return (float) StockQuant::where('company_id', $rule->company_id)
            ->where('product_id', $rule->product_id)
            ->where('location_id', $rule->location_id)
            ->sum('quantity');
    }

    /**
     * Get available quantity (total - reserved) for a rule's product and location
     */
    private function getAvailableQuantity(ReorderingRule $rule): float
    {
        return $this->stockQuantService->available(
            $rule->company_id,
            $rule->product_id,
            $rule->location_id
        );
    }

    /**
     * Mark suggestions as processed
     */
    public function markSuggestionsAsProcessed(array $suggestionIds): void
    {
        ReplenishmentSuggestion::whereIn('id', $suggestionIds)
            ->update([
                'processed' => true,
                'processed_at' => Carbon::now(),
            ]);
    }

    /**
     * Get pending suggestions grouped by priority
     */
    public function getPendingSuggestionsByPriority(): array
    {
        $suggestions = ReplenishmentSuggestion::unprocessed()
            ->with(['product', 'location', 'reorderingRule'])
            ->orderBy('priority')
            ->orderBy('suggested_order_date')
            ->get()
            ->groupBy('priority');

        return [
            'urgent' => $suggestions->get('urgent', collect()),
            'high' => $suggestions->get('high', collect()),
            'normal' => $suggestions->get('normal', collect()),
        ];
    }

    /**
     * Clean up old processed suggestions
     */
    public function cleanupOldSuggestions(int $daysOld = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysOld);
        
        return ReplenishmentSuggestion::where('processed', true)
            ->where('processed_at', '<', $cutoffDate)
            ->delete();
    }
}
