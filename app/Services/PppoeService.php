<?php

namespace App\Services;

use App\Models\Package;
use App\Models\Router;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * PPPoE subscriber lifecycle: credential provisioning on RouterOS
 * (/ppp/secret + /ppp/profile rate limits), session sync, and disconnect.
 */
class PppoeService
{
    public function __construct(private MikroTikService $mikroTik) {}

    /**
     * Provision or update PPPoE credentials for a subscriber.
     * Generates pppoe_username/password on first call; stores them encrypted
     * on the user and pushes the secret to the package's router.
     */
    public function provision(User $user, Package $package, ?string $password = null): array
    {
        $router = $package->router;
        if (!$router) {
            return ['success' => false, 'message' => 'Package has no router assigned'];
        }

        if (empty($user->pppoe_username)) {
            $user->pppoe_username = $this->generateUsername($user);
        }

        $plainPassword = $password ?? $user->pppoe_password ?? Str::random(12);

        $result = $this->mikroTik->provisionPppoeSecret(
            $router,
            $user->pppoe_username,
            $plainPassword,
            $package
        );

        if (!$result['success']) {
            return $result;
        }

        $user->update([
            'connection_type' => 'pppoe',
            'package_id' => $package->id,
            'router_id' => $router->id,
            'pppoe_password' => $plainPassword,
        ]);

        return [
            'success' => true,
            'message' => 'PPPoE account provisioned',
            'username' => $user->pppoe_username,
            'password' => $plainPassword,
        ];
    }

    /**
     * Suspend service: disable the secret (keeps it for easy re-enable).
     */
    public function suspend(User $user): bool
    {
        return $this->setSecretDisabled($user, true);
    }

    /**
     * Resume service: re-enable the secret.
     */
    public function resume(User $user): bool
    {
        return $this->setSecretDisabled($user, false);
    }

    /**
     * Terminate: disconnect active session and remove the secret entirely.
     */
    public function disconnect(User $user): bool
    {
        $router = $user->package?->router ?? $user->router;
        if (!$router || !$user->pppoe_username) {
            return false;
        }

        // Kick active session first so the router drops the connection
        try {
            $client = $this->mikroTik->connectToRouter($router);
            if ($client) {
                $active = $client->query(
                    (new \RouterOS\Query('/ppp/active/print'))->where('name', $user->pppoe_username)
                )->read();

                foreach ($active as $conn) {
                    if (isset($conn['.id'])) {
                        $client->query(
                            (new \RouterOS\Query('/ppp/active/remove'))->equal('.id', $conn['.id'])
                        )->read();
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('PPPoE active-session kick failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        $removed = $this->mikroTik->removePppoeSecret($router, $user->pppoe_username);

        if ($removed) {
            $user->update(['pppoe_username' => null, 'pppoe_password' => null]);
        }

        return $removed;
    }

    /**
     * Sync PPPoE sessions for a router (called from the session-sync job).
     */
    public function syncRouter(Router $router): array
    {
        return $this->mikroTik->syncPppoeSessions($router);
    }

    private function setSecretDisabled(User $user, bool $disabled): bool
    {
        $router = $user->package?->router ?? $user->router;
        if (!$router || !$user->pppoe_username) {
            return false;
        }

        try {
            $client = $this->mikroTik->connectToRouter($router);
            if (!$client) {
                return false;
            }

            $secrets = $client->query(
                (new \RouterOS\Query('/ppp/secret/print'))->where('name', $user->pppoe_username)
            )->read();

            foreach ($secrets as $secret) {
                if (isset($secret['.id'])) {
                    $client->query(
                        (new \RouterOS\Query('/ppp/secret/set'))
                            ->equal('.id', $secret['.id'])
                            ->equal('disabled', $disabled ? 'yes' : 'no')
                    )->read();
                }
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('PPPoE secret disable/enable failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function generateUsername(User $user): string
    {
        $base = Str::slug($user->name !== '' ? $user->name : ('user' . $user->id), '');
        $base = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $base));

        if ($base === '') {
            $base = 'user' . $user->id;
        }

        // Ensure uniqueness against existing pppoe usernames
        $username = $base;
        $i = 1;
        while (User::where('pppoe_username', $username)->exists()) {
            $username = $base . $i;
            $i++;
        }

        return $username;
    }
}
