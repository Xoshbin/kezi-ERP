<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class)
    ->in(__DIR__ . '/Feature', __DIR__ . '/Unit');

