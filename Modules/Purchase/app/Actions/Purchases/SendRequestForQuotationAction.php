<?php

namespace Modules\Purchase\Actions\Purchases;

use Illuminate\Support\Facades\DB;
use Modules\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Modules\Purchase\Models\RequestForQuotation;

class SendRequestForQuotationAction
{
    public function execute(RequestForQuotation $rfq): RequestForQuotation
    {
        return DB::transaction(function () use ($rfq) {
            $rfq->update([
                'status' => RequestForQuotationStatus::Sent,
            ]);

            // Dispatch event to trigger email/notification logic
            \Modules\Purchase\Events\RequestForQuotationSent::dispatch($rfq);

            return $rfq;
        });
    }
}
