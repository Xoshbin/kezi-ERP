<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Jmeryar\Accounting\Enums\Accounting\AccountType;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('account_group_id')->nullable();
            $table->string('code');
            $table->json('name');
            $allowedTypes = collect(AccountType::cases())->pluck('value')->all();
            $table->enum('type', $allowedTypes);
            $table->boolean('is_deprecated')->default(false);
            $table->boolean('can_create_assets')->default(false);
            $table->foreignId('currency_id')->nullable()->constrained();
            $table->timestamps();

            $table->boolean('allow_reconciliation')
                ->default(false)
                ->comment('Whether this account can be used in reconciliation processes (A/R, A/P, Bank)');

            // Add index for performance when filtering reconcilable accounts
            $table->index(['company_id', 'allow_reconciliation']);

            $table->unique(['company_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
