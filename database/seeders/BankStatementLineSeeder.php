<?php

namespace Database\Seeders;

use App\Models\BankStatement;
use App\Models\BankStatementLine;
use App\Models\Partner;
use Illuminate\Database\Seeder;

class BankStatementLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statements = BankStatement::all();
        $partners = Partner::all();

        if ($statements->isEmpty()) {
            $this->command->warn('No bank statements found. Skipping BankStatementLineSeeder.');
            return;
        }

        foreach ($statements as $statement) {
            $totalAmount = 0;
            for ($i = 0; $i < rand(5, 10); $i++) {
                $amount = rand(-20000, 50000) / 100;
                $totalAmount += $amount;

                BankStatementLine::create([
                    'bank_statement_id' => $statement->id,
                    'date' => $statement->statement_date->subDays(rand(1, 28)),
                    'description' => 'Transaction ' . ($i + 1),
                    'partner_id' => $partners->isEmpty() ? null : $partners->random()->id,
                    'amount' => $amount,
                    'status' => 'posted',
                    'company_id' => $statement->company_id,
                ]);
            }

            // Update the statement's ending balance
            $statement->ending_balance = $statement->starting_balance + $totalAmount;
            $statement->save();
        }
    }
}