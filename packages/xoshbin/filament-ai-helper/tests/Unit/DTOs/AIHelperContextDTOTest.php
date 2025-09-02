<?php

use Xoshbin\FilamentAiHelper\DTOs\AIHelperContextDTO;
use Illuminate\Database\Eloquent\Model;

// Mock model for testing
class TestModel extends Model
{
    protected $table = 'test_models';
    protected $fillable = ['name', 'email'];

    public static function find($id)
    {
        if ($id === 1) {
            $model = new static();
            $model->id = 1;
            $model->name = 'Test Model';
            $model->email = 'test@example.com';
            return $model;
        }
        return null;
    }
}

it('can be instantiated with required parameters', function () {
    $dto = new AIHelperContextDTO(
        modelClass: 'App\\Models\\Invoice',
        modelId: 123,
        resourceClass: 'App\\Filament\\Resources\\InvoiceResource',
        userQuestion: 'What is the total amount?',
        locale: 'en'
    );

    expect($dto->modelClass)->toBe('App\\Models\\Invoice');
    expect($dto->modelId)->toBe(123);
    expect($dto->resourceClass)->toBe('App\\Filament\\Resources\\InvoiceResource');
    expect($dto->userQuestion)->toBe('What is the total amount?');
    expect($dto->locale)->toBe('en');
});

it('can be created from array', function () {
    $data = [
        'model_class' => 'App\\Models\\Invoice',
        'model_id' => 123,
        'resource_class' => 'App\\Filament\\Resources\\InvoiceResource',
        'user_question' => 'What is the total amount?',
        'locale' => 'en',
        'additional_context' => ['key' => 'value']
    ];

    $dto = AIHelperContextDTO::fromArray($data);

    expect($dto->modelClass)->toBe('App\\Models\\Invoice');
    expect($dto->modelId)->toBe(123);
    expect($dto->additionalContext)->toBe(['key' => 'value']);
});

it('can be converted to array', function () {
    $dto = new AIHelperContextDTO(
        modelClass: 'App\\Models\\Invoice',
        modelId: 123,
        resourceClass: 'App\\Filament\\Resources\\InvoiceResource',
        userQuestion: 'What is the total amount?',
        locale: 'en',
        additionalContext: ['key' => 'value']
    );

    $array = $dto->toArray();

    expect($array)->toHaveKey('model_class', 'App\\Models\\Invoice');
    expect($array)->toHaveKey('model_id', 123);
    expect($array)->toHaveKey('additional_context', ['key' => 'value']);
});

it('can get model instance when record is provided', function () {
    $model = new TestModel();
    $model->id = 1;
    $model->name = 'Test';

    $dto = new AIHelperContextDTO(
        modelClass: TestModel::class,
        modelId: 1,
        resourceClass: 'TestResource',
        userQuestion: 'Test question',
        locale: 'en',
        record: $model
    );

    expect($dto->getModel())->toBe($model);
});

it('can get model instance by finding it', function () {
    $dto = new AIHelperContextDTO(
        modelClass: TestModel::class,
        modelId: 1,
        resourceClass: 'TestResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    $model = $dto->getModel();

    expect($model)->toBeInstanceOf(TestModel::class);
    expect($model->id)->toBe(1);
});

it('returns null for non-existent model', function () {
    $dto = new AIHelperContextDTO(
        modelClass: TestModel::class,
        modelId: 999,
        resourceClass: 'TestResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    expect($dto->getModel())->toBeNull();
});

it('returns null for invalid model class', function () {
    $dto = new AIHelperContextDTO(
        modelClass: 'NonExistentClass',
        modelId: 1,
        resourceClass: 'TestResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    expect($dto->getModel())->toBeNull();
});

it('can get resource name without namespace', function () {
    $dto = new AIHelperContextDTO(
        modelClass: 'App\\Models\\Invoice',
        modelId: 123,
        resourceClass: 'App\\Filament\\Resources\\InvoiceResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    expect($dto->getResourceName())->toBe('InvoiceResource');
});

it('can get model name without namespace', function () {
    $dto = new AIHelperContextDTO(
        modelClass: 'App\\Models\\Invoice',
        modelId: 123,
        resourceClass: 'App\\Filament\\Resources\\InvoiceResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    expect($dto->getModelName())->toBe('Invoice');
});

it('can check if has valid model', function () {
    $validDto = new AIHelperContextDTO(
        modelClass: TestModel::class,
        modelId: 1,
        resourceClass: 'TestResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    $invalidDto = new AIHelperContextDTO(
        modelClass: TestModel::class,
        modelId: 999,
        resourceClass: 'TestResource',
        userQuestion: 'Test question',
        locale: 'en'
    );

    expect($validDto->hasValidModel())->toBeTrue();
    expect($invalidDto->hasValidModel())->toBeFalse();
});

it('sanitizes user question when enabled', function () {
    config(['filament-ai-helper.security.sanitize_input' => true]);

    $dto = new AIHelperContextDTO(
        modelClass: 'App\\Models\\Invoice',
        modelId: 123,
        resourceClass: 'TestResource',
        userQuestion: '<script>alert("xss")</script>What is the total?  ',
        locale: 'en'
    );

    expect($dto->getSanitizedQuestion())->toBe('What is the total?');
});

it('does not sanitize when disabled', function () {
    config(['filament-ai-helper.security.sanitize_input' => false]);

    $dto = new AIHelperContextDTO(
        modelClass: 'App\\Models\\Invoice',
        modelId: 123,
        resourceClass: 'TestResource',
        userQuestion: '<script>alert("xss")</script>What is the total?',
        locale: 'en'
    );

    expect($dto->getSanitizedQuestion())->toBe('<script>alert("xss")</script>What is the total?');
});
