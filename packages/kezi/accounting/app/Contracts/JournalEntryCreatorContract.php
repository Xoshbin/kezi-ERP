<?php

namespace Kezi\Accounting\Contracts;

use Kezi\Accounting\DataTransferObjects\Accounting\CreateJournalEntryDTO;
use Kezi\Accounting\Models\JournalEntry;

/**
 * Contract for creating journal entries.
 *
 * This interface defines the contract that any journal entry creator must implement.
 * It allows other modules (Sales, Purchase, Inventory) to depend on the interface
 * rather than the concrete implementation, enabling loose coupling.
 */
interface JournalEntryCreatorContract
{
    /**
     * Create a journal entry from the given DTO.
     *
     * @param  CreateJournalEntryDTO  $dto  The data transfer object containing journal entry data
     * @return JournalEntry The created journal entry
     */
    public function execute(CreateJournalEntryDTO $dto): JournalEntry;
}
