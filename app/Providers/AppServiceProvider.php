<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Libsql\Laravel\Database\LibsqlConnection;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The libsql driver's native Rust runtime panics ("EnterGuard values
        // dropped out of order") if a connection outlives the request that
        // opened it. LibsqlServiceProvider also binds the raw connection as
        // a container singleton (app()->instance(LibsqlConnection::class, ...))
        // on top of Laravel's own connection cache, so both references need
        // to be dropped — and PHP's refcounting GC forced — before the next
        // request, or the driver's internal state outlives its OS process
        // and eventually panics. Scoped to the libsql connection specifically
        // (rather than disconnecting the default connection) so this doesn't
        // tear down an in-memory sqlite database between requests, which
        // otherwise wipes all data mid-test-run.
        $this->app->terminating(function () {
            if (config('database.default') === 'libsql') {
                DB::disconnect('libsql');
            }
        });

        // This is an API-only app with no `password.reset` web route, so point
        // the reset-password notification link at the SPA's own route instead
        // (see ResetPasswordFormSection.jsx, which reads ?token=&email= from it).
        ResetPassword::createUrlUsing(function ($user, string $token) {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/reset-password?token={$token}&email=".urlencode($user->email);
        });

        // Same idea for email verification: the link needs to open the SPA
        // (see ResetPasswordFormSection.jsx sibling ResetPasswordFormSection
        // for the pattern), which then calls the signed
        // /email/verify/{id}/{hash} API route directly with the same
        // expires/signature query string so the "signed" middleware still
        // validates it.
        VerifyEmail::createUrlUsing(function ($notifiable) {
            $id = $notifiable->getKey();
            $hash = sha1($notifiable->getEmailForVerification());

            $signedRoute = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes(60),
                ['id' => $id, 'hash' => $hash]
            );

            $query = parse_url($signedRoute, PHP_URL_QUERY);
            $frontendUrl = rtrim(config('app.frontend_url'), '/');

            return "{$frontendUrl}/verify-email?id={$id}&hash={$hash}&{$query}";
        });
    }
}
