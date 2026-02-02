<?php

namespace Kezi\Foundation\DataTransferObjects;

readonly class ShippingCostResponsibilityDTO
{
    public function __construct(
        public bool $buyerPaysFreight,
        public bool $buyerPaysInsurance,
        public bool $buyerHandlesExportClearance,
        public bool $buyerHandlesImportClearance,
    ) {}
}
