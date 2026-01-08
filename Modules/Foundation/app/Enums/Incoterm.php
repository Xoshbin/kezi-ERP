<?php

namespace Modules\Foundation\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum Incoterm: string implements HasDescription, HasLabel
{
    // Any Mode of Transport
    case Exw = 'exw'; // Ex Works
    case Fca = 'fca'; // Free Carrier
    case Cpt = 'cpt'; // Carriage Paid To
    case Cip = 'cip'; // Carriage and Insurance Paid To
    case Dap = 'dap'; // Delivered at Place
    case Dpu = 'dpu'; // Delivered at Place Unloaded
    case Ddp = 'ddp'; // Delivered Duty Paid

    // Sea and Inland Waterway Transport Only
    case Fas = 'fas'; // Free Alongside Ship
    case Fob = 'fob'; // Free on Board
    case Cfr = 'cfr'; // Cost and Freight
    case Cif = 'cif'; // Cost, Insurance and Freight

    public function getLabel(): string
    {
        return __('enums.incoterm.'.$this->value);
    }

    public function getDescription(): ?string
    {
        return match ($this) {
            self::Exw => 'Buyer handles everything from seller\'s premises.',
            self::Fca => 'Seller hands over goods to carrier at named place.',
            self::Cpt => 'Seller pays carriage to destination; risk transfers to buyer at carrier.',
            self::Cip => 'Seller pays carriage and insurance to destination.',
            self::Dap => 'Seller delivers to destination; buyer unloads.',
            self::Dpu => 'Seller delivers and unloads at destination.',
            self::Ddp => 'Seller handles everything to buyer\'s door including duties.',
            self::Fas => 'Seller places goods alongside ship; buyer handles export/loading.',
            self::Fob => 'Seller loads goods on ship; risk transfers once on board.',
            self::Cfr => 'Seller pays freight to port; risk transfers once on board.',
            self::Cif => 'Seller pays freight and insurance to port.',
        };
    }

    public function sellerPaysFreight(): bool
    {
        return match ($this) {
            self::Exw, self::Fca, self::Fas, self::Fob => false,
            default => true,
        };
    }

    public function sellerPaysInsurance(): bool
    {
        return match ($this) {
            self::Cip, self::Cif, self::Ddp => true, // DDP usually implies it effectively
            default => false,
        };
    }

    public function sellerHandlesExportClearance(): bool
    {
        return match ($this) {
            self::Exw => false,
            default => true,
        };
    }

    public function sellerHandlesImportClearance(): bool
    {
        return match ($this) {
            self::Ddp => true,
            default => false,
        };
    }

    public function shouldBuyerPayFor(ShippingCostType $type): bool
    {
        return match ($type) {
            ShippingCostType::Freight => ! $this->sellerPaysFreight(),
            ShippingCostType::Insurance => ! $this->sellerPaysInsurance(),
            ShippingCostType::CustomsDuty => ! $this->sellerHandlesImportClearance(),
            ShippingCostType::Handling, ShippingCostType::PortCharges => match ($this) {
                self::Exw, self::Fca, self::Fas, self::Fob => true,
                default => false,
            },
        };
    }

    public function getCostResponsibilities(): \Modules\Foundation\DataTransferObjects\ShippingCostResponsibilityDTO
    {
        return new \Modules\Foundation\DataTransferObjects\ShippingCostResponsibilityDTO(
            buyerPaysFreight: ! $this->sellerPaysFreight(),
            buyerPaysInsurance: ! $this->sellerPaysInsurance(),
            buyerHandlesExportClearance: ! $this->sellerHandlesExportClearance(),
            buyerHandlesImportClearance: ! $this->sellerHandlesImportClearance(),
        );
    }
}
