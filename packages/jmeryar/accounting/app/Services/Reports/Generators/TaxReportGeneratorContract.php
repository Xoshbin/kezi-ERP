<?php

namespace Jmeryar\Accounting\Services\Reports\Generators;

use App\Models\Company;
use Carbon\Carbon;

interface TaxReportGeneratorContract
{
    /**
     * Generate the specific tax report data.
     *
     * @return array<string, mixed>
     */
    public function generate(Company $company, Carbon $startDate, Carbon $endDate): array;
}
