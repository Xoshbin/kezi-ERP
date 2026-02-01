<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\LeaveRequestResource;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages\CreateLeaveRequest;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages\EditLeaveRequest;
use Jmeryar\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Pages\ListLeaveRequests;
use Jmeryar\HR\Models\Employee;
use Jmeryar\HR\Models\LeaveRequest;
use Jmeryar\HR\Models\LeaveType;
use Tests\Traits\WithConfiguredCompany;

use function Pest\Livewire\livewire;

uses(RefreshDatabase::class, WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
});

describe('LeaveRequestResource', function () {
    it('can render list page', function () {
        $this->actingAs($this->user)
            ->get(LeaveRequestResource::getUrl())
            ->assertSuccessful();
    });

    it('can render create page', function () {
        $this->actingAs($this->user)
            ->get(LeaveRequestResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can render edit page', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);

        $this->actingAs($this->user)
            ->get(LeaveRequestResource::getUrl('edit', ['record' => $leaveRequest]))
            ->assertSuccessful();
    });

    it('can list leave requests', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $leaveRequests = LeaveRequest::factory()->count(3)->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);

        livewire(ListLeaveRequests::class)
            ->assertCanSeeTableRecords($leaveRequests);
    });

    it('can create leave request', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $newData = LeaveRequest::factory()->make([
            'start_date' => now()->addDays(5),
            'end_date' => now()->addDays(7),
        ]);

        livewire(CreateLeaveRequest::class)
            ->fillForm([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $newData->start_date,
                'end_date' => $newData->end_date,
                'days_requested' => 3,
                'reason' => 'Vacation',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('leave_requests', [
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'reason' => 'Vacation',
        ]);
    });

    it('validates required fields', function () {
        livewire(CreateLeaveRequest::class)
            ->fillForm([
                'employee_id' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'employee_id' => 'required',
                'leave_type_id' => 'required',
                'start_date' => 'required',
                'end_date' => 'required',
                'days_requested' => 'required',
                'reason' => 'required',
            ]);
    });

    it('can edit leave request', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'reason' => 'Original Reason',
        ]);

        $newReason = 'Updated Reason';

        livewire(EditLeaveRequest::class, ['record' => $leaveRequest->getRouteKey()])
            ->fillForm([
                'reason' => $newReason,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        expect($leaveRequest->refresh()->reason)->toBe($newReason);
    });

    it('can delete leave request', function () {
        $employee = Employee::factory()->create(['company_id' => $this->company->id]);
        $leaveType = LeaveType::factory()->create(['company_id' => $this->company->id]);

        $leaveRequest = LeaveRequest::factory()->create([
            'company_id' => $this->company->id,
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);

        livewire(ListLeaveRequests::class)
            // Function callTableAction expects action name and record.
            // If delete action is available.
            ->callTableAction('delete', $leaveRequest);

        $this->assertDatabaseMissing('leave_requests', [
            'id' => $leaveRequest->id,
        ]);
    });
});
