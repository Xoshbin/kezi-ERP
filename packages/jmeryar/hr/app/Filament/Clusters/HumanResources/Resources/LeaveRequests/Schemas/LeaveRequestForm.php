<?php

namespace Jmeryar\HR\Filament\Clusters\HumanResources\Resources\LeaveRequests\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Jmeryar\HR\Models\Employee;

class LeaveRequestForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('hr::leave_request.request_information'))
                ->description(__('hr::leave_request.request_information_description'))
                ->schema([
                    TextInput::make('request_number')
                        ->label(__('hr::leave_request.request_number'))
                        ->required()
                        ->unique(ignoreRecord: true)
                        ->maxLength(50)
                        ->default(fn () => 'LR-'.date('Ymd').'-'.str_pad((string) rand(1, 9999), 4, '0', STR_PAD_LEFT))
                        ->disabled()
                        ->dehydrated()
                        ->columnSpan(1),

                    Select::make('employee_id')
                        ->label(__('hr::leave_request.employee'))
                        ->relationship('employee', 'first_name')
                        ->searchable(['first_name', 'last_name', 'employee_number'])
                        ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->full_name.' ('.$record->employee_number.')')
                        ->required()
                        ->preload()
                        ->columnSpan(1),

                    Select::make('leave_type_id')
                        ->label(__('hr::leave_request.leave_type'))
                        ->relationship('leaveType', 'name')
                        ->required()
                        ->preload()
                        ->live()
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('hr::leave_request.leave_dates'))
                ->description(__('hr::leave_request.leave_dates_description'))
                ->schema([
                    DatePicker::make('start_date')
                        ->label(__('hr::leave_request.start_date'))
                        ->required()
                        ->minDate(now())
                        ->live()
                        ->columnSpan(1),

                    DatePicker::make('end_date')
                        ->label(__('hr::leave_request.end_date'))
                        ->required()
                        ->minDate(fn ($get) => $get('start_date') ?? now())
                        ->live()
                        ->columnSpan(1),

                    TextInput::make('days_requested')
                        ->label(__('hr::leave_request.days_requested'))
                        ->numeric()
                        ->required()
                        ->minValue(0.5)
                        ->step(0.5)
                        ->suffix(__('hr::leave_request.days'))
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('hr::leave_request.details'))
                ->description(__('hr::leave_request.details_description'))
                ->schema([
                    Textarea::make('reason')
                        ->label(__('hr::leave_request.reason'))
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label(__('hr::leave_request.notes'))
                        ->rows(3)
                        ->columnSpanFull(),

                    Select::make('delegate_employee_id')
                        ->label(__('hr::leave_request.delegate_employee'))
                        ->relationship('delegateEmployee', 'first_name')
                        ->searchable(['first_name', 'last_name', 'employee_number'])
                        ->getOptionLabelFromRecordUsing(fn (Employee $record) => $record->full_name.' ('.$record->employee_number.')')
                        ->preload()
                        ->helperText(__('hr::leave_request.delegate_employee_helper'))
                        ->columnSpan(2),

                    Textarea::make('delegation_notes')
                        ->label(__('hr::leave_request.delegation_notes'))
                        ->rows(2)
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull(),

            Section::make(__('hr::leave_request.attachments'))
                ->description(__('hr::leave_request.attachments_description'))
                ->schema([
                    FileUpload::make('attachments')
                        ->label(__('hr::leave_request.supporting_documents'))
                        ->multiple()
                        ->directory('leave-attachments')
                        ->maxSize(5120)
                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),

            Section::make(__('hr::leave_request.approval_section'))
                ->description(__('hr::leave_request.approval_section_description'))
                ->schema([
                    Select::make('status')
                        ->label(__('hr::leave_request.status'))
                        ->options([
                            'pending' => __('hr::leave_request.status_pending'),
                            'approved' => __('hr::leave_request.status_approved'),
                            'rejected' => __('hr::leave_request.status_rejected'),
                            'cancelled' => __('hr::leave_request.status_cancelled'),
                        ])
                        ->default('pending')
                        ->required()
                        ->columnSpan(1),

                    Textarea::make('approval_notes')
                        ->label(__('hr::leave_request.approval_notes'))
                        ->rows(2)
                        ->columnSpan(1),

                    Textarea::make('rejection_reason')
                        ->label(__('hr::leave_request.rejection_reason'))
                        ->rows(2)
                        ->visible(fn ($get) => $get('status') === 'rejected')
                        ->columnSpan(1),
                ])
                ->columns(3)
                ->columnSpanFull()
                ->visibleOn('edit'),
        ]);
    }
}
