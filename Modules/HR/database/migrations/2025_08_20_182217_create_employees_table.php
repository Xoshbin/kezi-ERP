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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('department_id')->nullable()->constrained('departments')->onDelete('set null');
            $table->foreignId('position_id')->nullable()->constrained('positions')->onDelete('set null');
            $table->foreignId('manager_id')->nullable()->constrained('employees')->onDelete('set null');

            // Personal Information
            $table->string('employee_number')->unique();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('nationality')->nullable();
            $table->string('national_id')->nullable();
            $table->string('passport_number')->nullable();

            // Address Information
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('zip_code')->nullable();
            $table->string('country')->nullable();

            // Emergency Contact
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Employment Information
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->string('employment_status')->default('active'); // active, inactive, terminated
            $table->string('employee_type')->default('full_time'); // full_time, part_time, contract, intern

            // Bank Information for Payroll
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_routing_number')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes('deleted_at', precision: 0);

            // Indexes for performance
            $table->index(['company_id', 'is_active']);
            $table->index(['department_id']);
            $table->index(['position_id']);
            $table->index(['manager_id']);
            $table->index(['employment_status']);
            $table->index(['hire_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
