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
        Schema::create('custom_field_definitions', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to any model that supports custom fields
            $table->string('model_type'); // e.g., 'App\Models\Partner'

            // JSON field containing multiple field definitions for this model
            // Structure: [
            //   {
            //     "key": "emergency_contact",                                    // Unique field identifier
            //     "label": {"en": "Emergency Contact", "ckb": "پەیوەندی لەکاتی فریاکەوتن"}, // Translatable field label
            //     "type": "text",                                               // Field type: text, textarea, number, boolean, date, select
            //     "required": false,                                            // Whether field is required
            //     "show_in_table": false,                                       // Whether to display as table column in Filament resources
            //     "options": [                                                  // For select fields only
            //       {"value": "option1", "label": {"en": "Option 1"}},
            //       {"value": "option2", "label": {"en": "Option 2"}}
            //     ],
            //     "validation_rules": ["max:255"],                              // Additional Laravel validation rules
            //     "help_text": {"en": "Enter emergency contact information"}   // Optional help text
            //   },
            //   ...
            // ]
            $table->json('field_definitions');

            // Metadata
            $table->json('name'); // Translatable name for this set of custom fields
            $table->json('description')->nullable(); // Translatable description
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Indexes for performance
            $table->index(['model_type', 'is_active']);
            $table->index(['model_type']);

            // Ensure one definition per model
            $table->unique(['model_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_field_definitions');
    }
};
