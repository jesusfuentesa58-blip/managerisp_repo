<?php

namespace App\Filament\Resources\CustomerResource\Pages;

use App\Filament\Resources\CustomerResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

use App\Models\Service;
use App\Models\Router;
use App\Models\Plan;
use App\Services\MikroTik\MikroTikService;

class EditCustomer extends EditRecord
{
    protected static string $resource = CustomerResource::class;

    /**
     * Snapshot del estado ANTES de guardar (DB real)
     */
    protected array $oldServices = [];

    protected function getHeaderActions(): array
    {
        return [];
    }

    // ==========================================================
    // 1. CAPTURAR ESTADO ANTES DEL SAVE
    // ==========================================================

    protected function beforeSave(): void
    {
        $this->oldServices = $this->record
            ->services
            ->map(fn ($s) => $s->toArray())
            ->toArray();
    }

    // ==========================================================
    // 2. SINCRONIZAR DESPUÉS DEL SAVE (DATOS DEFINITIVOS)
    // ==========================================================

    protected function afterSave(): void
    {
        $services = $this->record->fresh()->services;

        foreach ($services as $service) {
            $old = collect($this->oldServices)->firstWhere('id', $service->id);

            if ($old) {
                $this->syncServiceToMikrotik($old, $service);
            }
        }
    }

    // ==========================================================
    // 3. LÓGICA FINAL DE SINCRONIZACIÓN
    // ==========================================================

    private function syncServiceToMikrotik(array $old, Service $new): void
    {
        $routerChanged = $old['router_id'] != $new->router_id;
        $userChanged   = $old['pppoe_user'] !== $new->pppoe_user;

        $updates = [];

        if ($old['pppoe_password'] !== $new->pppoe_password) {
            $updates['password'] = $new->pppoe_password;
        }

        if ($old['plan_id'] !== $new->plan_id) {
            $updates['profile'] = $new->plan->pppoe_profile_name;
        }

        if ($old['remote_address'] !== $new->remote_address) {
            $updates['remote-address'] = $new->remote_address;
        }

        $oldDisabled = $old['status'] === 'suspendido';
        $newDisabled = $new->status === 'suspendido';

        if ($oldDisabled !== $newDisabled) {
            $updates['disabled'] = $newDisabled;
        }

        // Si no hay cambios, no hacemos nada
        if (!$routerChanged && !$userChanged && empty($updates)) {
            return;
        }

        try {

            // ======================================================
            // MIGRACIÓN TOTAL
            // ======================================================
            if ($routerChanged || $userChanged) {

                // borrar del router viejo
                if ($old['router_id']) {
                    try {
                        $mkOld = new MikroTikService(Router::find($old['router_id']));
                        $mkOld->deletePppSecret($old['pppoe_user']);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Advertencia')
                            ->body('No se pudo borrar del router anterior.')
                            ->warning()
                            ->send();
                    }
                }

                // crear en router nuevo
                $mkNew = new MikroTikService($new->router);
                $mkNew->createPppSecret(
                    user: $new->pppoe_user,
                    password: $new->pppoe_password,
                    profile: $new->plan->pppoe_profile_name,
                    remoteAddress: $new->remote_address,
                    comment: $this->record->name,
                    disabled: $new->status === 'suspendido'
                );
            }

            // ======================================================
            // UPDATE PARCIAL
            // ======================================================
            elseif (!empty($updates)) {
                $mk = new MikroTikService($new->router);
                $mk->updatePppSecret($new->pppoe_user, $updates);
            }

        } catch (\Throwable $e) {
            Notification::make()
                ->title('Error de sincronización')
                ->body('El router rechazó los cambios: ' . $e->getMessage())
                ->danger()
                ->persistent()
                ->send();

            // ⚠️ DB ya fue guardada, pero informamos claramente
            // Si quieres rollback real → hay que mover esto a Jobs con transacción
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
