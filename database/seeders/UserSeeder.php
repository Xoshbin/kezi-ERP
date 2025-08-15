<?php

namespace Database\Seeders;

use Exception;
use App\Models\Company;
use App\Models\User;
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
        // Find the company 'Jmeryar Solutions'
        $company = Company::where('name', 'Jmeryar Solutions')->first();

        // If the company is not found, throw an exception.
        if (!$company) {
            throw new Exception('Company "Jmeryar Solutions" not found. Please run the CompanySeeder first.');
        }

        // Create the admin user
        User::updateOrCreate(
            ['email' => 'admin@jmeryar.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'company_id' => $company->id,
            ]
        );
    }
}