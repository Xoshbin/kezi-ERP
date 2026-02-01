<?php

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Gate;
use Jmeryar\HR\DataTransferObjects\HumanResources\CreateLeaveRequestDTO;
use Jmeryar\HR\Models\Employee;
use Jmeryar\HR\Models\EmploymentContract;
use Jmeryar\HR\Models\LeaveRequest;
use Jmeryar\HR\Models\LeaveType;
use Jmeryar\HR\Services\HumanResources\LeaveManagementService;
use Tests\Traits\WithConfiguredCompany;

uses(WithConfiguredCompany::class);

beforeEach(function () {
    $this->setupWithConfiguredCompany();
    Carbon::setTestNow(Carbon::parse('2026-01-05 10:00:00')); // Monday
    $this->service = app(LeaveManagementService::class);

    // Setup employee with contract
    $this->employee = Employee::factory()->create(['company_id' => $this->company->id]);
    $this->contract = EmploymentContract::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'start_date' => Carbon::now()->subYear(),
        'annual_leave_days' => 20,
        'sick_leave_days' => 10,
    ]);
});

test('it can create leave request if balance allows', function () {
    $leaveType = LeaveType::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'annual',
        'requires_approval' => true,
        'min_notice_days' => 1,
    ]);

    $dto = new CreateLeaveRequestDTO(
        company_id: $this->company->id,
        employee_id: $this->employee->id,
        leave_type_id: $leaveType->id,
        start_date: Carbon::now()->addDays(2)->format('Y-m-d'),
        end_date: Carbon::now()->addDays(4)->format('Y-m-d'),
        days_requested: 3,
        requested_by_user_id: $this->user->id,
        reason: 'Holiday',
    );

    $leaveRequest = $this->service->createLeaveRequest($dto);

    expect($leaveRequest)->toBeInstanceOf(LeaveRequest::class)
        ->and($leaveRequest->status)->toBe('pending');
});

test('it throws exception if insufficient leave balance', function () {
    $leaveType = LeaveType::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'annual',
    ]);

    // Contract has 20 days. Use them up first?
    // Or just request more than 20.

    $dto = new CreateLeaveRequestDTO(
        company_id: $this->company->id,
        employee_id: $this->employee->id,
        leave_type_id: $leaveType->id,
        start_date: Carbon::now()->addDays(2)->format('Y-m-d'),
        end_date: Carbon::now()->addDays(25)->format('Y-m-d'),
        days_requested: 24, // > 20
        requested_by_user_id: $this->user->id,
        reason: 'Long holiday',
    );

    $this->service->createLeaveRequest($dto);
})->throws(Exception::class, 'Insufficient leave balance');

test('it throws exception if notice period not met', function () {
    $leaveType = LeaveType::factory()->create([
        'company_id' => $this->company->id,
        'min_notice_days' => 7,
    ]);

    $dto = new CreateLeaveRequestDTO(
        company_id: $this->company->id,
        employee_id: $this->employee->id,
        leave_type_id: $leaveType->id,
        start_date: Carbon::now()->addDays(2)->format('Y-m-d'), // only 2 days notice
        end_date: Carbon::now()->addDays(3)->format('Y-m-d'),
        days_requested: 2,
        requested_by_user_id: $this->user->id,
        reason: 'Urgent',
    );

    $this->service->createLeaveRequest($dto);
})->throws(Exception::class, 'Minimum notice of 7 days required');

test('it allows approval of pending request', function () {
    $approver = User::factory()->create();
    $approver->companies()->attach($this->company);

    $leaveRequest = LeaveRequest::factory()->create([
        'company_id' => $this->company->id,
        'status' => 'pending',
        'employee_id' => $this->employee->id,
        'start_date' => Carbon::now()->format('Y-m-d'), // Today
        'end_date' => Carbon::now()->format('Y-m-d'),
        'days_requested' => 1,
    ]);

    // Mock Gate authorization
    Gate::shouldReceive('forUser')->with($approver)->andReturnSelf();
    Gate::shouldReceive('authorize')->with('approve', $leaveRequest)->andReturn(true);

    $this->service->approveLeaveRequest($leaveRequest, $approver, 'Approved');

    $leaveRequest->refresh();
    expect($leaveRequest->status)->toBe('approved')
        ->and($leaveRequest->approved_by_user_id)->toBe($approver->id)
        ->and($leaveRequest->approval_notes)->toBe('Approved');

    // Check attendance created
    $attendance = $this->employee->attendances()
        ->where('leave_request_id', $leaveRequest->id)
        ->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->status)->toBe('on_leave');
});

test('it prevents creating overlapping leave requests', function () {
    $leaveType = LeaveType::factory()->create([
        'company_id' => $this->company->id,
        'code' => 'annual',
    ]);

    // Existing leave: 6 days (10 to 15)
    LeaveRequest::factory()->create([
        'company_id' => $this->company->id,
        'employee_id' => $this->employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => Carbon::now()->addDays(10)->format('Y-m-d'),
        'end_date' => Carbon::now()->addDays(15)->format('Y-m-d'),
        'days_requested' => 6,
        'status' => 'approved',
    ]);

    // New overlapping request: 7 days (14 to 20)
    // Total used would be 6 + 7 = 13, which is < 20 available for 'annual'.
    $dto = new CreateLeaveRequestDTO(
        company_id: $this->company->id,
        employee_id: $this->employee->id,
        leave_type_id: $leaveType->id,
        start_date: Carbon::now()->addDays(14)->format('Y-m-d'), // Overlap
        end_date: Carbon::now()->addDays(20)->format('Y-m-d'),
        days_requested: 7,
        requested_by_user_id: $this->user->id,
    );

    $this->service->createLeaveRequest($dto);
})->throws(Exception::class, 'Leave request overlaps with existing leave');
