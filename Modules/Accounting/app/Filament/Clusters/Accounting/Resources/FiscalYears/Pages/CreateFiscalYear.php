<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\Pages;

use Filament\Resources\Pages\CreateRecord;
use Modules\Accounting\Actions\Accounting\CreateFiscalYearAction;
use Modules\Accounting\DataTransferObjects\Accounting\CreateFiscalYearDTO;
use Modules\Accounting\Filament\Clusters\Accounting\Resources\FiscalYears\FiscalYearResource;

class CreateFiscalYear extends CreateRecord
{
    protected static string $resource = FiscalYearResource::class;

    /**
     * Handle the record creation using the Action pattern.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $dto = new CreateFiscalYearDTO(
            companyId: filament()->getTenant()->id,
            name: $data['name'],
            startDate: \Carbon\Carbon::parse($data['start_date']),
            endDate: \Carbon\Carbon::parse($data['end_date']),
            generatePeriods: $data['generate_periods'] ?? false,
        );

        return app(CreateFiscalYearAction::class)->execute($dto);
    }
}
