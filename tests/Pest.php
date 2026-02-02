<?php

$root = dirname(__DIR__);

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in(
        __DIR__.'/Feature',
        ...glob($root.'/packages/kezi/*/tests/Feature'),
        ...glob($root.'/packages/kezi/*/tests/Unit'),
    );

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\DatabaseMigrations::class)
    ->in(...glob($root.'/packages/kezi/*/tests/Browser'));
