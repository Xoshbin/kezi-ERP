<?php

namespace Tests\Support;

use App\Enums\Accounting\AccountType;
use App\Enums\Accounting\JournalType;
use App\Models\Account;
use App\Models\Company;
use App\Models\Currency;
use App\Models\Journal;
use App\Models\Partner;
use Filament\Facades\Filament;
use Illuminate\Support\Arr;

class FilamentInlineCreate
{
    private static function companyId(): int
    {
        $tenant = Filament::getTenant();
        if ($tenant instanceof Company) {
            return $tenant->getKey();
        }

        return auth()->user()?->companies()->firstOrFail()->getKey();
    }

    public static function partner(array $overrides = []): Partner
    {
        $defaults = [
            'company_id' => self::companyId(),
            'name' => 'Test Partner',
            'type' => \App\Enums\Partners\PartnerType::Vendor,
            'email' => 'ap@test.local',
        ];

        return Partner::factory()->create(array_merge($defaults, $overrides));
    }

    public static function currency(array $overrides = []): Currency
    {
        $defaults = [
            'code' => 'EUR',
            'name' => ['en' => 'Euro'],
            'symbol' => '€',
        ];

        return Currency::query()->firstOrCreate(
            ['code' => Arr::get($overrides, 'code', $defaults['code'])],
            array_merge($defaults, $overrides)
        );
    }

    public static function journal(array $overrides = []): Journal
    {
        $company = $overrides['company'] ?? auth()->user()?->companies()->first();

        $defaults = [
            'type' => JournalType::Bank,
            'name' => 'Bank Journal',
            'short_code' => 'BNK',
        ];

        $data = array_merge($defaults, Arr::except($overrides, ['company']));

        return Journal::factory()->for($company)->create($data);
    }

    public static function account(array $overrides = []): Account
    {
        $company = $overrides['company'] ?? auth()->user()?->companies()->first();

        $defaults = [
            'code' => '100100',
            'name' => 'Main Bank',
            'type' => AccountType::BankAndCash,
        ];

        $data = array_merge($defaults, Arr::except($overrides, ['company']));

        return Account::factory()->for($company)->create($data);
    }
}
