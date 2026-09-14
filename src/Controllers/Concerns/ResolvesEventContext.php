<?php
declare(strict_types=1);

namespace App\Controllers\Concerns;

use App\Services\AuthService;
use App\Services\EventContext;

/**
 * Single auth gate for event-scoped admin actions: logged in, current event
 * resolvable, and an allowed assignment on it. All Father passes canAccess
 * for every event, so no separate admin branch lives in the controllers.
 */
trait ResolvesEventContext
{
    private function sendDenial(bool $json, string $kind): void
    {
        if ($kind === 'login') {
            if ($json) { http_response_code(403); echo json_encode(['error' => 'forbidden']); }
            else { header('Location: ?r=admin_login'); }
            return;
        }
        if ($kind === 'no_event') {
            http_response_code(404);
            echo $json ? json_encode(['error' => 'no_event']) : 'No events';
            return;
        }
        if ($json) { http_response_code(403); echo json_encode(['error' => 'forbidden']); }
        else { AuthService::deny($_SERVER['REQUEST_METHOD'] ?? 'GET'); }
    }

    /** Login-only gate for actions that need no event context. */
    private function requireLogin(bool $json = false): bool
    {
        if (!AuthService::check()) {
            $this->sendDenial($json, 'login');
            return false;
        }
        return true;
    }

    /**
     * Login + current event + canAccess in one gate.
     * @param list<string> $roles assignment roles allowed on the event
     * @return array<string,mixed>|null null after sending the denial response
     */
    private function requireEventContext(\PDO $pdo, array $roles, bool $json = false): ?array
    {
        if (!AuthService::check()) {
            $this->sendDenial($json, 'login');
            return null;
        }
        $event = EventContext::currentEvent($pdo);
        if (!$event) {
            $this->sendDenial($json, 'no_event');
            return null;
        }
        $adminId = (int)($_SESSION['admin_id'] ?? 0);
        if (!EventContext::canAccess($pdo, $adminId, (int)$event['id'], $roles)) {
            $this->sendDenial($json, 'forbidden');
            return null;
        }
        return $event;
    }

    /** Login + canAccess for a specific event id; sends the denial on failure. */
    private function requireEventAccess(\PDO $pdo, int $eventId, array $roles, bool $json = false): bool
    {
        if (!AuthService::check()) {
            $this->sendDenial($json, 'login');
            return false;
        }
        if (!EventContext::canAccess($pdo, (int)($_SESSION['admin_id'] ?? 0), $eventId, $roles)) {
            $this->sendDenial($json, 'forbidden');
            return false;
        }
        return true;
    }
}
