<?php

namespace App\Filament\Resources\RouterResource\Pages;

use App\Filament\Resources\RouterResource;
use App\Models\Router;
use App\Services\MikroTik\MikroTikService;
use App\Models\RouterProvision;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;

class CreateRouter extends CreateRecord
{
    protected static string $resource = RouterResource::class;

    protected function handleRecordCreation(array $data): Router
    {
        $router = new Router($data);

        // 1) Test conexión ANTES de guardar
        try {
            $service = new MikroTikService($router);
            $service->testConnection();
        } catch (\RuntimeException $e) {
            Notification::make()
                ->title('No se pudo conectar al router')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }

        // 2) Guardar + provisionar (si aplica)
        DB::beginTransaction();
        try {
            $router->save();

            if (
                ($data['suspension_method'] ?? null) === 'address-list' &&
                !empty($data['provision_address_list']) &&
                !$router->provisioned_address_list
            ) {
                try {
                    $service->provisionRouter();

                    RouterProvision::create([
                        'router_id' => $router->id,
                        'method' => 'address-list',
                        'status' => 'success',
                        'message' => 'Provision ejecutada correctamente',
                    ]);

                    $router->update([
                        'provisioned_address_list' => true,
                        'provisioned_at' => now(),
                    ]);

                } catch (\Throwable $e) {
                    RouterProvision::create([
                        'router_id' => $router->id,
                        'method' => 'address-list',
                        'status' => 'failed',
                        'message' => $e->getMessage(),
                    ]);

                    Notification::make()
                        ->title('El router se creó, pero la provisión falló')
                        ->body($e->getMessage())
                        ->warning()
                        ->send();
                }
            }

            DB::commit();

            Notification::make()
                ->title('Router creado correctamente')
                ->success()
                ->send();

            return $router;

        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

}
