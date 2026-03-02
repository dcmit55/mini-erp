<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof HttpException) {
            return $this->renderHttpException($exception);
        }

        return parent::render($request, $exception);
    }

    /**
     * Render the given HttpException.
     */
    protected function renderHttpException(HttpException $e)
    {
        $statusCode = $e->getStatusCode();

        // Use custom error messages for better user experience
        $message = $this->getErrorMessage($statusCode);

        if (view()->exists("errors.{$statusCode}")) {
            return response()->view(
                "errors.{$statusCode}",
                [
                    'exception' => $e,
                    'message' => $message,
                ],
                $statusCode,
                $e->getHeaders(),
            );
        }

        return parent::renderHttpException($e);
    }

    /**
     * Get user-friendly error messages in English.
     */
    private function getErrorMessage($statusCode)
    {
        $messages = [
            // 3xx Redirect Errors
            300 => 'There are multiple options for the resource you requested. Please choose one.',
            301 => 'This page has been permanently moved to a new location.',
            302 => 'This page has been temporarily moved to another location.',
            303 => 'The response to your request can be found at another location.',
            304 => 'Content has not been modified since your last request.',
            305 => 'Access to this resource must be through the specified proxy.',
            306 => 'This status code is no longer used.',
            307 => 'This page has been temporarily moved, keeping the request method.',
            308 => 'This page has been permanently moved, keeping the request method.',
            310 => 'The resource has several available representations. Please choose your preferred format.',

            // 4xx Client Errors
            400 => 'Your request is invalid. Please check your input data.',
            401 => 'You need to log in to access this page.',
            403 => 'Sorry, you do not have permission to access this page.',
            404 => 'The page you are looking for was not found. Please check the URL or return to the homepage.',
            405 => 'The HTTP method used is not allowed for this page.',
            406 => 'The server cannot generate a response acceptable to your browser.',
            408 => 'Your request took too long. Please try again.',
            409 => 'There is a conflict with the current state. Please refresh and try again.',
            410 => 'The page you are looking for is no longer available.',
            411 => 'Your request must include a valid content length.',
            413 => 'The data you sent is too large. Please reduce the file or data size.',
            414 => 'The URL you entered is too long.',
            415 => 'The file or media format you sent is not supported.',
            422 => 'The data you sent could not be processed. Please check again.',
            429 => 'Too many requests. Please wait a moment before trying again.',

            // 5xx Server Errors
            500 => 'A server error occurred. Our technical team is working to resolve this issue.',
            501 => 'The feature you requested is not available yet. Please contact the administrator.',
            502 => 'The server encountered a communication problem. Please try again later.',
            503 => 'The service is under maintenance. Please try again later.',
            504 => 'The server took too long to respond. Please try again.',
            505 => 'The HTTP version used is not supported by the server.',
            507 => 'The server is out of storage space. Please contact the administrator.',
            508 => 'The server detected an infinite loop while processing your request.',
            511 => 'Network authentication is required to access this service.',
        ];

        return $messages[$statusCode] ?? 'An unknown error has occurred.';
    }
}
