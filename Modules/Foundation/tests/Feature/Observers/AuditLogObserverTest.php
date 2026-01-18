<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Model;
use Modules\Foundation\Models\AuditLog;
use Modules\Foundation\Models\PaymentTerm;
use Modules\Foundation\Observers\AuditLogObserver;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function () {
    // Setup a user
    $this->user = User::factory()->create();
    $this->company = \App\Models\Company::factory()->create();
});

test('it logs record creation', function () {
    actingAs($this->user);

    $paymentTerm = PaymentTerm::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Test Term',
    ]);

    assertDatabaseHas('audit_logs', [
        'event_type' => 'record_created',
        'auditable_type' => PaymentTerm::class,
        'auditable_id' => $paymentTerm->id,
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
    ]);
});

test('it logs record update', function () {
    actingAs($this->user);

    $paymentTerm = PaymentTerm::factory()->create([
        'company_id' => $this->company->id,
        'name' => 'Original Name',
    ]);

    $paymentTerm->update(['name' => 'Updated Name']);

    assertDatabaseHas('audit_logs', [
        'event_type' => 'record_updated',
        'auditable_type' => PaymentTerm::class,
        'auditable_id' => $paymentTerm->id,
        'user_id' => $this->user->id,
    ]);

    $log = AuditLog::where('auditable_id', $paymentTerm->id)
        ->where('event_type', 'record_updated')
        ->first();

    $oldName = $log->old_values['name'];
    $newName = $log->new_values['name'];

    expect(json_encode($oldName))->toContain('Original Name')
        ->and(json_encode($newName))->toContain('Updated Name');
});

test('it logs status change', function () {
    actingAs($this->user);

    // Ensure idempotency for the temporary table
    Schema::dropIfExists('test_models_with_statuses');

    // Create a temporary table for testing status changes
    Schema::create('test_models_with_statuses', function (Blueprint $table) {
        $table->id();
        $table->string('status')->nullable();
        $table->timestamps();
    });

    // Define a dynamic model class
    $modelClass = new class extends Model {
        protected $table = 'test_models_with_statuses';
        protected $guarded = [];
        public $timestamps = true;
    };

    // Register the observer manually
    $modelClass::observe(AuditLogObserver::class);

    $model = $modelClass::create(['status' => 'draft']);

    // Update status
    $model->update(['status' => 'published']);

    assertDatabaseHas('audit_logs', [
        'event_type' => 'status_changed',
        'auditable_id' => $model->id,
        'user_id' => $this->user->id,
    ]);

    $log = AuditLog::where('auditable_id', $model->id)
        ->where('event_type', 'status_changed')
        ->first();

    expect($log->old_values['status'])->toBe('draft')
        ->and($log->new_values['status'])->toBe('published');

    // Clean up
    Schema::dropIfExists('test_models_with_statuses');
});

test('it logs record deletion', function () {
    actingAs($this->user);

    $paymentTerm = PaymentTerm::factory()->create([
        'company_id' => $this->company->id,
    ]);

    $id = $paymentTerm->id;

    $paymentTerm->delete();

    assertDatabaseHas('audit_logs', [
        'event_type' => 'record_deleted',
        'auditable_type' => PaymentTerm::class,
        'auditable_id' => $id,
        'user_id' => $this->user->id,
    ]);

    $log = AuditLog::where('auditable_id', $id)
        ->where('event_type', 'record_deleted')
        ->first();

    expect($log->old_values)->not->toBeEmpty();
});

test('it does not log when running in console and not authenticated', function () {
    // Ensure we are not authenticated
    auth()->logout();

    // We are running in console (test environment)
    // Create a record
    $paymentTerm = PaymentTerm::factory()->create([
        'company_id' => $this->company->id,
    ]);

    // Should NOT have a log
    assertDatabaseMissing('audit_logs', [
        'auditable_type' => PaymentTerm::class,
        'auditable_id' => $paymentTerm->id,
    ]);
});
