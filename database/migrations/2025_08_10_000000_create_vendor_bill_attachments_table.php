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
        Schema::create('vendor_bill_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_bill_id')->constrained('vendor_bills')->onDelete('cascade');
            $table->string('file_name'); // Original file name
            $table->string('file_path'); // Storage path
            $table->unsignedBigInteger('file_size'); // File size in bytes
            $table->string('mime_type'); // MIME type for validation and display
            $table->foreignId('uploaded_by_user_id')->constrained('users');
            $table->timestamps();

            // Add index for faster queries
            $table->index(['vendor_bill_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendor_bill_attachments');
    }
};
