<?php

namespace Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Support\Enums\Width;
use Kezi\QualityControl\Actions\RecordQualityCheckResultAction;
use Kezi\QualityControl\DataTransferObjects\RecordQualityCheckResultDTO;
use Kezi\QualityControl\Enums\QualityCheckStatus;
use Kezi\QualityControl\Enums\QualityCheckType;
use Kezi\QualityControl\Filament\Clusters\QualityControl\Resources\QualityCheckResource;
use Kezi\QualityControl\Models\QualityCheck;

/**
 * @extends ViewRecord<QualityCheck>
 */
class ViewQualityCheck extends ViewRecord
{
    protected static string $resource = QualityCheckResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('record_results')
                ->label(__('qualitycontrol::check.record_results'))
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (QualityCheck $record): bool => $record->status === QualityCheckStatus::Draft)
                ->modalHeading(__('qualitycontrol::check.record_results_modal_heading'))
                ->modalWidth(Width::TwoExtraLarge)
                ->form(function (QualityCheck $record): array {
                    $components = [];

                    foreach ($record->lines()->with('parameter')->get() as $line) {
                        $parameter = $line->parameter;
                        $field = null;

                        switch ($parameter->check_type) {
                            case QualityCheckType::PassFail:
                                $field = ToggleButtons::make("results.{$line->parameter_id}.result_pass_fail")
                                    ->label($parameter->name)
                                    ->boolean()
                                    ->options([
                                        true => __('qualitycontrol::check.pass'),
                                        false => __('qualitycontrol::check.fail'),
                                    ])
                                    ->grouped()
                                    ->required();
                                break;

                            case QualityCheckType::Measure:
                                $label = $parameter->name;
                                if ($parameter->unit_of_measure) {
                                    $label .= " ({$parameter->unit_of_measure})";
                                }
                                if ($parameter->min_value !== null || $parameter->max_value !== null) {
                                    $label .= " [{$parameter->min_value} - {$parameter->max_value}]";
                                }

                                $field = TextInput::make("results.{$line->parameter_id}.result_numeric")
                                    ->label($label)
                                    ->numeric()
                                    ->required();
                                break;

                            case QualityCheckType::TextInput:
                                $field = TextInput::make("results.{$line->parameter_id}.result_text")
                                    ->label($parameter->name)
                                    ->required();
                                break;

                            case QualityCheckType::TakePhoto:
                                $field = FileUpload::make("results.{$line->parameter_id}.result_image_path")
                                    ->label($parameter->name)
                                    ->image()
                                    ->required();
                                break;

                            case QualityCheckType::Instructions:
                                $field = Placeholder::make("results.{$line->parameter_id}.instructions")
                                    ->label($parameter->name)
                                    ->content($parameter->notes);
                                break;
                        }

                        if ($field) {
                            $components[] = Section::make()
                                ->schema([
                                    $field,
                                    Textarea::make("results.{$line->parameter_id}.notes")
                                        ->label(__('qualitycontrol::check.line_notes'))
                                        ->rows(2),
                                ])
                                ->compact();
                        }
                    }

                    $components[] = Textarea::make('notes')
                        ->label(__('qualitycontrol::check.notes'))
                        ->rows(3);

                    return $components;
                })
                ->action(function (array $data, QualityCheck $record): void {
                    $lineResults = [];
                    foreach ($data['results'] ?? [] as $parameterId => $result) {
                        $lineResults[] = array_merge(['parameter_id' => $parameterId], $result);
                    }

                    $dto = new RecordQualityCheckResultDTO(
                        qualityCheckId: $record->id,
                        inspectedByUserId: auth()->id(),
                        lineResults: $lineResults,
                        notes: $data['notes'] ?? null,
                    );

                    app(RecordQualityCheckResultAction::class)->execute($dto);

                    Notification::make()
                        ->success()
                        ->title(__('qualitycontrol::check.record_results_success'))
                        ->send();
                }),
        ];
    }
}
