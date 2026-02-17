<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pos_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // table, room, pump, chair
            $table->string('name');
            $table->string('status')->default('available'); // available, occupied, reserved
            $table->json('layout_data')->nullable(); // coordinates, shape
            $table->timestamps();

            $table->index(['pos_profile_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_resources');
    }
};
