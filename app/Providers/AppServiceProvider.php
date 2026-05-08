<?php

namespace App\Providers;

use App\Models\ClienteUser;
use App\Models\Encuesta;
use App\Models\Factura;
use App\Models\Pedido;
use App\Models\ProveedorUser;
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
        Pedido::created($invalidarDashboard);
        Pedido::updated($invalidarDashboard);
        Pedido::deleted($invalidarDashboard);

        ClienteUser::created($invalidarDashboard);
        ClienteUser::updated($invalidarDashboard);
        ClienteUser::deleted($invalidarDashboard);

        ProveedorUser::created($invalidarDashboard);
        ProveedorUser::updated($invalidarDashboard);

        Factura::created($invalidarDashboard);
        Factura::updated($invalidarDashboard);

        Encuesta::created($invalidarDashboard);
    }
}
