<?php

namespace App\Exceptions;

use Exception;
use Throwable;
// We will use the Symfony Response class as it is the foundational
// parent for Laravel's HTTP responses (Illuminate\Http\Response, JsonResponse, RedirectResponse).
// This makes the type hint broader and more compatible.
use Symfony\Component\HttpFoundation\Response as SymfonyResponse; // Alias to avoid any potential conflicts

class DeletionNotAllowedException extends Exception
{
    /**
     * Create a new deletion not allowed exception instance.
     *
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     * @return void
     */
    public function __construct(string $message = "Deletion of this financial record is not allowed due to accounting principles.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception into an HTTP response.
     * This method is crucial for how your application presents errors to the user,
     * whether they are using a web browser or an API client.
     *
     * @param \Illuminate\Http\Request $request
     * @return SymfonyResponse // Changed the return type hint to the more general SymfonyResponse
     */
    public function render($request): SymfonyResponse
    {
        // If the request expects a JSON response (common for APIs), we return a JSON error.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error_code' => 'ACCOUNTING_DELETION_PROHIBITED',
            ], 403); // 403 Forbidden indicates the client does not have permission to access the resource or perform the action.
        }

        // For traditional web requests, we redirect the user back to the previous page
        // with an error message stored in the session.
        return back()->with('error', $this->getMessage());
    }
}