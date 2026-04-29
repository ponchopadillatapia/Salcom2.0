<?php

namespace App\Providers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Forzar HTTPS en producción
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Invalidar cache del dashboard admin cuando cambian datos clave
        $invalidarDashboard = function () {
            Cache::forget('admin_dashboard_metrics');
        };

        // Escuchar eventos de modelos que afectan las métricas del dashboard
        \App\Models\Pedido::created($invalidarDashboard);
        \App\Models\Pedido::updated($invalidarDashboard);
        \App\Models\Pedido::deleted($invalidarDashboard);

        \App\Models\ClienteUser::created($invalidarDashboard);
        \App\Models\ClienteUser::updated($invalidarDashboard);
        \App\Models\ClienteUser::deleted($invalidarDashboard);

        \App\Models\ProveedorUser::created($invalidarDashboard);
        \App\Models\ProveedorUser::updated($invalidarDashboard);

        \App\Models\Factura::created($invalidarDashboard);
        \App\Models\Factura::updated($invalidarDashboard);

        \App\Models\Encuesta::created($invalidarDashboard);
    }
}
