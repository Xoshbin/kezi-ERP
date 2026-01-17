<?php

namespace Modules\Accounting\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Accounting\Models\Account;

class AccountService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Account
    {
        Validator::make($data, [
            'code' => [
                'required',
                Rule::unique('accounts')->where('company_id', $data['company_id'] ?? null),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'exists:companies,id'],
        ])->validate();

        return Account::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Account $account, array $data): Account
    {
        Validator::make($data, [
            'code' => [
                'required',
                Rule::unique('accounts')->where('company_id', $data['company_id'])->ignore($account->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'exists:companies,id'],
        ])->validate();

        $account->update($data);

        return $account;
    }

    public function delete(Account $account): void
    {
        if ($account->journalEntryLines()->exists()) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException('Cannot delete account with associated financial records.');
        }

        $account->delete();
    }
}
