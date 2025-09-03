<?php

namespace App\Actions\HumanResources;

use App\DataTransferObjects\HumanResources\CreateLeaveRequestDTO;
use App\Models\LeaveRequest;
use Illuminate\Support\Facades\DB;

class CreateLeaveRequestAction
{
    public function execute(CreateLeaveRequestDTO $createLeaveRequestDTO): LeaveRequest
    {
        return DB::transaction(function () use ($createLeaveRequestDTO) {
            // Generate request number if not provided
            $requestNumber = $createLeaveRequestDTO->request_number;
            if (empty($requestNumber)) {
                $requestNumber = $this->generateRequestNumber($createLeaveRequestDTO->company_id);
            }

            $leaveRequest = LeaveRequest::create([
                'company_id' => $createLeaveRequestDTO->company_id,
                'employee_id' => $createLeaveRequestDTO->employee_id,
                'leave_type_id' => $createLeaveRequestDTO->leave_type_id,
                'request_number' => $requestNumber,
                'start_date' => $createLeaveRequestDTO->start_date,
                'end_date' => $createLeaveRequestDTO->end_date,
                'days_requested' => $createLeaveRequestDTO->days_requested,
                'reason' => $createLeaveRequestDTO->reason,
                'notes' => $createLeaveRequestDTO->notes,
                'delegate_employee_id' => $createLeaveRequestDTO->delegate_employee_id,
                'delegation_notes' => $createLeaveRequestDTO->delegation_notes,
                'attachments' => $createLeaveRequestDTO->attachments,
                'requested_by_user_id' => $createLeaveRequestDTO->requested_by_user_id,
                'submitted_at' => now(),
                'status' => 'pending',
            ]);

            return $leaveRequest->fresh();
        });
    }

    private function generateRequestNumber(int $companyId): string
    {
        $prefix = 'LR';
        $year = now()->year;

        // Get the next sequential number for this year
        $lastRequest = LeaveRequest::where('company_id', $companyId)
            ->where('request_number', 'like', $prefix.$year.'%')
            ->orderBy('request_number', 'desc')
            ->first();

        if ($lastRequest) {
            $lastNumber = (int) substr($lastRequest->request_number, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return $prefix.$year.str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
