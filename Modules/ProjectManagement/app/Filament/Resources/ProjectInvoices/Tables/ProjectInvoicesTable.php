<?php

namespace Modules\ProjectManagement\Filament\Resources\ProjectInvoices\Tables;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Modules\ProjectManagement\Services\ProjectInvoicingService;

class ProjectInvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('project.name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                TextColumn::make('invoice_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('period_start')
                    ->date()
                    ->label('Period')
                    ->formatStateUsing(fn ($record) => "{$record->period_start->format('M d')} - {$record->period_end->format('M d, Y')}"),
                TextColumn::make('total_amount')
                    ->money(fn ($record) => $record->project->company->currency_code ?? 'USD')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'invoiced' => 'success',
                        'cancelled' => 'danger',
                    }),
                TextColumn::make('invoice.document_number')
                    ->label('Invoice #')
                    ->searchable()
                    ->url(fn ($record) => $record->invoice_id ? route('filament.app.resources.invoices.view', $record->invoice_id) : null),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('generate_invoice')
                    ->label('Generate Invoice')
                    ->icon('heroicon-o-document-text')
                    ->color(Color::Green)
                    ->visible(fn ($record) => $record->status === 'draft' && ! $record->invoice_id)
                    ->requiresConfirmation()
                    ->action(function ($record, ProjectInvoicingService $service) {
                        $service->createCustomerInvoice($record);
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make(),
                ]),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
