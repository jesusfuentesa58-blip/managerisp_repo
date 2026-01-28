<?php

namespace App\Jobs\MikroTik;

use App\Models\Router;
use App\Services\MikroTik\MikroTikService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CheckRouterStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected Router $router
    ) {}

    public function handle(): void
    {
        // Si el router no está marcado como activo en el panel, no perdemos tiempo
        if (!$this->router->is_active) {
            return;
        }

        try {
            $service = new MikroTikService($this->router);
            $statusDto = $service->getHealth();
            
            // Usamos tu lógica del Service para determinar el estado
            $nuevoEstado = $service->determineRouterStateFromDto($statusDto);

            $this->router->update([
                'status' => $nuevoEstado,
                'last_checked_at' => now(),
            ]);

        } catch (\Throwable $e) {
            // Si falla, lo marcamos como offline
            $this->router->update([
                'status' => Router::STATUS_OFFLINE,
                'last_checked_at' => now(),
            ]);

            Log::error("Error automático en Router {$this->router->name}: " . $e->getMessage());
        }
    }
}