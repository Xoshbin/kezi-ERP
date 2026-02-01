<?php

declare(strict_types=1);

namespace Jmeryar\Accounting\Exceptions;

use Exception;

/**
 * Thrown when a fiscal period cannot be closed.
 *
 * Common causes include:
 * - Period is not in 'open' state
 * - Parent fiscal year is closed
 * - Draft journal entries exist in the period
 */
final class FiscalPeriodNotReadyToCloseException extends Exception {}
