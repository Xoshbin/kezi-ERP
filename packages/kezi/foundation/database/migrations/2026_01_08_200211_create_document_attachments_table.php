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
        Schema::create('document_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies');
            $table->string('attachable_type'); // Polymorphic type (Invoice, PurchaseOrder, etc.)
            $table->unsignedBigInteger('attachable_id'); // Polymorphic ID
            $table->string('file_name'); // Original file name
            $table->string('file_path'); // Storage path
            $table->unsignedBigInteger('file_size'); // File size in bytes
            $table->string('mime_type'); // MIME type for validation and display
            $table->foreignId('uploaded_by_user_id')->constrained('users');
            $table->timestamps();

            // Add index for faster queries on polymorphic relationships
            $table->index(['attachable_type', 'attachable_id', 'created_at'], 'doc_attach_poly_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_attachments');
    }
};
