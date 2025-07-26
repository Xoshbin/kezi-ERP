<?php

namespace App\Exceptions;

use Exception;
use Throwable;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse; // Alias for consistency

class UpdateNotAllowedException extends Exception
{
    /**
     * Create a new update not allowed exception instance.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    public function __construct(string $message = "Modification of this financial record is not allowed due to accounting principles.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @return SymfonyResponse // Changed the return type hint here
     */
    public function render($request): SymfonyResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error_code' => 'ACCOUNTING_UPDATE_PROHIBITED',
            ], 403); // 403 Forbidden
        }

        return back()->with('error', $this->getMessage());
    }
}