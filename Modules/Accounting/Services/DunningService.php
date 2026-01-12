<?php

namespace Modules\Accounting\Services;

use Modules\Accounting\Actions\Dunning\CreateDunningLevelAction;
use Modules\Accounting\Actions\Dunning\ProcessDunningRunAction;
use Modules\Accounting\Actions\Dunning\UpdateDunningLevelAction;
use Modules\Accounting\DataTransferObjects\DunningLevelDTO;
use Modules\Accounting\Models\DunningLevel;

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
