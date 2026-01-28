<?php

namespace App\Filament\Resources\PlanResource\Pages;

use App\Filament\Resources\PlanResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use App\Services\MikroTik\MikroTikService;
use Illuminate\Support\Facades\Log;

class CreatePlan extends CreateRecord
{
    protected static string $resource = PlanResource::class;

    /**
     * Después de crear el Plan en la BD, sincronizamos perfiles en los routers seleccionados.
     */
    protected function afterCreate(): void
    {
        // NO llamar a parent::afterCreate() — la clase padre no lo define.

        // $this->record es el modelo Plan recién creado
        $plan = $this->record;

        $this->syncProfilesToSelectedRouters($plan);
    }

    /**
     * Sincroniza el profile PPP en los routers seleccionados en el plan.
     *
     * @param \App\Models\Plan $plan
     */
    protected function syncProfilesToSelectedRouters($plan): void
    {
        if (empty($plan->pppoe_profile_name)) {
            return;
        }

        $rateLimit = $this->buildRateLimit($plan);

        // Usamos la relación ya cargada para evitar queries extra
        $routerIds = $plan->routers->pluck('id')->toArray();

        foreach ($routerIds as $routerId) {
            $router = \App\Models\Router::find($routerId);

            if (!$router) {
                Notification::make()
                    ->title('Router no encontrado')
                    ->body("Router ID {$routerId} no encontrado. Se omitirá.")
                    ->warning()
                    ->send();
                continue;
            }

            if (!$router->is_active) {
                Notification::make()
                    ->title('Router inactivo')
                    ->body("Router '{$router->name}' está inactivo. Se omitirá.")
                    ->warning()
                    ->send();
                continue;
            }

            if (empty($router->lan_ip)) {
                Notification::make()
                    ->title('Falta IP LAN en Router')
                    ->body("El router '{$router->name}' no tiene 'IP Interfaz LAN' configurada. Debes configurarla antes.")
                    ->danger()
                    ->persistent()
                    ->send();
                continue;
            }

            try {
                $mk = new MikroTikService($router);

                // Método que debes tener en MikroTikService (usa lan_ip internamente)
                $mk->createPlanProfileForRouter($plan->pppoe_profile_name, $rateLimit);

                Notification::make()
                    ->title('Profile sincronizado')
                    ->body("Profile '{$plan->pppoe_profile_name}' creado/actualizado en router '{$router->name}'.")
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                Log::warning("Fallo sincronizando profile '{$plan->pppoe_profile_name}' en router '{$router->name}': " . $e->getMessage(), [
                    'plan_id' => $plan->id,
                    'router_id' => $router->id,
                ]);

                Notification::make()
                    ->title('Error sincronizando router')
                    ->body("{$router->name}: " . $e->getMessage())
                    ->danger()
                    ->persistent()
                    ->send();
            }
        }
    }

    /**
     * Normaliza y construye rate-limit esperado por MikroTik.
     *
     * @param \App\Models\Plan $plan
     * @return string
     */
    protected function buildRateLimit($plan): string
    {
        $download = (string)($plan->download_speed ?? '');
        $upload = (string)($plan->upload_speed ?? '');

        $normalize = function (string $v) {
            $v = trim($v);
            if ($v === '') return '';
            if (preg_match('/[a-zA-Z]$/', $v)) return $v;
            if (str_contains($v, '/')) return $v;
            return $v . 'M';
        };

        $d = $normalize($download);
        $u = $normalize($upload);

        if ($d !== '' && $u !== '') {
            return "{$d}/{$u}";
        }

        return $d ?: $u ?: 'unlimited';
    }
}
