<?php

namespace Kezi\Pos\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;

class PosTerminalController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('pos::terminal');
    }
}
