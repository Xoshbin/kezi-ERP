<?php

use Modules\Accounting\Services\Loans\EIRSolverService;


it('solves IRR for simple cashflows', function () {
    /** @var EIRSolverService $svc */
    $svc = app(EIRSolverService::class);

    // Cashflows at period ends: t0 outflow -1000, then 12 inflows of 90
    $cashflows = [-1000.0];
    for ($i = 0; $i < 12; $i++) {
        $cashflows[] = 90.0;
    }

    $irr = $svc->solvePeriodicIRR($cashflows);

    // Expected IRR around 1.2043% per period (~15.5% annual nominal) for this example
    expect($irr)->toBeFloat()->toBeGreaterThan(0)->toBeLessThan(0.02);
    expect(round($irr, 4))->toBe(0.0120);
});
