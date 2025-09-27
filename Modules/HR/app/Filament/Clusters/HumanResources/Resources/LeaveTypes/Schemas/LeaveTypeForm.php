<?php

namespace App\Filament\Clusters\HumanResources\Resources\LeaveTypes\Schemas;

use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class LeaveTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('leave_type.basic_information'))
                ->description(__('leave_type.basic_information_description'))
                ->schema([
                    TextInput::make('name')
                        ->label(__('leave_type.name'))
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(2),

                    TextInput::make('code')
                        ->label(__('leave_type.code'))
                        ->required()
                        ->maxLength(10)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(1),

                    Textarea::make('description')
                        ->label(__('leave_type.description'))
                        ->maxLength(1000)
                        ->rows(3)
                        ->columnSpanFull(),

                    ColorPicker::make('color')
                        ->label(__('leave_type.color'))
                        ->default('#3B82F6')
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('leave_type.allocation_rules'))
                ->description(__('leave_type.allocation_rules_description'))
                ->schema([
                    TextInput::make('default_days_per_year')
                        ->label(__('leave_type.default_days_per_year'))
                        ->required()
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->columnSpan(1),

                    Toggle::make('carries_forward')
                        ->label(__('leave_type.carries_forward'))
                        ->default(false)
                        ->live()
                        ->columnSpan(1),

                    TextInput::make('max_carry_forward_days')
                        ->label(__('leave_type.max_carry_forward_days'))
                        ->numeric()
                        ->default(0)
                        ->minValue(0)
                        ->visible(fn ($get) => $get('carries_forward'))
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('leave_type.approval_rules'))
                ->description(__('leave_type.approval_rules_description'))
                ->schema([
                    Toggle::make('requires_approval')
                        ->label(__('leave_type.requires_approval'))
                        ->default(true)
                        ->columnSpan(1),

                    Toggle::make('requires_documentation')
                        ->label(__('leave_type.requires_documentation'))
                        ->default(false)
                        ->columnSpan(1),

                    TextInput::make('min_notice_days')
                        ->label(__('leave_type.min_notice_days'))
                        ->required()
                        ->numeric()
                        ->default(1)
                        ->minValue(0)
                        ->columnSpan(1),

                    TextInput::make('max_consecutive_days')
                        ->label(__('leave_type.max_consecutive_days'))
                        ->numeric()
                        ->minValue(1)
                        ->columnSpan(1),
                ])
                ->columns(4)
                ->columnSpanFull(),

            Section::make(__('leave_type.payment_status'))
                ->description(__('leave_type.payment_status_description'))
                ->schema([
                    Toggle::make('is_paid')
                        ->label(__('leave_type.is_paid'))
                        ->default(true)
                        ->columnSpan(1),

                    Toggle::make('is_active')
                        ->label(__('leave_type.is_active'))
                        ->default(true)
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ]);
    }
}
