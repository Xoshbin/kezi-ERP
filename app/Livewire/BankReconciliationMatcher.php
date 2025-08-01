<?php

namespace App\Livewire;

use Brick\Money\Money;
use App\Models\Account;
use App\Models\Payment;
use Livewire\Component;
use Filament\Actions\Action;
use App\Models\BankStatement;
use App\Models\BankStatementLine;
use Livewire\Attributes\Computed;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Actions\Contracts\HasActions;
use App\Services\BankReconciliationService;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Actions\Concerns\InteractsWithActions;

class BankReconciliationMatcher extends Component implements HasForms, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;

    public BankStatement $record;

    public array $selectedLines = [];
    public array $selectedPayments = [];

    #[Computed]
    public function bankTotal(): Money
    {
        if (empty($this->selectedLines)) {
            return Money::of(0, $this->record->currency->code);
        }

        $total = BankStatementLine::whereIn('id', $this->selectedLines)->sum('amount');
        return Money::ofMinor($total, $this->record->currency->code);
    }

    #[Computed]
    public function paymentTotal(): Money
    {
        if (empty($this->selectedPayments)) {
            return Money::of(0, $this->record->currency->code);
        }

        $payments = Payment::find($this->selectedPayments);
        $total = Money::of(0, $this->record->currency->code);

        foreach ($payments as $payment) {
            // To balance against bank statement lines, inbound payments are treated
            // as negative and outbound as positive in this calculation.
            if ($payment->payment_type === 'inbound') {
                $total = $total->minus($payment->amount);
            } else { // outbound
                $total = $total->plus($payment->amount);
            }
        }
        return $total;
    }

    #[Computed]
    public function difference(): Money
    {
        return $this->bankTotal()->plus($this->paymentTotal());
    }

    public function reconcile(): void
    {
        // Ensure there's something to reconcile and the totals match
        if ($this->difference()->isZero() === false || (count($this->selectedLines) === 0 && count($this->selectedPayments) === 0)) {
            return;
        }

        try {
            // Instantiate and execute the service
            $service = new BankReconciliationService();
            $service->reconcile($this->selectedLines, $this->selectedPayments, Auth::user());

            // Send a success notification
            Notification::make()
                ->title('Reconciliation Successful')
                ->body('The selected items have been reconciled.')
                ->success()
                ->send();

            // Reset the selections to clear the UI
            $this->reset('selectedLines', 'selectedPayments');
        } catch (\Exception $e) {
            // Send a failure notification
            Notification::make()
                ->title('Reconciliation Failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    // Action method to define the modal
    public function writeOff(): Action
    {
        return Action::make('writeOff')
            ->label('Create Write-Off')
            ->modalHeading('Create Write-Off Entry')
            ->modalWidth('xl')
            // The action() method handles the submission
            ->action(function (array $data, array $arguments, BankReconciliationService $service) {
                $line = BankStatementLine::find($arguments['lineId']);
                $writeOffAccount = Account::find($data['write_off_account_id']);

                if (!$line || !$writeOffAccount) {
                    Notification::make()->title('Error')->body('Invalid data provided.')->danger()->send();
                    return;
                }

                try {
                    $service->createWriteOff($line, $writeOffAccount, Auth::user(), $data['description']);

                    Notification::make()
                        ->title('Write-off created successfully')
                        ->success()
                        ->send();

                    // Refresh the component to show the line is gone
                    $this->dispatch('$refresh');
                } catch (\Exception $e) {
                    Notification::make()
                        ->title('Failed to create write-off')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            })
            // form() defines the fields inside the modal
            ->form(function (array $arguments) {
                // We can use the arguments to pre-fill data
                $line = BankStatementLine::find($arguments['lineId']);

                return [
                    Select::make('write_off_account_id')
                        ->label('Write-Off Account')
                        ->searchable()
                        ->options(function () {
                            // Only allow selection of Income or Expense accounts
                            return Account::query()
                                ->where('company_id', auth()->user()->company_id)
                                ->whereIn('type', [Account::TYPE_INCOME, Account::TYPE_EXPENSE]) // Corrected line
                                ->where('is_deprecated', false)
                                ->pluck('name', 'id');
                        })
                        ->required(),
                    Textarea::make('description')
                        ->label('Description')
                        ->required()
                        ->default('Write-Off for: ' . $line?->description),
                ];
            });
    }

    public function render()
    {
        $statementLines = $this->record->bankStatementLines()
            ->where('is_reconciled', false)
            ->with('partner') // Eager load partner for display
            ->get();

        $payments = Payment::where('status', Payment::STATUS_CONFIRMED)
            ->where('journal_id', $this->record->journal_id)
            ->with('partner') // Eager load partner for display
            ->get();

        return view('livewire.bank-reconciliation-matcher', [
            'statementLines' => $statementLines,
            'payments' => $payments,
        ]);
    }
}
