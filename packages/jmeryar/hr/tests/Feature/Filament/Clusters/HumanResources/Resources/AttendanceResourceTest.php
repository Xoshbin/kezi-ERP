<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Attendances\AttendanceResource;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Attendances\Pages\CreateAttendance;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Attendances\Pages\EditAttendance;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\Attendances\Pages\ListAttendances;
use Jmeryar\HR\Models\Attendance;
use Jmeryar\HR\Models\Employee;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('AttendanceResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(AttendanceResource::getUrl())
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(AttendanceResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can render edit page', function () {
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => Employee::factory()->create(['company_id' => $this->company->id])->id,
        ]);

        $this->actingAs($this->user)
            ->get(AttendanceResource::getUrl('edit', ['record' => $attendance]))
            ->assertSuccessful();
    });

    it('can list attendances', function () {
        $attendances = Attendance::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => Employee::factory()->create(['company_id' => $this->company->id])->id,
        ]);

        livewire(ListAttendances::class)
            ->assertCanSeeTableRecords($attendances);
    });

    it('can create attendance', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $date = now()->toDateString();

        livewire(CreateAttendance::class)
            ->fillForm([
                'employee_id' => $employee->id,
                'attendance_date' => $date,
                'status' => 'present',
                'total_hours' => '8',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('attendances', [
            'employee_id' => $employee->id,
            'attendance_date' => $date.' 00:00:00',
            'status' => 'present',
            'total_hours' => 8,
        ]);
    });

    it('validates required fields', function () {
        livewire(CreateAttendance::class)
            ->fillForm([
                'employee_id' => null,
                'attendance_date' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'employee_id' => 'required',
                'attendance_date' => 'required',
            ]);
    });

    it('can edit attendance', function () {
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => Employee::factory()->create(['company_id' => $this->company->id])->id,
            'status' => 'present',
        ]);

        $newStatus = 'absent';

        livewire(EditAttendance::class, ['record' => $attendance->getRouteKey()])
            ->fillForm([
                'status' => $newStatus,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($attendance->refresh()->status)->toBe($newStatus);
    });

    it('can delete attendance', function () {
        $attendance = Attendance::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => Employee::factory()->create(['company_id' => $this->company->id])->id,
        ]);

        livewire(ListAttendances::class)
            ->callTableAction('delete', $attendance);

        $this->assertDatabaseMissing('attendances', ['id' => $attendance->id]);
    });
});
