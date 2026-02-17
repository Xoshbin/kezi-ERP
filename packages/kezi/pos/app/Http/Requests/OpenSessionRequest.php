<?php

namespace Kezi\Pos\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Kezi\Pos\Models\PosProfile;

class OpenSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'pos_profile_id' => [
                'required',
                'exists:pos_profiles,id',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    /** @var PosProfile|null $profile */
                    $profile = PosProfile::find($value);
                    if (! $profile) {
                        return;
                    }

                    /** @var \App\Models\User $user */
                    $user = $this->user();

                    // Company scoping: profile must belong to one of user's companies
                    $userCompanies = $user->companies()->pluck('companies.id')->toArray();
                    if (! in_array($profile->company_id, $userCompanies)) {
                        $fail('You do not have access to this POS profile.');
                    }

                    // Active check
                    if (! $profile->is_active) {
                        $fail('This POS profile is inactive.');
                    }
                },
            ],
            'opening_cash' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pos_profile_id.required' => 'A POS profile is required to open a session.',
            'pos_profile_id.exists' => 'The selected POS profile is invalid.',
            'opening_cash.required' => 'Please enter the opening cash amount.',
            'opening_cash.numeric' => 'The opening cash must be a number.',
            'opening_cash.min' => 'The opening cash cannot be negative.',
        ];
    }
}
