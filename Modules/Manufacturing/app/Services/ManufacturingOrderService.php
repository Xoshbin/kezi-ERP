<?php

namespace Modules\Manufacturing\Services;

use Modules\Manufacturing\Actions\ConfirmManufacturingOrderAction;
use Modules\Manufacturing\Actions\ConsumeComponentsAction;
use Modules\Manufacturing\Actions\CreateManufacturingOrderAction;
use Modules\Manufacturing\Actions\ProduceFinishedGoodsAction;
use Modules\Manufacturing\Actions\StartProductionAction;
use Modules\Manufacturing\DataTransferObjects\CreateManufacturingOrderDTO;
use Modules\Manufacturing\Events\ManufacturingOrderConfirmed;
use Modules\Manufacturing\Events\ProductionCompleted;
use Modules\Manufacturing\Events\ProductionStarted;
use Modules\Manufacturing\Models\ManufacturingOrder;

class ManufacturingOrderService
{
    public function __construct(
        private readonly CreateManufacturingOrderAction $createAction,
        private readonly ConfirmManufacturingOrderAction $confirmAction,
        private readonly StartProductionAction $startProductionAction,
        private readonly ConsumeComponentsAction $consumeComponentsAction,
        private readonly ProduceFinishedGoodsAction $produceFinishedGoodsAction,
    ) {}

    public function create(CreateManufacturingOrderDTO $dto): ManufacturingOrder
    {
        return $this->createAction->execute($dto);
    }

    public function confirm(ManufacturingOrder $mo): ManufacturingOrder
    {
        $mo = $this->confirmAction->execute($mo);

        event(new ManufacturingOrderConfirmed($mo));

        return $mo;
    }

    public function startProduction(ManufacturingOrder $mo): ManufacturingOrder
    {
        $mo = $this->startProductionAction->execute($mo);

        event(new ProductionStarted($mo));

        return $mo;
    }

    public function consumeComponents(ManufacturingOrder $mo): ManufacturingOrder
    {
        return $this->consumeComponentsAction->execute($mo);
    }

    public function produceFinishedGoods(ManufacturingOrder $mo): ManufacturingOrder
    {
        $mo = $this->produceFinishedGoodsAction->execute($mo);

        event(new ProductionCompleted($mo));

        return $mo;
    }

    /**
     * Complete full production workflow: consume components + produce finished goods
     */
    public function completeProduction(ManufacturingOrder $mo): ManufacturingOrder
    {
        // First consume components
        $mo = $this->consumeComponents($mo);

        // Then produce finished goods
        $mo = $this->produceFinishedGoods($mo);

        return $mo;
    }
}
