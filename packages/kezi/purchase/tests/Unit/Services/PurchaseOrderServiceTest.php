<?php

namespace Kezi\Purchase\tests\Unit\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Kezi\Accounting\Services\Accounting\LockDateService;
use Kezi\Accounting\Services\BudgetControlService;
use Kezi\Foundation\Services\SequenceService;
use Kezi\Purchase\Enums\Purchases\PurchaseOrderStatus;
use Kezi\Purchase\Events\PurchaseOrderConfirmed;
use Kezi\Purchase\Models\PurchaseOrder;
use Kezi\Purchase\Models\PurchaseOrderLine;
use Kezi\Purchase\Services\PurchaseOrderService;
use Mockery;

beforeEach(function () {
    $this->company = Company::factory()->create();
    $this->user = User::factory()->create();
    $this->user->companies()->attach($this->company);

    // Mock Dependencies
    $this->lockDateService = Mockery::mock(LockDateService::class);
    $this->sequenceService = Mockery::mock(SequenceService::class);
    $this->budgetControlService = Mockery::mock(BudgetControlService::class);

    $this->service = new PurchaseOrderService(
        $this->lockDateService,
        $this->sequenceService,
        $this->budgetControlService
    );
});

describe('sendRFQ', function () {
    it('sends RFQ successfully', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::RFQ,
            'company_id' => $this->company->id,
        ]);
        PurchaseOrderLine::factory()->create(['purchase_order_id' => $po->id]);

        $result = $this->service->sendRFQ($po, $this->user);

        expect($result->status)->toBe(PurchaseOrderStatus::RFQSent);
    });

    it('throws exception if PO cannot send RFQ', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Confirmed,
            'company_id' => $this->company->id,
        ]);

        expect(fn () => $this->service->sendRFQ($po, $this->user))
            ->toThrow(InvalidArgumentException::class, 'RFQ cannot be sent in the current state.');
    });

    it('throws exception if PO has no lines', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::RFQ,
            'company_id' => $this->company->id,
        ]);

        expect(fn () => $this->service->sendRFQ($po, $this->user))
            ->toThrow(InvalidArgumentException::class, 'Cannot send RFQ without any lines.');
    });
});

describe('send', function () {
    it('sends purchase order successfully and generates sequence', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Draft,
            'company_id' => $this->company->id,
            'po_date' => now(),
            'po_number' => null,
        ]);
        PurchaseOrderLine::factory()->create(['purchase_order_id' => $po->id]);

        $this->lockDateService->shouldReceive('enforce')
            ->once()
            ->with(
                Mockery::on(fn ($c) => $c->id === $this->company->id),
                Mockery::on(fn ($date) => $date->format('Y-m-d') === $po->po_date->format('Y-m-d'))
            );

        $this->sequenceService->shouldReceive('getNextNumber')
            ->once()
            ->with(
                Mockery::on(fn ($c) => $c->id === $this->company->id),
                'purchase_order',
                'PO'
            )
            ->andReturn('PO-001');

        $result = $this->service->send($po, $this->user);

        expect($result->status)->toBe(PurchaseOrderStatus::Sent)
            ->and($result->po_number)->toBe('PO-001');
    });

    it('throws exception if PO cannot be sent', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Done,
            'company_id' => $this->company->id,
        ]);

        expect(fn () => $this->service->send($po, $this->user))
            ->toThrow(InvalidArgumentException::class, 'Purchase order cannot be sent in its current state.');
    });

    it('throws exception if PO has no lines', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Draft,
            'company_id' => $this->company->id,
        ]);

        expect(fn () => $this->service->send($po, $this->user))
            ->toThrow(InvalidArgumentException::class, 'Cannot send purchase order without any lines.');
    });
});

