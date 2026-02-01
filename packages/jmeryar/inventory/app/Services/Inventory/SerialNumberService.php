<?php

namespace Jmeryar\Inventory\Services\Inventory;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Jmeryar\Foundation\Models\Partner;
use Jmeryar\Inventory\Actions\Inventory\CreateSerialNumberAction;
use Jmeryar\Inventory\DataTransferObjects\Inventory\CreateSerialNumberDTO;
use Jmeryar\Inventory\Enums\Inventory\SerialNumberStatus;
use Jmeryar\Inventory\Models\SerialNumber;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Inventory\Models\StockMove;
use Jmeryar\Product\Models\Product;

class SerialNumberService
{
    public function __construct(
        private readonly CreateSerialNumberAction $createAction
    ) {}

    /**
     * Create a new serial number
     */
    public function create(CreateSerialNumberDTO $dto): SerialNumber
    {
        return $this->createAction->execute($dto);
    }

    /**
     * Mark a serial number as sold
     */
    public function markSold(SerialNumber $serial, Partner $customer): void
    {
        DB::transaction(function () use ($serial, $customer) {
            $serial->update([
                'status' => SerialNumberStatus::Sold,
                'sold_to_partner_id' => $customer->id,
                'sold_at' => now(),
            ]);
        });
    }

    /**
     * Mark a serial number as returned
     */
    public function markReturned(SerialNumber $serial): void
    {
        DB::transaction(function () use ($serial) {
            $serial->update([
                'status' => SerialNumberStatus::Returned,
            ]);
        });
    }

    /**
     * Mark a serial number as defective
     */
    public function markDefective(SerialNumber $serial, string $notes): void
    {
        DB::transaction(function () use ($serial, $notes) {
            $serial->update([
                'status' => SerialNumberStatus::Defective,
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Assign serial number to a location
     */
    public function assignToLocation(SerialNumber $serial, StockLocation $location): void
    {
        DB::transaction(function () use ($serial, $location) {
            $serial->update([
                'current_location_id' => $location->id,
            ]);
        });
    }

    /**
     * Get available serial numbers for a product at a location
     *
     * @return Collection<int, SerialNumber>
     */
    public function getAvailableAtLocation(Product $product, StockLocation $location): Collection
    {
        return SerialNumber::available()
            ->forProduct($product->id)
            ->atLocation($location->id)
            ->get();
    }

    /**
     * Validate if a serial number can be used for an outgoing move
     */
    public function validateForOutgoing(SerialNumber $serial, StockMove $move): bool
    {
        // Serial must be available
        if ($serial->status !== SerialNumberStatus::Available) {
            return false;
        }

        // Check if any product line in the move matches the serial's product and location
        foreach ($move->productLines as $line) {
            // Ensure product matches
            if ($line->product_id !== $serial->product_id) {
                continue;
            }

            // Ensure source location matches
            if ($line->from_location_id === $serial->current_location_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if serial number is under warranty
     */
    public function isUnderWarranty(SerialNumber $serial): bool
    {
        return $serial->isUnderWarranty();
    }

    /**
     * Get serial numbers with warranty expiring within specified days
     *
     * @return Collection<int, SerialNumber>
     */
    public function getWarrantyExpiringWithinDays(int $days, ?int $companyId = null): Collection
    {
        $query = SerialNumber::warrantyExpiringWithin($days);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        return $query->get();
    }
}
