<?php

namespace App\Services\MikroTik;

use App\Models\Router;
use Illuminate\Support\Facades\Log;

use ISP\Mikrotik\Connection as ISPConnection;
use ISP\Mikrotik\MikrotikClient;
use ISP\Mikrotik\Enums\RouterStatus;
use ISP\Mikrotik\Commands\Ppp\Profile\CreateProfile;

// Exceptions SDK
use ISP\Mikrotik\Exceptions\{
    RouterOfflineException,
    CommandFailedException,
    MikrotikException
};

// Commands
use ISP\Mikrotik\Commands\Ppp\{
    ListSecrets,
    CreateSecret,
    UpdateSecret,
    RemoveSecret
};

use RouterOS\Exceptions\{
    BadCredentialsException,
    ConnectException
};

class MikroTikService
{
    private Router $router;
    private ?MikrotikClient $client = null;
    private ?ISPConnection $connection = null;

    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    // ==========================================================
    // CORE: CONEXIÓN SEGURA Y CONTROLADA
    // ==========================================================

    private function buildConnection(): ISPConnection
    {
        $user = $this->router->username ?? $this->router->user;
        $pass = $this->router->password;
        $host = $this->router->host;
        $port = (int)($this->router->port ?? 8728);

        return new ISPConnection(
            host: $host,
            user: $user,
            password: $pass,
            port: $port
        );
    }

    /**
     * Ejecuta cualquier operación con conexión garantizada y cerrada.
     */
    private function withConnection(callable $callback)
    {
        try {
            $this->connection = $this->buildConnection();
            return $callback($this->connection);

        } catch (\Throwable $e) {
            throw $e;

        } finally {
            // IMPORTANTE: no llamar disconnect()
            $this->connection = null;
            $this->client = null;
        }
    }

    private function client(): MikrotikClient
    {
        if (!$this->client) {
            $this->client = new MikrotikClient($this->connection);
        }
        return $this->client;
    }

    // ==========================================================
    // HEALTH / STATUS
    // ==========================================================

    public function getHealth()
    {
        return $this->withConnection(function () {
            return $this->client()->system()->run();
        });
    }

    public function determineRouterStateFromDto(object $dto): string
    {
        if (!isset($dto->status) || !$dto->status instanceof RouterStatus) {
            return Router::STATUS_OFFLINE;
        }

        return match ($dto->status) {
            RouterStatus::OK       => Router::STATUS_ONLINE,
            RouterStatus::DEGRADED => Router::STATUS_DEGRADED,
            RouterStatus::OFFLINE  => Router::STATUS_OFFLINE,
        };
    }

    public function testConnection(): bool
    {
        $this->getHealth();
        return true;
    }

    // ==========================================================
    // PROVISIONING
    // ==========================================================

    public function provisionRouter(): bool
    {
        return $this->withConnection(function () {
            return (bool)$this->client()->provision();
        });
    }

    // ==========================================================
    // PPP PROFILES
    // ==========================================================

    public function ensurePppProfile(string $name, string $rate): bool
    {
        return $this->withConnection(function () use ($name, $rate) {
            $this->client()->ppp()->profiles()->create($name, $rate);
            return true;
        });
    }

    public function getProfile(string $name): ?array
    {
        return $this->withConnection(function () use ($name) {
            return $this->client()->ppp()->profiles()->getProfile($name);
        });
    }

    public function updateProfile(string $id, array $data): bool
    {
        return $this->withConnection(function () use ($id, $data) {
            return $this->client()->ppp()->profiles()->updateProfile($id, $data);
        });
    }

    public function syncPlanProfile(string $name, string $rateLimit): void
    {
        $profile = $this->getProfile($name);

        if ($profile) {
            $this->updateProfile($profile['.id'], [
                'rate-limit' => $rateLimit,
                'comment' => 'Actualizado desde panel ' . now()->format('d/m/Y H:i'),
            ]);
        } else {
            $this->ensurePppProfile($name, $rateLimit);
        }
    }

    // ==========================================================
    // PPP SECRETS (COMMANDS)
    // ==========================================================

    public function checkSecretExists(string $username): bool
    {
        return $this->withConnection(function () use ($username) {
            $cmd = new ListSecrets($this->connection);
            foreach ($cmd->execute() as $user) {
                if ($user->name === $username) {
                    return true;
                }
            }
            return false;
        });
    }

