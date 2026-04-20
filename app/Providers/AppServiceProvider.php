<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\Program;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureRouteBindings();
        $this->configureRateLimiters();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    /**
     * Configure authorization gates.
     */
    protected function configureAuthorization(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole(Role::SuperAdmin) ? true : null;
        });
    }

    /**
     * Configure route model bindings scoped for public views.
     */
    protected function configureRouteBindings(): void
    {
        Route::bind('publicProgram', fn (string $slug): Program => Program::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail()
        );
    }

    /**
     * Configure named rate limiters for public-facing actions.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('contact', fn (Request $request) => Limit::perHour(3)->by($request->ip()));
    }
}
