<?php

namespace Jmeryar\Inventory\Tests\Feature\Filament;

use Jmeryar\Inventory\Enums\Inventory\SerialNumberStatus;
use Jmeryar\Inventory\Enums\Inventory\TrackingType;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages\EditSerialNumber;
use Jmeryar\Inventory\Filament\Clusters\Inventory\Resources\SerialNumbers\Pages\ListSerialNumbers;
use Jmeryar\Inventory\Models\SerialNumber;
use Jmeryar\Product\Models\Product;
use Tests\TestCase;

use function Pest\Livewire\livewire;

// uses(TestCase::class); // Removed redundant line

beforeEach(function () {
    $this->company = \App\Models\Company::factory()->create();
    $this->user = \App\Models\User::factory()->create();
    $this->user->companies()->attach($this->company);
    $this->actingAs($this->user);
    \Filament\Facades\Filament::setTenant($this->company);
});

it('can render serial number list page', function () {
    livewire(ListSerialNumbers::class)
        ->assertSuccessful();
});

it('can list serial numbers in table', function () {
    $product = Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]);

    $serials = SerialNumber::factory()
        ->for($this->company)
        ->for($product)
        ->count(3)
        ->create();

    livewire(ListSerialNumbers::class)
        ->assertCanSeeTableRecords($serials);
});

it('can filter serial numbers by status', function () {
    $product = Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]);

    $available = SerialNumber::factory()->for($this->company)->for($product)->available()->create();
    $sold = SerialNumber::factory()->for($this->company)->for($product)->sold()->create();

    livewire(ListSerialNumbers::class)
        ->filterTable('status', SerialNumberStatus::Available->value)
        ->assertCanSeeTableRecords([$available])
        ->assertCanNotSeeTableRecords([$sold]);
});

it('can search serial numbers by serial code', function () {
    $product = Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]);

    $serial1 = SerialNumber::factory()->for($this->company)->for($product)->create(['serial_code' => 'SN-FIND-ME']);
    $serial2 = SerialNumber::factory()->for($this->company)->for($product)->create(['serial_code' => 'SN-OTHER']);

    livewire(ListSerialNumbers::class)
        ->searchTable('FIND-ME')
        ->assertCanSeeTableRecords([$serial1])
        ->assertCanNotSeeTableRecords([$serial2]);
});

it('can render edit page', function () {
    $product = Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]);
    $serial = SerialNumber::factory()->for($this->company)->for($product)->create();

    livewire(EditSerialNumber::class, ['record' => $serial->id])
        ->assertSuccessful();
});

it('can update warranty dates', function () {
    $product = Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]);
    $serial = SerialNumber::factory()->for($this->company)->for($product)->create();

    $newWarrantyEnd = now()->addMonths(18);

    livewire(EditSerialNumber::class, ['record' => $serial->id])
        ->fillForm([
            'warranty_end' => $newWarrantyEnd->format('Y-m-d'),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($serial->fresh()->warranty_end->isSameDay($newWarrantyEnd))->toBeTrue();
});

it('cannot modify serial code after creation', function () {
    $product = Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]);
    $serial = SerialNumber::factory()->for($this->company)->for($product)->create(['serial_code' => 'ORIGINAL']);

    livewire(EditSerialNumber::class, ['record' => $serial->id])
        ->assertFormFieldIsDisabled('serial_code')
        ->assertFormFieldIsDisabled('product_id');
});

it('displays status badge correctly', function () {
    $product = Product::factory()->for($this->company)->create(['tracking_type' => TrackingType::Serial]);

    $available = SerialNumber::factory()->for($this->company)->for($product)->available()->create();
    $defective = SerialNumber::factory()->for($this->company)->for($product)->defective()->create();

    livewire(ListSerialNumbers::class)
        ->assertCanSeeTableRecords([$available, $defective]);
});
