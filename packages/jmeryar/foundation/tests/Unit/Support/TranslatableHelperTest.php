<?php

use Jmeryar\Foundation\Support\TranslatableHelper;

describe('TranslatableHelper', function () {
    describe('getLocalizedValue', function () {
        it('returns empty string for null value', function () {
            expect(TranslatableHelper::getLocalizedValue(null))->toBe('');
        });

        it('returns empty string for empty string value', function () {
            expect(TranslatableHelper::getLocalizedValue(''))->toBe('');
        });

        it('returns string as-is when not JSON', function () {
            expect(TranslatableHelper::getLocalizedValue('Cash Account'))->toBe('Cash Account');
        });

        it('extracts current locale from JSON string', function () {
            app()->setLocale('en');
            $json = '{"en":"Cash","ar":"نقد","ckb":"پارە"}';

            expect(TranslatableHelper::getLocalizedValue($json))->toBe('Cash');
        });

        it('extracts specified locale from JSON string', function () {
            $json = '{"en":"Cash","ar":"نقد","ckb":"پارە"}';

            expect(TranslatableHelper::getLocalizedValue($json, 'ar'))->toBe('نقد');
            expect(TranslatableHelper::getLocalizedValue($json, 'ckb'))->toBe('پارە');
        });

        it('extracts value from array with current locale', function () {
            app()->setLocale('en');
            $array = ['en' => 'Cash', 'ar' => 'نقد', 'ckb' => 'پارە'];

            expect(TranslatableHelper::getLocalizedValue($array))->toBe('Cash');
        });

        it('extracts specified locale from array', function () {
            $array = ['en' => 'Cash', 'ar' => 'نقد', 'ckb' => 'پارە'];

            expect(TranslatableHelper::getLocalizedValue($array, 'ar'))->toBe('نقد');
        });

        it('falls back to English when current locale not available', function () {
            app()->setLocale('fr'); // French not in the array
            $array = ['en' => 'Cash', 'ar' => 'نقد'];

            expect(TranslatableHelper::getLocalizedValue($array))->toBe('Cash');
        });

        it('falls back to first available locale when neither current nor English available', function () {
            app()->setLocale('fr');
            $array = ['ar' => 'نقد', 'ckb' => 'پارە'];

            expect(TranslatableHelper::getLocalizedValue($array))->toBe('نقد');
        });

        it('returns empty string for empty array', function () {
            expect(TranslatableHelper::getLocalizedValue([]))->toBe('');
        });

        it('returns empty string for empty JSON object', function () {
            expect(TranslatableHelper::getLocalizedValue('{}'))->toBe('');
        });
    });
});
