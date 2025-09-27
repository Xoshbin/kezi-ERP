<?php

namespace Modules\Accounting\Services;

use App\Exceptions\DeletionNotAllowedException;
use App\Models\Account;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AccountService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): \Modules\Accounting\Models\Account
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

        return \Modules\Accounting\Models\Account::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(\Modules\Accounting\Models\Account $account, array $data): \Modules\Accounting\Models\Account
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

    public function delete(\Modules\Accounting\Models\Account $account): void
    {
        if ($account->journalEntryLines()->exists()) {
            throw new \Modules\Foundation\Exceptions\DeletionNotAllowedException('Cannot delete account with associated financial records.');
        }

        $account->delete();
    }
}
