<?php

use App\Enums\Accounting\AccountType;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->string('code');
            $table->json('name');
            $allowedTypes = collect(AccountType::cases())->pluck('value')->all();
            $table->enum('type', $allowedTypes);
            $table->boolean('is_deprecated')->default(false);
            $table->boolean('can_create_assets')->default(false);
            $table->timestamps();

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
