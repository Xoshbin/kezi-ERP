<?php

namespace Jmeryar\Purchase\Actions\Purchases;

use Illuminate\Support\Facades\DB;
use Jmeryar\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Jmeryar\Purchase\Models\RequestForQuotation;

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
