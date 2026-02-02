<?php

namespace Tests\Support;

use App\Models\Company;
use Filament\Facades\Filament;
use Illuminate\Support\Arr;
use Kezi\Foundation\Enums\Partners\PartnerType;

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

    public static function partner(array $overrides = []): \Kezi\Foundation\Models\Partner
    {
        $defaults = [
            'company_id' => self::companyId(),
            'name' => 'Test Partner',
            'type' => PartnerType::Vendor,
            'email' => 'ap@test.local',
        ];

        return \Kezi\Foundation\Models\Partner::factory()->create(array_merge($defaults, $overrides));
    }

    public static function currency(array $overrides = []): \Kezi\Foundation\Models\Currency
    {
        $defaults = [
            'code' => 'EUR',
            'name' => ['en' => 'Euro'],
            'symbol' => '€',
        ];

        return \Kezi\Foundation\Models\Currency::query()->firstOrCreate(
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

    public static function account(array $overrides = []): \Kezi\Accounting\Models\Account
    {
        $company = $overrides['company'] ?? auth()->user()?->companies()->first();

        $defaults = [
            'code' => '100100',
            'name' => 'Main Bank',
            'type' => \Kezi\Accounting\Enums\Accounting\AccountType::BankAndCash,
        ];

        $data = array_merge($defaults, Arr::except($overrides, ['company']));

        return \Kezi\Accounting\Models\Account::factory()->for($company)->create($data);
    }
}
