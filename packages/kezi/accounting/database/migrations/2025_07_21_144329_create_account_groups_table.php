<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('account_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id');
            $table->foreignId('parent_id')->nullable();
            $table->string('code_prefix_start', 10);  // e.g., "1100"
            $table->string('code_prefix_end', 10);    // e.g., "1199"
            $table->json('name');                      // Translatable
            $table->unsignedTinyInteger('level')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'code_prefix_start', 'code_prefix_end'], 'acct_grp_prefix_unique');
            $table->index(['company_id', 'level'], 'acct_grp_level_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_groups');
    }
};
