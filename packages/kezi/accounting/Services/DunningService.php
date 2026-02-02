<?php

namespace Kezi\Accounting\Services;

use Kezi\Accounting\Actions\Dunning\CreateDunningLevelAction;
use Kezi\Accounting\Actions\Dunning\ProcessDunningRunAction;
use Kezi\Accounting\Actions\Dunning\UpdateDunningLevelAction;
use Kezi\Accounting\DataTransferObjects\DunningLevelDTO;
use Kezi\Accounting\Models\DunningLevel;

class DunningService
{
    public function __construct(
        protected CreateDunningLevelAction $createDunningLevelAction,
        protected UpdateDunningLevelAction $updateDunningLevelAction,
        protected ProcessDunningRunAction $processDunningRunAction
    ) {}

    public function createLevel(DunningLevelDTO $dto): DunningLevel
    {
        return $this->createDunningLevelAction->execute($dto);
    }

    public function updateLevel(DunningLevel $dunningLevel, DunningLevelDTO $dto): DunningLevel
    {
        return $this->updateDunningLevelAction->execute($dunningLevel, $dto);
    }

    public function processRun(int $companyId): void
    {
        $this->processDunningRunAction->execute($companyId);
    }
}
