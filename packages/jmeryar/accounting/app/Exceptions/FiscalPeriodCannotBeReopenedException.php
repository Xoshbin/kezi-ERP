<?php

declare(strict_types=1);

namespace Jmeryar\Accounting\Exceptions;

use Exception;

/**
 * Thrown when a fiscal period cannot be reopened.
 *
 * Common causes include:
 * - Period is not in 'closed' state
 * - Parent fiscal year is closed
 */
final class FiscalPeriodCannotBeReopenedException extends Exception {}
