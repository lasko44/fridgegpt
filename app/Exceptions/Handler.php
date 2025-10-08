<?php
// app/Exceptions/Handler.php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Session\TokenMismatchException;
use Inertia\Inertia;

class Handler extends ExceptionHandler
{
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            return Inertia::render('Home', [
                'show419ErrorModal' => true,
            ])->toResponse($request)->setStatusCode(419);
        }

        return parent::render($request, $exception);
    }

    public function report(Throwable $exception)
    {
        Log::error($exception->getMessage());
        parent::report($exception);
    }
}
