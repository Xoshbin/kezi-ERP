<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Filament\Resources\AuditLogResource\RelationManagers;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label(__('audit_log.user_id'))
                    ->relationship('user', 'name')
                    ->required(),
                Forms\Components\TextInput::make('event_type')
                    ->label(__('audit_log.event_type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('auditable_type')
                    ->label(__('audit_log.auditable_type'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('auditable_id')
                    ->label(__('audit_log.auditable_id'))
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('old_values')
                    ->label(__('audit_log.old_values')),
                Forms\Components\TextInput::make('new_values')
                    ->label(__('audit_log.new_values')),
                Forms\Components\Textarea::make('description')
                    ->label(__('audit_log.description'))
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('ip_address')
                    ->label(__('audit_log.ip_address'))
                    ->maxLength(45),
                Forms\Components\Textarea::make('user_agent')
                    ->label(__('audit_log.user_agent'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('audit_log.user_name'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('event_type')
                    ->label(__('audit_log.event_type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label(__('audit_log.auditable_type'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('auditable_id')
                    ->label(__('audit_log.auditable_id'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('audit_log.ip_address'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('audit_log.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListAuditLogs::route('/'),
            'create' => Pages\CreateAuditLog::route('/create'),
            'edit' => Pages\EditAuditLog::route('/{record}/edit'),
        ];
    }
}