    public function createPppSecret(
        string $user,
        string $password,
        string $profile,
        ?string $remoteAddress = null,
        string $comment = ''
    ): void {
        $this->withConnection(function () use ($user, $password, $profile, $remoteAddress, $comment) {
            (new CreateSecret(
                connection:    $this->connection,
                name:          $user,
                password:      $password,
                profile:       $profile,
                service:       'pppoe',
                remoteAddress: $remoteAddress,
                localAddress:  null,
                comment:       $comment,
                disabled:      false
            ))->execute();
        });
    }

    public function updatePppSecret(string $user, array $data): void
    {
        if (isset($data['profile'])) {
            if (!$this->getProfile($data['profile'])) {
                throw new \RuntimeException("Perfil '{$data['profile']}' no existe en el router");
            }
        }

        $this->withConnection(function () use ($user, $data) {
            (new UpdateSecret($this->connection, $user, $data))->execute();
        });
    }

    public function deletePppSecret(string $user): void
    {
        try {
            $this->withConnection(function () use ($user) {
                (new RemoveSecret($this->connection, $user))->execute();
            });
        } catch (\Throwable $e) {
            // NO rompemos migraciones ni procesos
            Log::warning("No se pudo eliminar secret {$user}: " . $e->getMessage());
        }
    }

    /**
     * Crea o actualiza el profile PPP en el router seleccionado.
     * Siempre usa $router->lan_ip como local-address.
     *
     * @param string $name      Nombre del profile en MikroTik
     * @param string $rateLimit Rate-limit (ej: "100M/10M")
     * @return bool
     *
     * @throws \RuntimeException si hay un fallo crítico (conexion, api, etc.)
     */
    public function createPlanProfileForRouter(string $name, string $rateLimit): bool
    {
        // Validación temprana
        if (empty($this->router->lan_ip)) {
            throw new \RuntimeException("Router '{$this->router->name}' no tiene lan_ip configurada.");
        }

        return $this->withConnection(function () use ($name, $rateLimit) {
            try {
                // 1) Intentar leer el profile mediante el client (evita reconsultas de Connection)
                $profilesApi = $this->client()->ppp()->profiles();

                // getProfile debe devolver null o array con '.id'
                $existing = null;
                try {
                    $existing = $profilesApi->getProfile($name);
                } catch (\Throwable $e) {
                    // Si getProfile falla por cualquier razón, lo registramos y seguimos:
                    // no queremos que una excepción aquí bloquee el flujo de creación.
                    \Illuminate\Support\Facades\Log::warning("getProfile fallo: " . $e->getMessage(), [
                        'router' => $this->router->id ?? null,
                        'profile' => $name,
                    ]);
                    $existing = null;
                }

                // Datos que queremos asegurar/actualizar
                $data = [
                    'rate-limit'    => $rateLimit,
                    'comment'       => 'Sincronizado desde Panel ' . now()->format('d/m/Y H:i'),
                    // Nota: incluimos local-address también en update data
                    'local-address' => $this->router->lan_ip,
                ];

                if (!empty($existing) && isset($existing['.id'])) {
                    // 2) Si existe, actualizamos usando la API client
                    $updated = $profilesApi->updateProfile($existing['.id'], $data);

                    // updateProfile podría devolver bool o similar; forzamos respuesta booleana
                    return (bool)$updated;
                }

                // 3) Si NO existe, creamos usando el comando CreateProfile (soporta local-address)
                $cmd = new CreateProfile(
                    $this->connection,
                    $name,
                    $rateLimit,
                    $this->router->lan_ip,
                    null // remote pool si en el futuro se requiere
                );

                return (bool)$cmd->execute();

            } catch (\Throwable $e) {
                // Normalizamos la excepción para el caller
                \Illuminate\Support\Facades\Log::error("Error creando/actualizando profile '{$name}' en router '{$this->router->name}': " . $e->getMessage(), [
                    'router_id' => $this->router->id ?? null,
                    'exception' => $e::class,
                ]);

                throw new \RuntimeException("Error sincronizando profile '{$name}' en router '{$this->router->name}': " . $e->getMessage(), 0, $e);
            }
        });
    }
}
