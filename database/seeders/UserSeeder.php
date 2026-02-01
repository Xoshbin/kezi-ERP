<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Exception;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Find the company 'Kezi Solutions'
        $company = Company::where('name', 'Kezi Solutions')->first();

        // If the company is not found, throw an exception.
        if (! $company) {
            throw new Exception('Company "Kezi Solutions" not found. Please run the CompanySeeder first.');
        }

        // Create the admin user
        $user = User::updateOrCreate(
            ['email' => 'admin@kezi.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
            ]
        );

        // Attach the user to the company if not already attached
        if (! $user->companies()->where('company_id', $company->id)->exists()) {
            $user->companies()->attach($company);
        }
    }
}
