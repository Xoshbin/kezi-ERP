<?php

namespace Modules\Payment\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Payment\Models\Cheque;

class ChequeController extends Controller
{
    public function print(Cheque $cheque)
    {
        // Ensure only payable cheques can be printed
        if ($cheque->type !== \Modules\Payment\Enums\Cheques\ChequeType::Payable) {
            abort(403, 'Only payable cheques can be printed.');
        }

        return view('payment::cheques.print', compact('cheque'));
    }
}