describe('confirm', function () {
    it('confirms purchase order successfully', function () {
        Event::fake();

        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Draft,
            'company_id' => $this->company->id,
            'po_date' => now(),
            'po_number' => null,
        ]);
        PurchaseOrderLine::factory()->create(['purchase_order_id' => $po->id]);

        $this->lockDateService->shouldReceive('enforce')
            ->once()
            ->with(
                Mockery::on(fn ($c) => $c->id === $this->company->id),
                Mockery::on(fn ($date) => $date->format('Y-m-d') === $po->po_date->format('Y-m-d'))
            );

        $this->budgetControlService->shouldReceive('validatePurchaseOrder')
            ->once()
            ->with(
                Mockery::on(fn ($arg) => $arg->id === $po->id)
            );

        $this->sequenceService->shouldReceive('getNextNumber')
            ->once()
            ->with(
                Mockery::on(fn ($c) => $c->id === $this->company->id),
                'purchase_order',
                'PO'
            )
            ->andReturn('PO-002');

        $result = $this->service->confirm($po, $this->user);

        expect($result->confirmed_at)->not->toBeNull()
            ->and($result->po_number)->toBe('PO-002');

        Event::assertDispatched(PurchaseOrderConfirmed::class);
    });

    it('throws exception if PO cannot be confirmed', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Cancelled,
            'company_id' => $this->company->id,
        ]);

        expect(fn () => $this->service->confirm($po, $this->user))
            ->toThrow(InvalidArgumentException::class, 'Purchase order cannot be confirmed in its current state.');
    });

    it('throws exception if PO has no lines', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Draft,
            'company_id' => $this->company->id,
        ]);

        expect(fn () => $this->service->confirm($po, $this->user))
            ->toThrow(InvalidArgumentException::class, 'Cannot confirm purchase order without any lines.');
    });
});

describe('cancel', function () {
    it('cancels purchase order successfully', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Draft,
            'company_id' => $this->company->id,
            'po_date' => now(),
        ]);

        $this->lockDateService->shouldReceive('enforce')
            ->once()
            ->with(
                Mockery::on(fn ($c) => $c->id === $this->company->id),
                Mockery::on(fn ($date) => $date->format('Y-m-d') === $po->po_date->format('Y-m-d'))
            );

        $result = $this->service->cancel($po, $this->user, 'Too expensive');

        expect($result->status)->toBe(PurchaseOrderStatus::Cancelled)
            ->and($result->cancelled_at)->not->toBeNull()
            ->and($result->notes)->toContain('Cancelled: Too expensive');
    });

    it('throws exception if PO cannot be cancelled', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Done,
            'company_id' => $this->company->id,
        ]);

        expect(fn () => $this->service->cancel($po, $this->user))
            ->toThrow(InvalidArgumentException::class, 'Purchase order cannot be cancelled in its current state.');
    });
});

describe('updateReceivedQuantity', function () {
    it('updates received quantity successfully', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Confirmed, // Needs to be in a state that can receive goods
            'company_id' => $this->company->id,
        ]);
        $line = PurchaseOrderLine::factory()->create([
            'purchase_order_id' => $po->id,
            'quantity' => 10,
            'quantity_received' => 0,
        ]);

        $result = $this->service->updateReceivedQuantity($po, $line->id, 5);

        $line->refresh();
        expect($line->quantity_received)->toBe(5.0);
    });

    it('throws exception if PO cannot receive goods', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Draft,
            'company_id' => $this->company->id,
        ]);
        $line = PurchaseOrderLine::factory()->create(['purchase_order_id' => $po->id]);

        expect(fn () => $this->service->updateReceivedQuantity($po, $line->id, 5))
            ->toThrow(InvalidArgumentException::class, 'Cannot receive goods for this purchase order.');
    });
});

describe('markAsDone', function () {
    it('marks purchase order as done successfully', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::FullyBilled,
            'company_id' => $this->company->id,
        ]);

        $result = $this->service->markAsDone($po, $this->user);

        expect($result->status)->toBe(PurchaseOrderStatus::Done);
    });

    it('throws exception if PO is not fully billed', function () {
        $po = PurchaseOrder::factory()->create([
            'status' => PurchaseOrderStatus::Confirmed,
            'company_id' => $this->company->id,
        ]);

        expect(fn () => $this->service->markAsDone($po, $this->user))
            ->toThrow(InvalidArgumentException::class, 'Purchase order must be fully billed before it can be marked as done.');
    });
});
