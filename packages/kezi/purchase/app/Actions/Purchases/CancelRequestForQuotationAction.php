<?php

namespace Kezi\Purchase\Actions\Purchases;

use Illuminate\Support\Facades\DB;
use Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Kezi\Purchase\Models\RequestForQuotation;

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
