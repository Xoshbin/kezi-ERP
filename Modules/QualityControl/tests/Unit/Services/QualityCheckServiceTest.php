<?php

namespace Modules\QualityControl\Tests\Unit\Services;

use App\Models\User;
use Mockery;
use Modules\QualityControl\Actions\CreateQualityCheckAction;
use Modules\QualityControl\Actions\RecordQualityCheckResultAction;
use Modules\QualityControl\DataTransferObjects\CreateQualityCheckDTO;
use Modules\QualityControl\DataTransferObjects\RecordQualityCheckResultDTO;
use Modules\QualityControl\Models\QualityCheck;
use Modules\QualityControl\Models\QualityControlPoint;
use Modules\QualityControl\Services\QualityCheckService;

beforeEach(function () {
    $this->createAction = Mockery::mock(CreateQualityCheckAction::class);
    $this->recordAction = Mockery::mock(RecordQualityCheckResultAction::class);

    $this->service = new QualityCheckService(
        $this->createAction,
        $this->recordAction
    );
});

describe('createFromControlPoint', function () {
    it('delegates to CreateQualityCheckAction with correct DTO', function () {
        // Arrange
        $controlPoint = Mockery::mock(QualityControlPoint::class);
        $controlPoint->shouldReceive('getAttribute')->with('company_id')->andReturn(1);
        $controlPoint->shouldReceive('getAttribute')->with('inspection_template_id')->andReturn(5);

        $source = Mockery::mock('alias:SomeSourceModel');
        $source->shouldReceive('getAttribute')->with('id')->andReturn(10);

        // We need a class name for get_class($source)
        // Mocking get_class is hard. Let's use a real anonymous class instance that behaves like a model
        // Or simpler: just accept that get_class on a mock returns the mock class name.
        // Let's use a real object for source to make get_class() predictable
        $sourceDummy = new class
        {
            public int $id = 10;
        };

        $productId = 100;
        $lotId = 200;
        $serialNumberId = 300;

        $expectedDto = new CreateQualityCheckDTO(
            companyId: 1,
            sourceType: get_class($sourceDummy),
            sourceId: 10,
            productId: 100,
            lotId: 200,
            serialNumberId: 300,
            inspectionTemplateId: 5
        );

        $expectedQualityCheck = Mockery::mock(QualityCheck::class);

        // Expectation
        $this->createAction->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function ($arg) use ($expectedDto) {
                return $arg instanceof CreateQualityCheckDTO &&
                    $arg->companyId === $expectedDto->companyId &&
                    $arg->sourceType === $expectedDto->sourceType &&
                    $arg->sourceId === $expectedDto->sourceId &&
                    $arg->productId === $expectedDto->productId &&
                    $arg->lotId === $expectedDto->lotId &&
                    $arg->serialNumberId === $expectedDto->serialNumberId &&
                    $arg->inspectionTemplateId === $expectedDto->inspectionTemplateId;
            }))
            ->andReturn($expectedQualityCheck);

        // Act
        $result = $this->service->createFromControlPoint(
            $controlPoint,
            $sourceDummy,
            $productId,
            $lotId,
            $serialNumberId
        );

        // Assert
        expect($result)->toBe($expectedQualityCheck);
    });

    it('handles optional parameters correctly', function () {
        // Arrange
        $controlPoint = Mockery::mock(QualityControlPoint::class);
        $controlPoint->shouldReceive('getAttribute')->with('company_id')->andReturn(2);
        $controlPoint->shouldReceive('getAttribute')->with('inspection_template_id')->andReturn(6);
        $sourceDummy = new class
        {
            public int $id = 20;
        };
        $productId = 101;

        $expectedQualityCheck = Mockery::mock(QualityCheck::class);

        // Expectation
        $this->createAction->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function ($arg) {
                return $arg instanceof CreateQualityCheckDTO &&
                   $arg->companyId === 2 &&
                   $arg->productId === 101 &&
                   $arg->lotId === null &&
                   $arg->serialNumberId === null;
            }))
            ->andReturn($expectedQualityCheck);

        // Act
        $result = $this->service->createFromControlPoint(
            $controlPoint,
            $sourceDummy,
            $productId
        );

        // Assert
        expect($result)->toBe($expectedQualityCheck);
    });
});

describe('recordResults', function () {
    it('delegates to RecordQualityCheckResultAction with correct DTO', function () {
        // Arrange
        $qualityCheck = Mockery::mock(QualityCheck::class);
        $qualityCheck->shouldReceive('getAttribute')->with('id')->andReturn(500);

        $inspector = Mockery::mock(User::class);
        $inspector->shouldReceive('getAttribute')->with('id')->andReturn(99);

        $lineResults = ['line1' => 'pass'];
        $notes = 'All good';

        $expectedQualityCheck = Mockery::mock(QualityCheck::class);

        // Expectation
        $this->recordAction->shouldReceive('execute')
            ->once()
            ->with(Mockery::on(function ($arg) use ($lineResults, $notes) {
                return $arg instanceof RecordQualityCheckResultDTO &&
                    $arg->qualityCheckId === 500 &&
                    $arg->inspectedByUserId === 99 &&
                    $arg->lineResults === $lineResults &&
                    $arg->notes === $notes;
            }))
            ->andReturn($expectedQualityCheck);

        // Act
        $result = $this->service->recordResults(
            $qualityCheck,
            $lineResults,
            $inspector,
            $notes
        );

        // Assert
        expect($result)->toBe($expectedQualityCheck);
    });
});
