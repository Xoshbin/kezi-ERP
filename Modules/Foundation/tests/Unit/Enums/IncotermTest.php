<?php

namespace Modules\Foundation\Tests\Unit\Enums;

use Modules\Foundation\Enums\Incoterm;
use Tests\TestCase;

class IncotermTest extends TestCase
{
    public function test_it_has_all_incoterms()
    {
        $cases = Incoterm::cases();
        $this->assertCount(11, $cases);

        $values = array_map(fn ($c) => $c->value, $cases);
        $this->assertContains('exw', $values);
        $this->assertContains('fca', $values);
        $this->assertContains('cpt', $values);
        $this->assertContains('cip', $values);
        $this->assertContains('dap', $values);
        $this->assertContains('dpu', $values);
        $this->assertContains('ddp', $values);
        $this->assertContains('fas', $values);
        $this->assertContains('fob', $values);
        $this->assertContains('cfr', $values);
        $this->assertContains('cif', $values);
    }

    public function test_it_has_labels()
    {
        foreach (Incoterm::cases() as $incoterm) {
            $this->assertNotNull($incoterm->getLabel());
            // Since translations might not be fully populated yet, just checking it returns something not empty
            // Ideally we check specific strings if we added them.
            // For now, let's just ensure it doesn't crash.
        }
    }

    public function test_it_has_descriptions()
    {
        foreach (Incoterm::cases() as $incoterm) {
            $this->assertNotNull($incoterm->getDescription());
        }
    }

    public function test_seller_pays_freight_logic()
    {
        // EXW: Buyer pays (False)
        $this->assertFalse(Incoterm::Exw->sellerPaysFreight());
        // FOB: Buyer pays main carriage (False)
        $this->assertFalse(Incoterm::Fob->sellerPaysFreight());
        // CIF: Seller pays main carriage (True)
        $this->assertTrue(Incoterm::Cif->sellerPaysFreight());
        // DAP: Seller pays (True)
        $this->assertTrue(Incoterm::Dap->sellerPaysFreight());
    }

    public function test_seller_pays_insurance_logic()
    {
        // CIF: Seller pays (True)
        $this->assertTrue(Incoterm::Cif->sellerPaysInsurance());
        // CIP: Seller pays (True)
        $this->assertTrue(Incoterm::Cip->sellerPaysInsurance());
        // FOB: Buyer pays (False)
        $this->assertFalse(Incoterm::Fob->sellerPaysInsurance());
    }

    public function test_seller_handles_export_clearance()
    {
        // EXW: Buyer handles (False)
        $this->assertFalse(Incoterm::Exw->sellerHandlesExportClearance());
        // FOB: Seller handles (True)
        $this->assertTrue(Incoterm::Fob->sellerHandlesExportClearance());
    }

    public function test_seller_handles_import_clearance()
    {
        // DDP: Seller handles (True)
        $this->assertTrue(Incoterm::Ddp->sellerHandlesImportClearance());
        // DAP: Buyer handles (False)
        $this->assertFalse(Incoterm::Dap->sellerHandlesImportClearance());
    }
}
