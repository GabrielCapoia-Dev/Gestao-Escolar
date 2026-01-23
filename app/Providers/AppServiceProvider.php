<?php

namespace App\Providers;

use App\Models\ComponenteCurricular;
use App\Policies\ComponenteCurricularPolicy;
use App\Models\DominioEmail;
use App\Models\EquipeGestora;
use App\Models\Escola;
use App\Models\Permission;
use App\Models\Professor;
use App\Models\Role;
use App\Models\Serie;
use App\Models\Turma;
use App\Models\User;
use App\Policies\DominioEmailPolicy;
use App\Policies\EscolaPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\RolePolicy;
use App\Policies\SeriePolicy;
use App\Policies\TurmaPolicy;
use App\Policies\UserPolicy;
use App\Policies\ProfessorPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use App\Observers\TurmaObserver;
use App\Policies\EquipeGestoraPolicy;
use App\Services\ProfessorAuthService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(DominioEmail::class, DominioEmailPolicy::class);
        Gate::policy(Escola::class, EscolaPolicy::class);
        Gate::policy(Serie::class, SeriePolicy::class);
        Gate::policy(Turma::class, TurmaPolicy::class);
        Gate::policy(Professor::class, ProfessorPolicy::class);
        Gate::policy(ComponenteCurricular::class, ComponenteCurricularPolicy::class);
        Gate::policy(EquipeGestora::class, EquipeGestoraPolicy::class);

        $this->app->singleton(ProfessorAuthService::class);

        Gate::define('admin-only', function ($user) {
            return $user->hasRole('Admin');
        });

        FilamentAsset::register([
            Css::make('leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'),
            Js::make('leaflet-js',  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'),
        ]);


        Turma::observe(TurmaObserver::class);
    }
}
