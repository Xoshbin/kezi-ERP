<?php

namespace Kezi\Pos\Actions;

use Illuminate\Support\Facades\DB;
use Kezi\Pos\DataTransferObjects\CreatePosReturnDTO;
use Kezi\Pos\Enums\PosReturnStatus;
use Kezi\Pos\Models\PosReturn;
use Kezi\Pos\Models\PosReturnLine;
use Ramsey\Uuid\Uuid;

class CreatePosReturnAction
{
    public function execute(CreatePosReturnDTO $dto): PosReturn
    {
        return DB::transaction(function () use ($dto) {
            // Generate return number
            $returnNumber = $this->generateReturnNumber($dto->company_id);

            // Calculate totals
            $refundAmount = 0;
            $restockingFee = 0;

            foreach ($dto->lines as $lineDTO) {
                $refundAmount += $lineDTO->refund_amount;
                $restockingFee += $lineDTO->restocking_fee_line;
            }

            // Create return header
            $return = PosReturn::create([
                'uuid' => Uuid::uuid4()->toString(),
                'company_id' => $dto->company_id,
                'pos_session_id' => $dto->pos_session_id,
                'original_order_id' => $dto->original_order_id,
                'currency_id' => $dto->currency_id,
                'return_number' => $returnNumber,
                'return_date' => $dto->return_date,
                'status' => PosReturnStatus::Draft,
                'return_reason' => $dto->return_reason,
                'return_notes' => $dto->return_notes,
                'requested_by_user_id' => $dto->requested_by_user_id,
                'refund_amount' => $refundAmount,
                'restocking_fee' => $restockingFee,
                'refund_method' => $dto->refund_method,
            ]);

            // Create return lines
            foreach ($dto->lines as $lineDTO) {
                PosReturnLine::create([
                    'pos_return_id' => $return->id,
                    'original_order_line_id' => $lineDTO->original_order_line_id,
                    'product_id' => $lineDTO->product_id,
                    'quantity_returned' => $lineDTO->quantity_returned,
                    'quantity_available' => $lineDTO->quantity_available,
                    'unit_price' => $lineDTO->unit_price,
                    'refund_amount' => $lineDTO->refund_amount,
                    'restocking_fee_line' => $lineDTO->restocking_fee_line,
                    'restock' => $lineDTO->restock,
                    'item_condition' => $lineDTO->item_condition,
                    'return_reason_line' => $lineDTO->return_reason_line,
                    'metadata' => $lineDTO->metadata,
                ]);
            }

            return $return->load('lines.product');
        });
    }

    protected function generateReturnNumber(int $companyId): string
    {
        $lastReturn = PosReturn::where('company_id', $companyId)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastReturn ? ((int) substr($lastReturn->return_number, -6)) + 1 : 1;

        return 'RET-'.str_pad($sequence, 6, '0', STR_PAD_LEFT);
    }
}
