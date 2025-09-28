<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('loan_fee_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained('loan_agreements')->onDelete('cascade');
            $table->date('date');
            $table->string('type');
            $table->unsignedBigInteger('amount');
            $table->boolean('capitalize')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_fee_lines');
    }
};
