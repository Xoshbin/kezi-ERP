<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;

class BalanceSheetNotBalancedException extends Exception
{
    /**
     * Create a new balance sheet not balanced exception instance.
     *
     * @return void
     */
    public function __construct(string $message = 'The Balance Sheet does not balance. Assets must equal Liabilities plus Equity.', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param  Request  $request
     */
    public function render($request): SymfonyResponse
    {
        // If the request expects a JSON response (common for APIs), we return a JSON error.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error_code' => 'BALANCE_SHEET_NOT_BALANCED',
            ], 500); // 500 Internal Server Error indicates a calculation error
        }

        // For traditional web requests, we redirect the user back to the previous page
        // with an error message stored in the session.
        return back()->with('error', $this->getMessage());
    }
}
