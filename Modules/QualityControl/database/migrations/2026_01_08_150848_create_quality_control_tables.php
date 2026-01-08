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
        // Defect Types - categorization of quality issues
        Schema::create('defect_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('code', 50)->index();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
        });

        // Quality Inspection Templates - reusable inspection criteria sets
        Schema::create('quality_inspection_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        // Quality Inspection Parameters - individual quality characteristics (MICs)
        Schema::create('quality_inspection_parameters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('quality_inspection_templates')->cascadeOnDelete();
            $table->string('name');
            $table->string('check_type'); // pass_fail, measure, text_input, take_photo, instructions
            $table->decimal('min_value', 15, 4)->nullable();
            $table->decimal('max_value', 15, 4)->nullable();
            $table->string('unit_of_measure', 50)->nullable();
            $table->text('instructions')->nullable();
            $table->integer('sequence')->default(0);
            $table->timestamps();

            $table->index(['template_id', 'sequence']);
        });

        // Quality Control Points - defines WHERE/WHEN inspections are triggered
        Schema::create('quality_control_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_operation'); // goods_receipt, internal_transfer, manufacturing_output, customer_delivery
            $table->string('trigger_frequency'); // per_operation, per_product, per_quantity
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('inspection_template_id')->constrained('quality_inspection_templates')->cascadeOnDelete();
            $table->integer('quantity_threshold')->nullable(); // For per_quantity triggers
            $table->boolean('is_blocking')->default(false);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'trigger_operation', 'active']);
        });

        // Quality Checks - actual inspection records
        Schema::create('quality_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->morphs('source'); // StockPicking, ManufacturingOrder, etc. (creates index automatically)
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('serial_numbers')->nullOnDelete();
            $table->foreignId('inspection_template_id')->nullable()->constrained('quality_inspection_templates')->nullOnDelete();
            $table->string('status'); // draft, in_progress, passed, failed, conditionally_accepted
            $table->foreignId('inspected_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('inspected_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['product_id', 'status']);
        });

        // Quality Check Lines - individual parameter results per check
        Schema::create('quality_check_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quality_check_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parameter_id')->constrained('quality_inspection_parameters')->cascadeOnDelete();
            $table->boolean('result_pass_fail')->nullable();
            $table->decimal('result_numeric', 15, 4)->nullable();
            $table->text('result_text')->nullable();
            $table->string('result_image_path')->nullable();
            $table->boolean('is_within_tolerance')->nullable();
            $table->timestamps();

            $table->index('quality_check_id');
        });

        // Quality Alerts - issue tracking and CAPA management
        Schema::create('quality_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->foreignId('quality_check_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->nullable()->constrained('lots')->nullOnDelete();
            $table->foreignId('serial_number_id')->nullable()->constrained('serial_numbers')->nullOnDelete();
            $table->foreignId('defect_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status'); // new, in_progress, resolved, closed
            $table->text('description');
            $table->text('root_cause')->nullable();
            $table->text('corrective_action')->nullable();
            $table->text('preventive_action')->nullable();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reported_by_user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['product_id', 'status']);
            $table->index('assigned_to_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quality_alerts');
        Schema::dropIfExists('quality_check_lines');
        Schema::dropIfExists('quality_checks');
        Schema::dropIfExists('quality_control_points');
        Schema::dropIfExists('quality_inspection_parameters');
        Schema::dropIfExists('quality_inspection_templates');
        Schema::dropIfExists('defect_types');
    }
};
