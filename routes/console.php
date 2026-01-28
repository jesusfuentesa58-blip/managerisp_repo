<?php

use App\Models\Router;
use App\Jobs\MikroTik\CheckRouterStatusJob;
use Illuminate\Support\Facades\Schedule;

// Tarea programada: Verificar estado de Routers
Schedule::call(function () {
    $routers = Router::where('is_active', true)->get();

    foreach ($routers as $router) {
        CheckRouterStatusJob::dispatch($router);
    }
})->everyFiveMinutes(); // Puedes cambiar a ->everyMinute() si quieres tiempo real