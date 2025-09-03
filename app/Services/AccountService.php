<?php

namespace App\Services;

use App\Exceptions\DeletionNotAllowedException;
use App\Models\Account;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountService
{
    public function create(array $data): Account
    {
        Validator::make($data, [
            'code' => [
                'required',
                Rule::unique('accounts')->where('company_id', $data['company_id']),
            ],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:255'],
            'company_id' => ['required', 'exists:companies,id'],
        ])->validate();

        return Account::create($data);
    }

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
            throw new DeletionNotAllowedException('Cannot delete account with associated financial records.');
        }

        $account->delete();
    }
}
