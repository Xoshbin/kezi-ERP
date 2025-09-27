<?php

namespace Modules\Accounting\Filament\Clusters\Accounting\Resources\AuditLogs;

use App\Filament\Clusters\Accounting\AccountingCluster;
use App\Filament\Clusters\Accounting\Resources\AuditLogs\Pages\CreateAuditLog;
use App\Filament\Clusters\Accounting\Resources\AuditLogs\Pages\EditAuditLog;
use App\Filament\Clusters\Accounting\Resources\AuditLogs\Pages\ListAuditLogs;
use App\Models\AuditLog;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-eye';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = AccountingCluster::class;

    public static function getNavigationGroup(): ?string
    {
        return __('navigation.groups.administration');
    }

    public static function getModelLabel(): string
    {
        return __('audit_log.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('audit_log.plural_label');
    }

    public static function getNavigationLabel(): string
    {
        return __('audit_log.plural_label');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label(__('audit_log.user_id'))
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('event_type')
                    ->label(__('audit_log.event_type'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('auditable_type')
                    ->label(__('audit_log.auditable_type'))
                    ->required()
                    ->maxLength(255),
                TextInput::make('auditable_id')
                    ->label(__('audit_log.auditable_id'))
                    ->required()
                    ->numeric(),
                TextInput::make('old_values')
                    ->label(__('audit_log.old_values')),
                TextInput::make('new_values')
                    ->label(__('audit_log.new_values')),
                Textarea::make('description')
                    ->label(__('audit_log.description'))
                    ->columnSpanFull(),
                TextInput::make('ip_address')
                    ->label(__('audit_log.ip_address'))
                    ->maxLength(45),
                Textarea::make('user_agent')
                    ->label(__('audit_log.user_agent'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('audit_log.user_name'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label(__('audit_log.event_type'))
                    ->searchable(),
                TextColumn::make('auditable_type')
                    ->label(__('audit_log.auditable_type'))
                    ->searchable(),
                TextColumn::make('auditable_id')
                    ->label(__('audit_log.auditable_id'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label(__('audit_log.ip_address'))
                    ->searchable(),
                TextColumn::make('created_at')
                    ->label(__('audit_log.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditLogs::route('/'),
            'create' => CreateAuditLog::route('/create'),
            'edit' => EditAuditLog::route('/{record}/edit'),
        ];
    }
}
