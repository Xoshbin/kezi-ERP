<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_pickings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('type');
            $table->string('state');
            $table->foreignId('partner_id')->nullable()->constrained('partners');
            $table->timestamp('scheduled_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->string('reference')->nullable();
            $table->string('origin')->nullable();
            $table->foreignId('created_by_user_id')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_pickings');
    }
};
