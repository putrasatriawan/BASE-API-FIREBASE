<?php

namespace App\Exceptions;

use App\Services\FormatService;
use App\Services\GeneralServices;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\QueryException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;


use App\Http\ResponseFormatter;
use Kreait\Firebase\Exception\AuthApiExceptionConverter;

class Handler extends ExceptionHandler
{
    private $message;

    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    private $telegramService;



    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    private function logTheErrors(Throwable $exception)
    {
        \Log::error("\n" .
            "--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------" . "\n" .
            "Message : " . $exception->getMessage() . "\n" .
            "File    : " . $exception->getFile() . "\n" .
            "Line    : " . $exception->getLine() . "\n" .
            "--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------" . "\n" .
            "\n");
    }



    public function render($request, Throwable|Exception $exception)
    {
        $unknown = "Terjadi kesalahan, silahkan coba lagi nanti";
        $env = config('app.env');
        $this->logTheErrors($exception);
        $message = FormatService::capitalizeFirstWord($exception->getMessage());

        if ($exception instanceof PublicException) {
            return ResponseFormatter::error(null, $message, $exception->getCode());
        }

        if ($exception instanceof AuthApiExceptionConverter) {
            return ResponseFormatter::error(null, $message, 401);
        }

        if ($exception instanceof \Spatie\Permission\Exceptions\UnauthorizedException) {
            return ResponseFormatter::error(null, 'Unauthorized', 401);
        }

        if ($exception instanceof NotFoundHttpException) {
            return ResponseFormatter::error(
                null,
                'Endpoint tidak ditemukan',
                404
            );
        }

        if ($exception instanceof MethodNotAllowedHttpException) {
            return ResponseFormatter::error(
                null,
                'Method tidak diizinkan',
                405
            );
        }

        if ($exception instanceof ModelNotFoundException) {
            return ResponseFormatter::error(null, 'hasil yang di cari tidak ditemukan', 404);
        }

        if ($exception instanceof AuthenticationException) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return ResponseFormatter::error(
                    null,
                    'Anda harus login terlebih dahulu untuk mengakses fitur ini. Silakan login dan gunakan token yang valid.',
                    401,
                    ['authentication_required' => true]
                );
            }
            return ResponseFormatter::error(null, 'Unauthorized', 401);
        }

        if ($exception instanceof AccessDeniedHttpException || $exception instanceof AuthorizationException) {
            return ResponseFormatter::error(null, "Terjadi Kesalahan", 403);
        }

        if ($exception instanceof QueryException) {
            return ($env === 'production') ? ResponseFormatter::error(null, $unknown, 500)
                : ResponseFormatter::error(null, $exception->getMessage(), 500);
        }

        if ($exception instanceof RelationNotFoundException) {
            return ($env === 'production') ? ResponseFormatter::error(null, $unknown, 500)
                : ResponseFormatter::error(null, 'something went wrong', 500, GeneralServices::errorResponse($exception, $request));
        }

        if ($exception instanceof ValidationException) {
            $validationErrors = $exception->validator->errors()->getMessages();
            $errors = [];
            foreach ($validationErrors as $key => $data) {
                array_push($errors, [$key => $data]);
            }

            return ResponseFormatter::error(null, $exception->validator->errors()->first(), 422, $errors);
        }

        if ($exception) {
            $errCode = $exception?->getCode() ?? 500;

            // Ensure valid HTTP status code
            if ($errCode < 100 || $errCode > 599) {
                $errCode = 500;
            }

            return ($env === 'production') ? ResponseFormatter::error(null, $unknown, 500)
                : ResponseFormatter::error(null, $exception->getMessage(), $errCode, GeneralServices::errorResponse($exception, $request));
        }

        return parent::render($request, $exception);
    }
}
