<?php

namespace App\Services\Loans;

class EIRSolverService
{
    /**
     * Solve per-period IRR for a series of cashflows at period ends using bisection.
     * Returns rate r such that NPV = 0.
     * @param array<int,float> $cashflows
     */
    public function solvePeriodicIRR(array $cashflows, float $low = 0.0, float $high = 1.0, float $tol = 1e-8, int $maxIter = 200): float
    {
        $npv = function (float $r) use ($cashflows): float {
            $sum = 0.0;
            foreach ($cashflows as $t => $cf) {
                $sum += $cf / pow(1 + $r, $t);
            }
            return $sum;
        };

        $fLow = $npv($low);
        $fHigh = $npv($high);
        if ($fLow * $fHigh > 0) {
            // Expand the high bound until sign change or give up
            for ($h = $high; $h <= 5.0 && $fLow * $npv($h) > 0; $h += 0.5) {
                $high = $h;
                $fHigh = $npv($high);
            }
            if ($fLow * $fHigh > 0) {
                return 0.0; // cannot bracket; degenerate cashflows
            }
        }

        for ($i = 0; $i < $maxIter; $i++) {
            $mid = ($low + $high) / 2.0;
            $fMid = $npv($mid);
            if (abs($fMid) < $tol || ($high - $low) < $tol) {
                return $mid;
            }
            if ($fLow * $fMid < 0) {
                $high = $mid; $fHigh = $fMid;
            } else {
                $low = $mid; $fLow = $fMid;
            }
        }
        return ($low + $high) / 2.0;
    }
}

