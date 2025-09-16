<?php

namespace App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\Tables;

use App\Filament\Clusters\Settings\Resources\CustomFieldDefinitions\CustomFieldDefinitionResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CustomFieldDefinitionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('custom_fields.fields.name'))
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),

                TextColumn::make('model_type')
                    ->label(__('custom_fields.fields.model_type'))
                    ->formatStateUsing(function (string $state): string {
                        $modelTypes = CustomFieldDefinitionResource::getAvailableModelTypes();
                        return $modelTypes[$state] ?? class_basename($state);
                    })
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('field_definitions')
                    ->label(__('custom_fields.fields.field_definitions'))
                    ->formatStateUsing(function (?array $state): string {
                        if (empty($state)) {
                            return __('custom_fields.messages.no_fields_defined');
                        }

                        $count = count($state);
                        return trans_choice('{1} :count field|[2,*] :count fields', $count, ['count' => $count]);
                    })
                    ->badge()
                    ->color(fn (?array $state): string => empty($state) ? 'gray' : 'success'),

                IconColumn::make('is_active')
                    ->label(__('custom_fields.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label(__('Created At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label(__('Updated At'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('model_type')
                    ->label(__('custom_fields.fields.model_type'))
                    ->options(CustomFieldDefinitionResource::getAvailableModelTypes())
                    ->searchable(),

                TernaryFilter::make('is_active')
                    ->label(__('custom_fields.fields.is_active'))
                    ->boolean()
                    ->trueLabel(__('Active Only'))
                    ->falseLabel(__('Inactive Only'))
                    ->native(false),

                SelectFilter::make('has_fields')
                    ->label(__('Field Status'))
                    ->options([
                        'with_fields' => __('With Fields'),
                        'without_fields' => __('Without Fields'),
                    ])
                    ->query(function ($query, array $data) {
                        if ($data['value'] === 'with_fields') {
                            return $query->whereJsonLength('field_definitions', '>', 0);
                        } elseif ($data['value'] === 'without_fields') {
                            return $query->where(function ($q) {
                                $q->whereNull('field_definitions')
                                  ->orWhereJsonLength('field_definitions', 0);
                            });
                        }

                        return $query;
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('View')),

                EditAction::make()
                    ->label(__('Edit')),

                DeleteAction::make()
                    ->label(__('Delete'))
                    ->requiresConfirmation()
                    ->modalHeading(__('Delete Custom Field Definition'))
                    ->modalDescription(__('Are you sure you want to delete this custom field definition? This will also delete all associated custom field values.'))
                    ->modalSubmitActionLabel(__('Delete')),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('Delete Selected'))
                        ->requiresConfirmation()
                        ->modalHeading(__('Delete Custom Field Definitions'))
                        ->modalDescription(__('Are you sure you want to delete the selected custom field definitions? This will also delete all associated custom field values.'))
                        ->modalSubmitActionLabel(__('Delete')),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
}
