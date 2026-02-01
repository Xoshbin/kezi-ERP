<?php

namespace Jmeryar\Accounting\Contracts;

use App\Models\User;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Purchase\Models\VendorBill;

/**
 * Contract for creating journal entries from vendor bills.
 *
 * This interface defines the contract for converting a VendorBill document
 * into a JournalEntry following proper accounting rules. It allows the
 * Purchase module to depend on the interface rather than the concrete
 * implementation, enabling loose coupling.
 */
interface VendorBillJournalEntryCreatorContract
{
    /**
     * Create a journal entry for the given vendor bill.
     *
     * @param  VendorBill  $vendorBill  The vendor bill to create journal entry for
     * @param  User  $user  The user performing the action
     * @return JournalEntry The created journal entry
     */
    public function execute(VendorBill $vendorBill, User $user): JournalEntry;
}
