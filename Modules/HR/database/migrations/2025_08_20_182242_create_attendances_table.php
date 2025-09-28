<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');

            // Attendance Information
            $table->date('attendance_date');
            $table->time('clock_in_time')->nullable();
            $table->time('clock_out_time')->nullable();
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();

            // Calculated Fields
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->decimal('regular_hours', 5, 2)->nullable();
            $table->decimal('overtime_hours', 5, 2)->nullable();
            $table->decimal('break_hours', 5, 2)->nullable();

            // Status and Type
            $table->string('status')->default('present'); // present, absent, late, half_day, on_leave
            $table->string('attendance_type')->default('regular'); // regular, overtime, holiday, weekend

            // Location and Device Information
            $table->string('clock_in_location')->nullable(); // GPS coordinates or office location
            $table->string('clock_out_location')->nullable();
            $table->string('clock_in_device')->nullable(); // Device used for clocking in
            $table->string('clock_out_device')->nullable();
            $table->string('clock_in_ip')->nullable();
            $table->string('clock_out_ip')->nullable();

            // Notes and Adjustments
            $table->text('notes')->nullable();
            $table->boolean('is_manual_entry')->default(false);
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();

            // Leave Integration
            $table->foreignId('leave_request_id')->nullable()->constrained('leave_requests');

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'attendance_date']);
            $table->index(['employee_id', 'attendance_date']);
            $table->index(['status']);
            $table->index(['attendance_type']);

            // Unique constraint to prevent duplicate entries
            $table->unique(['employee_id', 'attendance_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
