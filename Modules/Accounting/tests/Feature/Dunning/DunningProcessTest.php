<?php

namespace Modules\Accounting\Tests\Feature\Dunning;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Modules\Accounting\Actions\Dunning\ProcessDunningRunAction;
use Modules\Accounting\Emails\DunningReminderMail;
use Modules\Accounting\Models\DunningLevel;
use Modules\Foundation\Models\Currency;
use Modules\Foundation\Models\Partner;
use Modules\Sales\Enums\Sales\InvoiceStatus;
use Modules\Sales\Models\Invoice;
use Tests\TestCase;

class DunningProcessTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected User $user;

    protected Currency $currency;

    protected Partner $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create();
        $this->user->companies()->attach($this->company);
        $this->currency = Currency::where('code', 'USD')->first() ?? Currency::factory()->create(['code' => 'USD']);
        $this->customer = Partner::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'customer@example.com',
            'type' => \Modules\Foundation\Enums\Partners\PartnerType::Customer,
        ]);

        Carbon::setTestNow('2026-01-12 10:00:00');
    }

    public function test_it_processes_dunning_for_overdue_invoices()
    {
        Mail::fake();

        // 1. Create Dunning Levels
        $level1 = DunningLevel::create([
            'company_id' => $this->company->id,
            'name' => 'Reminder 1',
            'days_overdue' => 5,
            'email_subject' => 'URGENT: Payment Reminder',
            'send_email' => true,
        ]);

        $level2 = DunningLevel::create([
            'company_id' => $this->company->id,
            'name' => 'Reminder 2',
            'days_overdue' => 15,
            'email_subject' => 'FINAL NOTICE',
            'send_email' => true,
        ]);

        // 2. Create Invoices
        // Invoice 1: 6 days overdue (should trigger Level 1)
        $invoice1 = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'due_date' => Carbon::today()->subDays(6),
            'status' => InvoiceStatus::Posted,
            'currency_id' => $this->currency->id,
            'total_amount' => 1000,
        ]);

        // Invoice 2: 1 day overdue (should NOT trigger any level)
        $invoice2 = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'due_date' => Carbon::today()->subDays(1),
            'status' => InvoiceStatus::Posted,
            'currency_id' => $this->currency->id,
            'total_amount' => 500,
        ]);

        // Invoice 3: 16 days overdue (should trigger Level 2)
        $invoice3 = Invoice::factory()->create([
            'company_id' => $this->company->id,
            'customer_id' => $this->customer->id,
            'due_date' => Carbon::today()->subDays(16),
            'status' => InvoiceStatus::Posted,
            'currency_id' => $this->currency->id,
            'total_amount' => 2000,
        ]);

        // 3. Run Dunning Run
        app(ProcessDunningRunAction::class)->execute($this->company->id);

        // 4. Assertions
        $invoice1->refresh();
        $invoice2->refresh();
        $invoice3->refresh();

        $this->assertEquals($level1->id, $invoice1->dunning_level_id);
        $this->assertNotNull($invoice1->last_dunning_date);

        $this->assertNull($invoice2->dunning_level_id);

        $this->assertEquals($level2->id, $invoice3->dunning_level_id);
        $this->assertNotNull($invoice3->last_dunning_date);

        Mail::assertSent(DunningReminderMail::class, function ($mail) use ($invoice1, $level1) {
            return $mail->invoice->id === $invoice1->id && $mail->dunningLevel->id === $level1->id;
        });

        Mail::assertSent(DunningReminderMail::class, function ($mail) use ($invoice3, $level2) {
            return $mail->invoice->id === $invoice3->id && $mail->dunningLevel->id === $level2->id;
        });

        Mail::assertSent(DunningReminderMail::class, 2);
    }
}
