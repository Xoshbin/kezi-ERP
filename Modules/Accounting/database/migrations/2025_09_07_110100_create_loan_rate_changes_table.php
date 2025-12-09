<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('loan_rate_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loan_agreements')->onDelete('cascade');
            $table->date('effective_date');
            $table->float('annual_rate');
            $table->timestamps();

            $table->index(['loan_id', 'effective_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_rate_changes');
    }
};
