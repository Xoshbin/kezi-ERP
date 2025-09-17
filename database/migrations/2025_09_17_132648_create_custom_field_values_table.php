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
        Schema::create('custom_field_values', function (Blueprint $table) {
            $table->id();

            // Reference to the custom field definition
            $table->foreignId('custom_field_definition_id')
                ->constrained('custom_field_definitions')
                ->onDelete('cascade');

            // Polymorphic relationship to the model instance that has custom field values
            $table->string('customizable_type'); // e.g., 'App\Models\Partner'
            $table->unsignedBigInteger('customizable_id'); // ID of the specific model instance

            // The field key from the definition
            $table->string('field_key'); // e.g., 'emergency_contact'

            // The actual value stored as JSON to handle different data types
            // For simple types: {"value": "John Doe"}
            // For arrays/objects: {"value": ["option1", "option2"]}
            $table->json('field_value');

            $table->timestamps();

            // Indexes for performance
            $table->index(['custom_field_definition_id']);
            $table->index(['customizable_type', 'customizable_id']);
            $table->index(['field_key']);

            // Ensure one value per field per model instance
            $table->unique(['custom_field_definition_id', 'customizable_type', 'customizable_id', 'field_key'], 'unique_custom_field_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
