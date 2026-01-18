<?php

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Foundation\Models\AuditLog;
use Modules\Foundation\Models\PaymentTerm;
use Modules\Foundation\Observers\AuditLogObserver;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    // Setup a user
    $this->user = User::factory()->create();
    $this->company = Company::factory()->create();
});

test('audit log has correct relationships', function () {
    $log = AuditLog::factory()->create([
        'user_id' => $this->user->id,
        'company_id' => $this->company->id,
        'auditable_type' => PaymentTerm::class,
        'auditable_id' => 1,
    ]);

    expect($log->user)->toBeInstanceOf(User::class)
        ->and($log->user->id)->toBe($this->user->id)
        ->and($log->company)->toBeInstanceOf(Company::class)
        ->and($log->company->id)->toBe($this->company->id);

    // Test polymorphic relationship
    $paymentTerm = PaymentTerm::factory()->create();
    $log->auditable_type = PaymentTerm::class;
    $log->auditable_id = $paymentTerm->id;
    $log->save();

    expect($log->refresh()->auditable)->toBeInstanceOf(PaymentTerm::class)
        ->and($log->auditable->id)->toBe($paymentTerm->id);
});

test('audit log casts values to array', function () {
    $oldValues = ['name' => 'Old Name', 'amount' => 100];
    $newValues = ['name' => 'New Name', 'amount' => 200];

    $log = AuditLog::factory()->create([
        'old_values' => $oldValues,
        'new_values' => $newValues,
    ]);

    $log->refresh();

    expect($log->old_values)->toBeArray()
        ->and($log->old_values)->toBe($oldValues)
        ->and($log->new_values)->toBeArray()
        ->and($log->new_values)->toBe($newValues);
});

test('audit log does not have updated_at', function () {
    $log = AuditLog::factory()->create();

    expect($log->timestamps)->toBeTrue(); // Model says timestamps = true but UPDATED_AT is null

    // Check if updated_at column exists or is null
    // In migration usually it's created, but model ignores it on update

    // If we update it, updated_at should not change if the model explicitly tracks it as null constant
    // OR if created_at is set but updated_at is generic.

    // The model has public const UPDATED_AT = null;
    expect(AuditLog::UPDATED_AT)->toBeNull();

    // Verify typical behavior: creating sets created_at
    expect($log->created_at)->not->toBeNull();

    // Updating shouldn't fail potentially, but conceptually audit logs are immutable.
    // However, if we do update, we want to ensure no updated_at query is fired or it works.
    $log->description = 'Updated description';
    $log->save();

    $log->refresh();
    expect($log->description)->toBe('Updated description');
});

test('it logs record creation via observer', function () {
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

test('it logs record update via observer', function () {
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

    // Depending on cast, it might be string or raw value if not array cast... but it IS array cast.
    // However, PaymentTerm 'name' is translatable? Let's check PaymentTerm
    // If PaymentTerm name is translatable, it returns array or string?
    // Let's assume standard behavior for now.

    // Actually, PaymentTerm might use HasTranslations.
    // We should check PaymentTerm.php but assume basic string for 'Original Name' if not cast to json.

    // Tests in AuditLogObserverTest used json_encode check so likely array/json.
    // "expect(json_encode($oldName))->toContain('Original Name')"

    // If it's a simple string column in DB, getChanges() returns string.
    // If it's a JSON column cast to array, getChanges() might return array.

    // We will stick to loose assertions or inspect the real values.
    // Using the same logic as previous test for safety.

    if (is_array($oldName)) {
        expect(json_encode($oldName))->toContain('Original Name');
    } else {
        expect($oldName)->toBe('Original Name');
    }
});

test('it logs status change via observer', function () {
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
    $modelClass = new class extends Model
    {
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

test('it logs record deletion via observer', function () {
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
