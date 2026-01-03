<?php

namespace Modules\Purchase\Actions\Purchases;

use Illuminate\Support\Facades\DB;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Modules\Purchase\Models\RequestForQuotation;

class CancelRequestForQuotationAction
{
    public function execute(RequestForQuotation $rfq): RequestForQuotation
    {
        return DB::transaction(function () use ($rfq) {
            $rfq->update([
                'status' => RequestForQuotationStatus::Cancelled,
            ]);

            return $rfq;
        });
    }
}
