<?php

namespace Jmeryar\Inventory\Tests\Feature\Concurrency;

use App\Models\Company;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Jmeryar\Accounting\Enums\Accounting\AccountType;
use Jmeryar\Accounting\Enums\Accounting\JournalType;
use Jmeryar\Accounting\Models\Account;
use Jmeryar\Accounting\Models\Journal;
use Jmeryar\Inventory\Models\StockLocation;
use Jmeryar\Product\Enums\Products\ProductType;
use Jmeryar\Product\Models\Product;

it('handles concurrent stock moves correctly without race conditions', function () {
    /**
     * NOTE ON TEST STRATEGY:
     * We spawn separate OS processes that compete for the same StockQuant row.
     * 1. We create a shared file-based SQLite database.
     * 2. We generate a standalone worker script that bootstraps Laravel.
     * 3. We launch multiple concurrent processes running that script.
     * 4. Each process creates and confirms N stock moves for the same product and location.
     * 5. We verify that the final StockQuant quantity is exactly (Number of Processes * N).
     */

    // 1. Setup a shared SQLite file for all processes
    $dbFile = base_path('database/testing_inventory_concurrency.sqlite');

    // Ensure clean state
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }
    touch($dbFile);

    // Configure the test process to use this specific file
    config(['database.connections.sqlite_concurrent_inv' => [
        'driver' => 'sqlite',
        'database' => $dbFile,
        'foreign_key_constraints' => false,
    ]]);

    // Temporarily switch default connection to run migrations
    $originalDefault = config('database.default');
    config(['database.default' => 'sqlite_concurrent_inv']);

    // Run migrations on the file-based DB
    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite_concurrent_inv',
        '--force' => true,
    ]);

    // Create prerequisite data
    /** @var Company $company */
    $company = Company::factory()->create(['name' => 'Concurrent Inv Co']);

    /** @var \App\Models\User $user */
    $user = \App\Models\User::factory()->create();

    // Setup accounting defaults for company
    $purchaseJournal = Journal::factory()->create([
        'company_id' => $company->id,
        'type' => JournalType::Purchase,
    ]);
    $company->update(['default_purchase_journal_id' => $purchaseJournal->id]);

    $inventoryAccount = Account::factory()->create([
        'company_id' => $company->id,
        'type' => AccountType::CurrentAssets,
    ]);
    $stockInputAccount = Account::factory()->create([
        'company_id' => $company->id,
        'type' => AccountType::CurrentAssets,
    ]);

    /** @var Product $product */
    $product = Product::factory()->create([
        'company_id' => $company->id,
        'name' => 'Concurrent Test Product',
        'type' => ProductType::Storable,
        'default_inventory_account_id' => $inventoryAccount->id,
        'default_stock_input_account_id' => $stockInputAccount->id,
    ]);

    /** @var StockLocation $sourceLocation */
    $sourceLocation = StockLocation::factory()->create([
        'company_id' => $company->id,
        'name' => 'Source',
    ]);

    /** @var StockLocation $destLocation */
    $destLocation = StockLocation::factory()->create([
        'company_id' => $company->id,
        'name' => 'Destination',
    ]);

    $companyId = $company->id;
    $userId = $user->id;
    $productId = $product->id;
    $sourceLocationId = $sourceLocation->id;
    $destLocationId = $destLocation->id;

    // Create the worker script
    $workerScriptPath = base_path('tests/inventory_concurrency_worker.php');
    $autoloadPath = base_path('vendor/autoload.php');
    $bootstrapPath = base_path('bootstrap/app.php');

    $movesPerProcess = 5;

    $workerCode = <<<PHP
<?php
require '{$autoloadPath}';
\$app = require '{$bootstrapPath}';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

// Force configuration to match the test setup
config(['database.connections.sqlite_concurrent_inv' => [
    'driver' => 'sqlite',
    'database' => '{$dbFile}',
    'foreign_key_constraints' => false,
]]);
config(['database.default' => 'sqlite_concurrent_inv']);

try {
    \$service = app(\Jmeryar\Inventory\Services\Inventory\StockMoveService::class);
    
    for (\$i = 0; \$i < {$movesPerProcess}; \$i++) {
        \$attempts = 0;
        \$success = false;
        
        while (\$attempts < 30 && !\$success) {
            try {
                \Illuminate\Support\Facades\DB::transaction(function() use (\$service) {
                    \$dto = new \Jmeryar\Inventory\DataTransferObjects\Inventory\CreateStockMoveDTO(
                        company_id: {$companyId},
                        move_type: \Jmeryar\Inventory\Enums\Inventory\StockMoveType::Incoming,
                        status: \Jmeryar\Inventory\Enums\Inventory\StockMoveStatus::Done,
                        move_date: \Carbon\Carbon::now(),
                        created_by_user_id: {$userId},
                        product_lines: [
                            new \Jmeryar\Inventory\DataTransferObjects\Inventory\CreateStockMoveProductLineDTO(
                                product_id: {$productId},
                                quantity: 1.0,
                                to_location_id: {$destLocationId},
                                from_location_id: {$sourceLocationId},
                            )
                        ],
                        reference: 'SYNC-' . getmypid() . '-' . uniqid()
                    );
                    
                    \$service->createMove(\$dto);
                });
                \$success = true;
            } catch (\Illuminate\Database\QueryException \$e) {
                if (str_contains(\$e->getMessage(), 'database is locked') || str_contains(\$e->getMessage(), 'Deadlock')) {
                    usleep(random_int(50000, 150000));
                    \$attempts++;
                    continue;
                }
                throw \$e;
            }
        }
        
        if (!\$success) {
            echo "ERROR: Failed to acquire lock after 30 attempts\\n";
            exit(1);
        }
    }
} catch (\Throwable \$e) {
    echo "ERROR: " . \$e->getMessage() . "\\n";
    echo \$e->getTraceAsString() . "\\n";
    exit(1);
}
PHP;

    file_put_contents($workerScriptPath, $workerCode);

    // 2. Run multiple processes concurrently
    $numberOfProcesses = 5;
    $processes = [];
    for ($i = 0; $i < $numberOfProcesses; $i++) {
        $processes[] = Process::start(PHP_BINARY.' '.$workerScriptPath);
    }

    $errors = [];
    foreach ($processes as $process) {
        $result = $process->wait();
        if ($result->exitCode() !== 0) {
            $errors[] = "Process failed with exit code {$result->exitCode()}: {$result->output()} {$result->errorOutput()}";
        }
    }

    // Cleanup
    if (file_exists($workerScriptPath)) {
        unlink($workerScriptPath);
    }

    // 4. Verification
    if (! empty($errors)) {
        $this->fail('Worker processes encountered errors: '.implode("\n", $errors));
    }

    // Check final quantity
    $finalQuant = DB::connection('sqlite_concurrent_inv')
        ->table('stock_quants')
        ->where('product_id', $productId)
        ->where('location_id', $destLocationId)
        ->first();

    $expectedTotal = $numberOfProcesses * $movesPerProcess;

    expect($finalQuant)->not->toBeNull('StockQuant record not found');
    /** @var \stdClass $finalQuant */
    expect((float) $finalQuant->quantity)->toBe((float) $expectedTotal, "Final quantity mismatch. Expected $expectedTotal, got {$finalQuant->quantity}");

    // Cleanup DB file
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }

    // Restore config
    config(['database.default' => $originalDefault]);
});
