<?php

namespace Modules\QualityControl\Enums;

enum QualityTriggerOperation: string
{
    case GoodsReceipt = 'goods_receipt';
    case InternalTransfer = 'internal_transfer';
    case ManufacturingOutput = 'manufacturing_output';
    case CustomerDelivery = 'customer_delivery';

    public function label(): string
    {
        return match ($this) {
            self::GoodsReceipt => __('quality::enums.trigger_operation.goods_receipt'),
            self::InternalTransfer => __('quality::enums.trigger_operation.internal_transfer'),
            self::ManufacturingOutput => __('quality::enums.trigger_operation.manufacturing_output'),
            self::CustomerDelivery => __('quality::enums.trigger_operation.customer_delivery'),
        };
    }
}
