<?php

namespace Kezi\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Kezi\Inventory\Filament\Clusters\Inventory\Resources\LandedCostResource;

/**
 * @extends CreateRecord<\Kezi\Inventory\Models\LandedCost>
 */
class CreateLandedCost extends CreateRecord
{
    protected static string $resource = LandedCostResource::class;

    public function mount(): void
    {
        parent::mount();

        if (request()->has('vendor_bill_id')) {
            $billId = request()->get('vendor_bill_id');
            $bill = \Kezi\Purchase\Models\VendorBill::find($billId);

            if ($bill) {
                $this->form->fill([
                    'vendor_bill_id' => $bill->id,
                    'vendor_id' => $bill->vendor_id, // Landed Cost doesn't usually track vendor directly in form schema, it's on bill. But we have vendor_bill_id. check schema.
                    // LC schema: company_id, vendor_bill_id, journal_entry_id, amount_total, date, description, allocation_method
                    'amount_total' => $bill->total_amount, // or 0? better to let user verify.
                    'description' => $bill->bill_reference ? "Landed Cost - {$bill->bill_reference}" : "Landed Cost - {$bill->reference}",
                    'date' => $bill->bill_date,
                    'company_id' => $bill->company_id,
                ]);
            }
        }
    }
}
