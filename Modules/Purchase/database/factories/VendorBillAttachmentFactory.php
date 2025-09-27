<?php

namespace Modules\Purchase\Database\Factories;

use App\Models\User;
use App\Models\VendorBillAttachment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Purchase\Models\VendorBill;

/**
 * @extends Factory<VendorBillAttachment>
 */
class VendorBillAttachmentFactory extends Factory
{
    protected $model = VendorBillAttachment::class;

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

        $vendorBill = VendorBill::factory();

        return [
            'vendor_bill_id' => $vendorBill,
            'company_id' => function (array $attributes) {
                return VendorBill::find($attributes['vendor_bill_id'])->company_id;
            },
            'file_name' => $fileType['name'],
            'file_path' => 'vendor-bill-attachments/'.$this->faker->uuid().'/'.$fileType['name'],
            'file_size' => $this->faker->numberBetween(1024, 10 * 1024 * 1024), // 1KB to 10MB
            'mime_type' => $fileType['mime'],
            'uploaded_by_user_id' => User::factory(),
        ];
    }
}
