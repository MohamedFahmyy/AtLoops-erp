<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Auth;
use App\Core\Response;

class GuestMiddleware
{
    public function handle(): void
    {
        if (Auth::check()) {
            Response::redirect(url('dashboard'));
        }
    }
}
