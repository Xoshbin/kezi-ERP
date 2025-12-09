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
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('leave_type_id')->constrained('leave_types')->onDelete('cascade');

            // Request Information
            $table->string('request_number')->unique();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_requested', 5, 2); // Support half days
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            // Status and Approval
            $table->string('status')->default('pending'); // pending, approved, rejected, cancelled
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->text('rejection_reason')->nullable();

            // Documentation
            $table->json('attachments')->nullable(); // File paths for supporting documents

            // Delegation (who covers the work)
            $table->foreignId('delegate_employee_id')->nullable()->constrained('employees');
            $table->text('delegation_notes')->nullable();

            // System tracking
            $table->foreignId('requested_by_user_id')->constrained('users');
            $table->timestamp('submitted_at')->nullable();

            $table->timestamps();

            // Indexes for performance
            $table->index(['company_id', 'status']);
            $table->index(['employee_id']);
            $table->index(['leave_type_id']);
            $table->index(['start_date', 'end_date']);
            $table->index(['approved_by_user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
