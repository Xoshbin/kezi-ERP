<?php

namespace Jmeryar\Inventory\DataTransferObjects\LandedCost;

use App\Models\Company;
use App\Models\User;
use Brick\Money\Money;
use Carbon\Carbon;
use Jmeryar\Inventory\Enums\Inventory\LandedCostAllocationMethod;
use Jmeryar\Inventory\Enums\Inventory\LandedCostStatus;

readonly class LandedCostData
{
    public function __construct(
        public Company $company,
        public Carbon $date,
        public Money $amount_total,
        public LandedCostAllocationMethod $allocation_method,
        public ?string $description = null,
        public ?int $vendor_bill_id = null,
        public ?User $created_by_user = null,
        public LandedCostStatus $status = LandedCostStatus::Draft,
    ) {}
}
