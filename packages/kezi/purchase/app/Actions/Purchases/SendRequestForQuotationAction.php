<?php

namespace Kezi\Purchase\Actions\Purchases;

use Illuminate\Support\Facades\DB;
use Kezi\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Kezi\Purchase\Models\RequestForQuotation;

class SendRequestForQuotationAction
{
    public function execute(RequestForQuotation $rfq): RequestForQuotation
    {
        return DB::transaction(function () use ($rfq) {
            $rfq->update([
                'status' => RequestForQuotationStatus::Sent,
            ]);

            // Dispatch event to trigger email/notification logic
            \Kezi\Purchase\Events\RequestForQuotationSent::dispatch($rfq);

            return $rfq;
        });
    }
}
