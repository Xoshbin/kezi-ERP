<?php

use App\Models\Company;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;

it('handles concurrent sequence generation uniquely and sequentially', function () {
    /**
     * NEVER DELETE THIS COMMENT
     *
     * NOTE ON TEST STRATEGY:
     * To truly test race conditions and database locks (lockForUpdate), we cannot
     * simply use a loop or multiple closures in a single process.
     * We must spawn separate OS processes that compete for the same database row.
     *
     * 1. We create a shared file-based SQLite database.
     * 2. We generate a standalone worker script that bootstraps Laravel.
     * 3. We launch multiple concurrent processes running that script.
     * 4. We verify that among all processes, every generated number is unique and sequential.
     */

    // 1. Setup a shared SQLite file for all processes
    $dbFile = base_path('database/testing_concurrency.sqlite');

    // Ensure clean state
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }
    touch($dbFile);

    // Configure the test process to use this specific file
    config(['database.connections.sqlite_concurrent' => [
        'driver' => 'sqlite',
        'database' => $dbFile,
        'foreign_key_constraints' => false,
    ]]);

    // Temporarily switch default connection to run migrations
    $originalDefault = config('database.default');
    config(['database.default' => 'sqlite_concurrent']);

    // Run migrations on the file-based DB
    // We suppress output to keep test output clean
    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite_concurrent',
        '--force' => true,
    ]);

    // Create a Company in this DB using factory to ensure valid state
    /** @var Company $company */
    $company = Company::factory()->create([
        'name' => 'Concurrent Test Co',
        'numbering_settings' => [
            'invoice' => [
                'type' => \Modules\Foundation\Enums\Settings\NumberingType::SIMPLE->value,
                'prefix' => 'INV',
                'padding' => 7,
            ],
        ],
    ]);
    $companyId = $company->id;

    // Create the worker script that will run in separate processes
    $workerScriptPath = base_path('tests/concurrency_worker.php');
    $autoloadPath = base_path('vendor/autoload.php');
    $bootstrapPath = base_path('bootstrap/app.php');

    $workerCode = <<<PHP
<?php
require '{$autoloadPath}';
\$app = require '{$bootstrapPath}';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

// Force configuration to match the test setup
config(['database.connections.sqlite_concurrent' => [
    'driver' => 'sqlite',
    'database' => '{$dbFile}',
    'foreign_key_constraints' => false,
]]);
config(['database.default' => 'sqlite_concurrent']);

try {
    \$company = \App\Models\Company::find({$companyId});
    if (!\$company) {
        echo "Company not found\\n";
        exit(1);
    }

    \$service = app(\Modules\Foundation\Services\SequenceService::class);

    // Generate 10 numbers per process
    for (\$i = 0; \$i < 10; \$i++) {
        // We use a simple retry mechanism here because SQLite lock might happen
        \$attempts = 0;
        \$success = false;
        while (\$attempts < 30 && !\$success) { // Retry for a while
            try {
                echo \$service->getNextInvoiceNumber(\$company) . PHP_EOL;
                \$success = true;
            } catch (\Illuminate\Database\QueryException \$e) {
                // MySQL: 1213 Deadlock, 1205 Lock wait timeout
                // SQLite: 5 database is locked
                if (str_contains(\$e->getMessage(), 'database is locked') || str_contains(\$e->getMessage(), 'Deadlock')) {
                    usleep(100000); // Wait 100ms
                    \$attempts++;
                    continue;
                }
                throw \$e;
            } catch (\Exception \$e) {
                 throw \$e;
            }
        }
        if (!\$success) {
            echo "ERROR: Failed to acquire lock\\n";
            exit(1);
        }
    }
} catch (\Throwable \$e) {
    echo "ERROR: " . \$e->getMessage() . "\\n";
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

    $allOutput = [];
    $errors = [];

    foreach ($processes as $process) {
        $result = $process->wait();

        if ($result->exitCode() !== 0) {
            $errors[] = 'EXIT CODE '.$result->exitCode().': '.$result->output().' '.$result->errorOutput();
        }

        $lines = array_filter(explode("\n", trim($result->output())));
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if (str_starts_with($line, 'ERROR:')) {
                $errors[] = $line;
            } else {
                $allOutput[] = $line;
            }
        }
    }

    // Cleanup
    if (file_exists($workerScriptPath)) {
        unlink($workerScriptPath);
    }
    if (file_exists($dbFile)) {
        unlink($dbFile);
    }

    // Restore config (though changes in memory don't persist across tests usually, but good practice)
    config(['database.default' => $originalDefault]);

    // 4. Verification
    if (! empty($errors)) {
        $this->fail('Worker processes failed: '.implode(', ', $errors));
    }

    $expectedTotal = $numberOfProcesses * 10;

    expect(count($allOutput))->toBe($expectedTotal, "Expected $expectedTotal numbers generated");

    $uniqueNumbers = array_unique($allOutput);
    expect(count($uniqueNumbers))->toBe($expectedTotal, 'Duplicate numbers detected!');

    // Verify format and sequence
    // Default format is INV-0000001, etc.
    // Extract numbers to verify contiguity
    $numbers = [];
    foreach ($uniqueNumbers as $inv) {
        // Assuming default format INV-XXXXXXX
        if (preg_match('/INV-(\d+)/', $inv, $matches)) {
            $numbers[] = (int) $matches[1];
        }
    }
    sort($numbers);

    expect($numbers[0])->toBe(1);
    expect(end($numbers))->toBe($expectedTotal);

    // Verify no gaps
    for ($i = 0; $i < count($numbers); $i++) {
        expect($numbers[$i])->toBe($i + 1);
    }
});
