<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\I18n;
use App\Core\RateLimiter;
use App\Core\Response;
use App\Core\Session;

class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            Response::redirect(url('dashboard'));
        }

        $this->render('auth/login', [
            'pageTitle' => __('auth.login'),
        ], 'layouts/auth');
    }

    public function login(): void
    {
        $email = strtolower(trim((string)($_POST['email'] ?? '')));
        $password = (string)($_POST['password'] ?? '');

        // 1. Validate Form Input
        $this->validate([
            'email' => $email,
            'password' => $password,
        ], [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        // 2. Dual-Key Rate Limiting Protection (Anti-Spraying & Anti-Distributed Brute Force)
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ipKey = 'login_ip_' . md5($ip);
        $accountKey = 'login_account_' . md5($email);

        // A. IP Throttle Check (Max 15 attempts / 15 minutes across all accounts)
        if (RateLimiter::tooManyAttempts($ipKey, 15)) {
            $seconds = RateLimiter::availableIn($ipKey);
            $this->redirect(url('login'), [
                'error' => __('auth.throttle', ['seconds' => $seconds])
            ]);
        }

        // B. Targeted Account Throttle Check (Max 5 attempts / 5 minutes on target account)
        if (RateLimiter::tooManyAttempts($accountKey, 5)) {
            $seconds = RateLimiter::availableIn($accountKey);
            $this->redirect(url('login'), [
                'error' => __('auth.throttle', ['seconds' => $seconds])
            ]);
        }

        // 3. Attempt Authentication
        if (Auth::attempt($email, $password)) {
            RateLimiter::clear($ipKey);
            RateLimiter::clear($accountKey);
            $this->redirect(url('dashboard'), [
                'success' => __('common.welcome', ['name' => Auth::user()['name']])
            ]);
        }

        // 4. Failed Attempt: increment both IP and Account counters
        RateLimiter::hit($ipKey, 900);
        RateLimiter::hit($accountKey, 300);
        $this->redirect(url('login'), [
            'error' => __('auth.failed')
        ]);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect(url('login'), [
            'success' => __('auth.logged_out')
        ]);
    }

    public function switchLocale(): void
    {
        $locale = $_GET['lang'] ?? 'ar';
        I18n::setLocale($locale);

        $referer = $_SERVER['HTTP_REFERER'] ?? url('dashboard');
        Response::redirect($referer);
    }
}
