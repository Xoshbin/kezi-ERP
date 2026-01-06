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
        Schema::create('cheque_bounced_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cheque_id')->constrained('cheques')->cascadeOnDelete();
            $table->datetime('bounced_at');
            $table->string('reason');
            $table->unsignedBigInteger('bank_charges')->nullable(); // Minor units
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cheque_bounced_logs');
    }
};
