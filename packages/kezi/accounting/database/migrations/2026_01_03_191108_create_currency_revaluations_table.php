<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Kezi\Accounting\Enums\Currency\RevaluationStatus;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('currency_revaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            // Revaluation details
            $table->date('revaluation_date')->comment('The date as of which balances are revalued');
            $table->string('reference')->nullable()->comment('Unique reference number for this revaluation');
            $table->text('description')->nullable();

            // Status tracking
            $allowedStatuses = collect(RevaluationStatus::cases())->pluck('value')->all();
            $table->enum('status', $allowedStatuses)->default(RevaluationStatus::Draft->value);
            $table->timestamp('posted_at')->nullable();

            // Totals (in company base currency)
            $table->bigInteger('total_gain')->default(0)->comment('Total unrealized gain in base currency minor units');
            $table->bigInteger('total_loss')->default(0)->comment('Total unrealized loss in base currency minor units');
            $table->bigInteger('net_adjustment')->default(0)->comment('Net adjustment (gain - loss) in base currency minor units');

            $table->timestamps();

            // Indexes for efficient querying
            $table->index(['company_id', 'revaluation_date']);
            $table->index(['company_id', 'status']);
        });

        // Create revaluation lines table to track individual account/currency adjustments
        Schema::create('currency_revaluation_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('currency_revaluation_id')->constrained('currency_revaluations')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts');
            $table->foreignId('currency_id')->constrained('currencies')->comment('The foreign currency being revalued');
            $table->foreignId('partner_id')->nullable()->constrained('partners')->nullOnDelete();

            // Balance information
            $table->bigInteger('foreign_currency_balance')->comment('Balance in foreign currency minor units');
            $table->decimal('historical_rate', 18, 10)->comment('Weighted average rate from original transactions');
            $table->decimal('current_rate', 18, 10)->comment('Exchange rate at revaluation date');

            // Calculated values in base currency
            $table->bigInteger('book_value')->comment('Current book value in base currency minor units');
            $table->bigInteger('revalued_amount')->comment('Revalued amount in base currency minor units');
            $table->bigInteger('adjustment_amount')->comment('Difference (gain or loss) in base currency minor units');

            $table->timestamps();

            // Indexes
            $table->index(['currency_revaluation_id', 'account_id'], 'cur_reval_id_acc_id_idx');
            $table->index(['account_id', 'currency_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_revaluation_lines');
        Schema::dropIfExists('currency_revaluations');
    }
};
