<?php

namespace Jmeryar\Accounting\Services;

use Jmeryar\Accounting\Actions\Dunning\CreateDunningLevelAction;
use Jmeryar\Accounting\Actions\Dunning\ProcessDunningRunAction;
use Jmeryar\Accounting\Actions\Dunning\UpdateDunningLevelAction;
use Jmeryar\Accounting\DataTransferObjects\DunningLevelDTO;
use Jmeryar\Accounting\Models\DunningLevel;

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
