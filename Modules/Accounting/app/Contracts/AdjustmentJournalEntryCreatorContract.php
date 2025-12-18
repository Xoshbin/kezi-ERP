<?php

namespace Modules\Accounting\Contracts;

use App\Models\User;
use Modules\Accounting\Models\JournalEntry;
use Modules\Inventory\Models\AdjustmentDocument;

/**
 * Contract for creating journal entries from adjustment documents.
 *
 * This interface defines the contract for converting an AdjustmentDocument
 * (credit/debit notes) into a JournalEntry following proper accounting rules.
 * It allows the Inventory module to depend on the interface rather than the
 * concrete implementation, enabling loose coupling.
 */
interface AdjustmentJournalEntryCreatorContract
{
    /**
     * Create a journal entry for the given adjustment document.
     *
     * @param  AdjustmentDocument  $adjustment  The adjustment document to create journal entry for
     * @param  User  $user  The user performing the action
     * @return JournalEntry The created journal entry
     */
    public function execute(AdjustmentDocument $adjustment, User $user): JournalEntry;
}
