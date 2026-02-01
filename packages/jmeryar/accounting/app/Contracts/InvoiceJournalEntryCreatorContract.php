<?php

namespace Jmeryar\Accounting\Contracts;

use App\Models\User;
use Jmeryar\Accounting\Models\JournalEntry;
use Jmeryar\Sales\Models\Invoice;

/**
 * Contract for creating journal entries from invoices.
 *
 * This interface defines the contract for converting an Invoice document
 * into a JournalEntry following proper accounting rules. It allows the
 * Sales module to depend on the interface rather than the concrete
 * implementation, enabling loose coupling.
 */
interface InvoiceJournalEntryCreatorContract
{
    /**
     * Create a journal entry for the given invoice.
     *
     * @param  Invoice  $invoice  The invoice to create journal entry for
     * @param  User  $user  The user performing the action
     * @return JournalEntry The created journal entry
     */
    public function execute(Invoice $invoice, User $user): JournalEntry;
}
