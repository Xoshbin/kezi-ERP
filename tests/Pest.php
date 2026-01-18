<?php

$root = dirname(__DIR__);

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in(
        __DIR__.'/Feature',
        // Include module test directories so they also boot Laravel's TestCase
        $root.'/Modules/Accounting/tests/Feature',
        $root.'/Modules/Accounting/tests/Unit',
        $root.'/Modules/Foundation/tests/Feature',
        $root.'/Modules/Foundation/tests/Unit',
        $root.'/Modules/HR/tests/Feature',
        $root.'/Modules/HR/tests/Unit',
        $root.'/Modules/Inventory/tests/Feature',
        $root.'/Modules/Inventory/tests/Unit',
        $root.'/Modules/Payment/tests/Feature',
        $root.'/Modules/Payment/tests/Unit',
        $root.'/Modules/Product/tests/Feature',
        $root.'/Modules/Product/tests/Unit',
        $root.'/Modules/Purchase/tests/Feature',
        $root.'/Modules/Purchase/tests/Unit',
        $root.'/Modules/Sales/tests/Feature',
        $root.'/Modules/Sales/tests/Unit',
        // Project Management
        $root.'/Modules/ProjectManagement/tests/Feature',
        $root.'/Modules/ProjectManagement/tests/Unit',
        // Manufacturing
        $root.'/Modules/Manufacturing/tests/Feature',
        // Quality Control
        $root.'/Modules/QualityControl/tests/Feature',
        $root.'/Modules/QualityControl/tests/Unit',
    );

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\DatabaseMigrations::class)
    ->in($root.'/Modules/*/tests/Browser');
