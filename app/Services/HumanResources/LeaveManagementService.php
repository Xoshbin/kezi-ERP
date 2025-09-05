<?php

namespace App\Services\HumanResources;

use App\Actions\HumanResources\CreateLeaveRequestAction;
use App\DataTransferObjects\HumanResources\CreateLeaveRequestDTO;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class LeaveManagementService
{
    public function __construct(
        protected CreateLeaveRequestAction $createLeaveRequestAction,
    ) {}

    /**
     * Create a leave request.
     */
    public function createLeaveRequest(CreateLeaveRequestDTO $createLeaveRequestDTO): LeaveRequest
    {
        return DB::transaction(function () use ($createLeaveRequestDTO) {
            // Validate leave request
            $this->validateLeaveRequest($createLeaveRequestDTO);

            return $this->createLeaveRequestAction->execute($createLeaveRequestDTO);
        });
    }

    /**
     * Approve a leave request.
     */
    public function approveLeaveRequest(LeaveRequest $leaveRequest, User $approver, ?string $notes = null): void
    {
        Gate::forUser($approver)->authorize('approve', $leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            throw new Exception('Only pending leave requests can be approved.');
        }

        DB::transaction(function () use ($leaveRequest, $approver, $notes) {
            $leaveRequest->update([
                'status' => 'approved',
                'approved_by_user_id' => $approver->id,
                'approved_at' => now(),
                'approval_notes' => $notes,
            ]);

            // Create attendance records for approved leave
            $this->createLeaveAttendanceRecords($leaveRequest);
        });
    }

    /**
     * Reject a leave request.
     */
    public function rejectLeaveRequest(LeaveRequest $leaveRequest, User $approver, string $reason): void
    {
        Gate::forUser($approver)->authorize('approve', $leaveRequest);

        if ($leaveRequest->status !== 'pending') {
            throw new Exception('Only pending leave requests can be rejected.');
        }

        $leaveRequest->update([
            'status' => 'rejected',
            'approved_by_user_id' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Cancel a leave request.
     */
    public function cancelLeaveRequest(LeaveRequest $leaveRequest, User $user, string $reason): void
    {
        Gate::forUser($user)->authorize('cancel', $leaveRequest);

        if (! in_array($leaveRequest->status, ['pending', 'approved'])) {
            throw new Exception('Only pending or approved leave requests can be cancelled.');
        }

        DB::transaction(function () use ($leaveRequest, $reason) {
            $wasApproved = $leaveRequest->status === 'approved';

            $leaveRequest->update([
                'status' => 'cancelled',
                'rejection_reason' => $reason,
            ]);

            // Remove attendance records if leave was already approved
            if ($wasApproved) {
                $this->removeLeaveAttendanceRecords($leaveRequest);
            }
        });
    }

    /**
     * Get leave balance for an employee.
     */
    public function getLeaveBalance(Employee $employee, LeaveType $leaveType, ?int $year = null): array
    {
        $year = $year ?? now()->year;
        $contract = $employee->currentContract;

        if (! $contract) {
            return [
                'entitled_days' => 0,
                'used_days' => 0,
                'remaining_days' => 0,
                'pending_days' => 0,
            ];
        }

        // Get entitled days based on leave type and contract
        $entitledDays = match ($leaveType->code) {
            'annual' => $contract->annual_leave_days,
            'sick' => $contract->sick_leave_days,
            'maternity' => $contract->maternity_leave_days,
            'paternity' => $contract->paternity_leave_days,
            default => $leaveType->default_days_per_year,
        };

        // Calculate used days for the year
        $usedDays = $employee->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $year)
            ->sum('days_requested');

        // Calculate pending days
        $pendingDays = $employee->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'pending')
            ->whereYear('start_date', $year)
            ->sum('days_requested');

        // Handle carry forward from previous year
        $carriedForwardDays = 0;
        if ($leaveType->carries_forward && $year > $employee->hire_date->year) {
            $carriedForwardDays = $this->calculateCarriedForwardDays($employee, $leaveType, $year - 1);
        }

        $totalEntitled = $entitledDays + $carriedForwardDays;
        $remainingDays = max(0, $totalEntitled - $usedDays);

        return [
            'entitled_days' => $entitledDays,
            'carried_forward_days' => $carriedForwardDays,
            'total_entitled_days' => $totalEntitled,
            'used_days' => $usedDays,
            'pending_days' => $pendingDays,
            'remaining_days' => $remainingDays,
        ];
    }

    /**
     * Validate leave request.
     */
    private function validateLeaveRequest(CreateLeaveRequestDTO $dto): void
    {
        $employee = Employee::find($dto->employee_id);
        $leaveType = LeaveType::find($dto->leave_type_id);

        // Check if employee has sufficient leave balance
        $balance = $this->getLeaveBalance($employee, $leaveType);
        if ($dto->days_requested > $balance['remaining_days']) {
            throw new Exception('Insufficient leave balance. Available: '.$balance['remaining_days'].' days.');
        }

        // Check minimum notice period
        $startDate = Carbon::parse($dto->start_date);
        $noticeGiven = now()->diffInDays($startDate);
        if ($noticeGiven < $leaveType->min_notice_days) {
            throw new Exception('Minimum notice of '.$leaveType->min_notice_days.' days required.');
        }

        // Check maximum consecutive days
        if ($leaveType->exceedsMaxConsecutiveDays((int) $dto->days_requested)) {
            throw new Exception('Maximum consecutive days allowed: '.$leaveType->max_consecutive_days);
        }

        // Check for overlapping leave requests
        $overlapping = $employee->leaveRequests()
            ->where('status', '!=', 'rejected')
            ->where('status', '!=', 'cancelled')
            ->where(function ($query) use ($dto) {
                $query->whereBetween('start_date', [$dto->start_date, $dto->end_date])
                    ->orWhereBetween('end_date', [$dto->start_date, $dto->end_date])
                    ->orWhere(function ($q) use ($dto) {
                        $q->where('start_date', '<=', $dto->start_date)
                            ->where('end_date', '>=', $dto->end_date);
                    });
            })
            ->exists();

        if ($overlapping) {
            throw new Exception('Leave request overlaps with existing leave.');
        }
    }

    /**
     * Create attendance records for approved leave.
     */
    private function createLeaveAttendanceRecords(LeaveRequest $leaveRequest): void
    {
        $startDate = Carbon::parse($leaveRequest->start_date);
        $endDate = Carbon::parse($leaveRequest->end_date);

        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            // Skip weekends (assuming 5-day work week)
            if (! $currentDate->isWeekend()) {
                /** @var \App\Models\Employee $employee */
                $employee = $leaveRequest->employee;
                $employee->attendances()->updateOrCreate(
                    [
                        'attendance_date' => $currentDate->format('Y-m-d'),
                    ],
                    [
                        'company_id' => $leaveRequest->company_id,
                        'status' => 'on_leave',
                        'attendance_type' => 'regular',
                        'leave_request_id' => $leaveRequest->getKey(),
                        'notes' => 'On leave: '.$leaveRequest->leaveType->getTranslation('name', app()->getLocale()),
                    ]
                );
            }
            $currentDate->addDay();
        }
    }

    /**
     * Remove attendance records for cancelled leave.
     */
    private function removeLeaveAttendanceRecords(LeaveRequest $leaveRequest): void
    {
        /** @var \App\Models\Employee $employee */
        $employee = $leaveRequest->employee;
        $employee->attendances()
            ->where('leave_request_id', $leaveRequest->getKey())
            ->delete();
    }

    /**
     * Calculate carried forward days from previous year.
     */
    private function calculateCarriedForwardDays(Employee $employee, LeaveType $leaveType, int $previousYear): int
    {
        $previousYearBalance = $this->getLeaveBalance($employee, $leaveType, $previousYear);
        $unusedDays = $previousYearBalance['remaining_days'];

        return min($unusedDays, $leaveType->max_carry_forward_days);
    }
}
