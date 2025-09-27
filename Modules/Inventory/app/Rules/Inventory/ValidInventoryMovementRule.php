<?php

namespace Modules\Inventory\Rules\Inventory;

use App\Models\Product;
use App\Enums\Inventory\StockMoveType;
use App\Services\Inventory\InventoryMovementValidationService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validation rule for inventory movements
 * 
 * Ensures that inventory movements meet all business requirements
 * before being allowed to proceed.
 */
class ValidInventoryMovementRule implements ValidationRule
{
    public function __construct(
        private \Modules\Product\Models\Product $product,
        private StockMoveType $moveType,
        private float $quantity
    ) {}

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $validationService = app(InventoryMovementValidationService::class);
        
        $result = $validationService->validateMovement(
            $this->product,
            $this->moveType,
            $this->quantity
        );

        if (!$result->isValid()) {
            $errors = $result->getErrors();
            $requirements = $result->getRequirements();
            
            $message = 'Inventory movement validation failed: ' . implode(', ', $errors);
            
            if (!empty($requirements)) {
                $message .= ' Requirements: ' . implode(', ', array_values($requirements));
            }
            
            $fail($message);
        }
    }

    /**
     * Create a rule instance for incoming movements
     */
    public static function incoming(\Modules\Product\Models\Product $product, float $quantity): self
    {
        return new self($product, StockMoveType::Incoming, $quantity);
    }

    /**
     * Create a rule instance for outgoing movements
     */
    public static function outgoing(\Modules\Product\Models\Product $product, float $quantity): self
    {
        return new self($product, StockMoveType::Outgoing, $quantity);
    }
}
