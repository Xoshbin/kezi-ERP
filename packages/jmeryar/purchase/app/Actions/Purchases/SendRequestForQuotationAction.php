<?php

namespace Jmeryar\Purchase\Actions\Purchases;

use Illuminate\Support\Facades\DB;
use Jmeryar\Purchase\Enums\Purchases\RequestForQuotationStatus;
use Jmeryar\Purchase\Models\RequestForQuotation;

class SendRequestForQuotationAction
{
    public function execute(RequestForQuotation $rfq): RequestForQuotation
    {
        return DB::transaction(function () use ($rfq) {
            $rfq->update([
                'status' => RequestForQuotationStatus::Sent,
            ]);

            // Dispatch event to trigger email/notification logic
            \Jmeryar\Purchase\Events\RequestForQuotationSent::dispatch($rfq);

            return $rfq;
        });
    }
}
