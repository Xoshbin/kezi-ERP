<?php

namespace PHPUnit\Framework;

/**
 * @property \App\Models\Company $company
 * @property \App\Models\User $user
 * @property \Kezi\Foundation\Models\Partner $partner
 * @property \Kezi\Foundation\Models\Currency $currency
 * @property \Kezi\Product\Models\Product $product
 * @property \Kezi\Accounting\Models\Account $account
 * @property \Kezi\Accounting\Models\Account $inventoryAccount
 * @property \Kezi\Accounting\Models\Account $stockInputAccount
 * @property \Kezi\Accounting\Models\Account $cogsAccount
 * @property \Kezi\Inventory\Models\StockLocation $vendorLocation
 * @property \Kezi\Inventory\Models\StockLocation $stockLocation
 * @property \Kezi\Inventory\Models\StockLocation $adjustmentLocation
 * @property \Kezi\Inventory\Models\StockLocation $customerLocation
 * @property \Kezi\Foundation\Models\Partner $vendor
 * @property \Kezi\Foundation\Models\Currency $usdCurrency
 * @property \Kezi\Foundation\Models\Currency $eurCurrency
 * @property \Kezi\Accounting\Models\Journal $usdBankJournal
 * @property \Kezi\Accounting\Models\Journal $miscJournal
 * @property \Kezi\Accounting\Models\Account $assetAccount
 * @property \Kezi\Accounting\Models\Account $liabilityAccount
 * @property \Kezi\Accounting\Models\Account $incomeAccount
 * @property \Kezi\Accounting\Models\Account $retainedEarningsAccount
 *
 * @method void assertDatabaseHas(string $table, array $data, string $connection = null)
 * @method void assertDatabaseMissing(string $table, array $data, string $connection = null)
 * @method void assertDatabaseCount(string $table, int $count, string $connection = null)
 * @method void assertModelExists(\Illuminate\Database\Eloquent\Model $model)
 * @method void assertModelMissing(\Illuminate\Database\Eloquent\Model $model)
 * @method void assertCount(int|\Countable $expectedCount, $actual, string $message = '')
 * @method void assertNotNull($actual, string $message = '')
 * @method void assertNull($actual, string $message = '')
 * @method void assertTrue($condition, string $message = '')
 * @method void assertFalse($condition, string $message = '')
 * @method void assertEquals($expected, $actual, string $message = '')
 * @method void assertNotEquals($expected, $actual, string $message = '')
 * @method void assertSame($expected, $actual, string $message = '')
 * @method void assertInstanceOf(string $expected, $actual, string $message = '')
 */
abstract class TestCase {}
