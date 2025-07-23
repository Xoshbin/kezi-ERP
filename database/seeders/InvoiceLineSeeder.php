<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Product;
use App\Models\Tax;
use Illuminate\Database\Seeder;

class InvoiceLineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $invoices = Invoice::all();
        $products = Product::take(3)->get();
        $taxes = Tax::all();

        if ($products->count() < 3) {
            throw new \Exception('Not enough products found to seed invoice lines.');
        }

        if ($taxes->isEmpty()) {
            throw new \Exception('No taxes found to seed invoice lines.');
        }

        foreach ($invoices as $invoice) {
            for ($i = 0; $i < rand(2, 3); $i++) {
                $product = $products->random();
                $quantity = rand(1, 5);
                InvoiceLine::updateOrCreate(
                    [
                        'invoice_id' => $invoice->id,
                        'product_id' => $product->id,
                    ],
                    [
                        'tax_id' => $taxes->random()->id,
                        'quantity' => $quantity,
                        'unit_price' => $product->standard_price,
                        'description' => $product->name,
                        'discount' => 0,
                        'amount' => $quantity * $product->standard_price,
                    ]
                );
            }
        }
    }
}
