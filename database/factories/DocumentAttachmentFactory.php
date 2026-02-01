<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Jmeryar\Foundation\Models\DocumentAttachment;
use Jmeryar\Sales\Models\Invoice;

/**
 * @extends Factory<DocumentAttachment>
 */
class DocumentAttachmentFactory extends Factory
{
    protected $model = DocumentAttachment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $fileTypes = [
            ['name' => 'document.pdf', 'mime' => 'application/pdf'],
            ['name' => 'invoice.jpg', 'mime' => 'image/jpeg'],
            ['name' => 'receipt.png', 'mime' => 'image/png'],
            ['name' => 'contract.docx', 'mime' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
            ['name' => 'spreadsheet.xlsx', 'mime' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        ];

        $fileType = $this->faker->randomElement($fileTypes);

        // Default to Invoice, but can be overridden
        $attachableType = Invoice::class;
        $attachable = Invoice::factory();

        return [
            'company_id' => Company::factory(),
            'attachable_type' => $attachableType,
            'attachable_id' => $attachable,
            'file_name' => $fileType['name'],
            'file_path' => 'document-attachments/'.$this->faker->uuid().'/'.$fileType['name'],
            'file_size' => $this->faker->numberBetween(1024, 10 * 1024 * 1024), // 1KB to 10MB
            'mime_type' => $fileType['mime'],
            'uploaded_by_user_id' => User::factory(),
        ];
    }
}
