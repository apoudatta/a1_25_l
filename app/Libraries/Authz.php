<?php
namespace App\Libraries;

use CodeIgniter\Database\BaseConnection;
use Config\Database;

class Authz
{
    /** Roles that bypass all permission checks */
    private const BYPASS_ROLE_KEYS = ['SUPERADMIN']; // normalize names (SUPER ADMIN / super_admin) both match

    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /* ---------------- helpers ---------------- */

    /** Normalize role: remove spaces/underscores/dashes and uppercase */
    private function canon(string $role): string
    {
        return strtoupper(preg_replace('/[\s_\-]+/', '', $role));
    }

    /** Fetch roles from session or DB */
    private function rolesFromSessionOrDb(int $userId): array
    {
        $roles = (array) (session('role_list') ?? []);
        if (! $roles) {
            $roles = $this->getRolesFromDb($userId);
            session()->set(['role_list' => $roles]);
        }
        return $roles;
    }

    /** Cache key to bump a user’s authz */
    private function bumpKey(int $userId): string
    {
        return "authz_bump_u{$userId}";
    }

    /** If someone bumped this user after we loaded perms/roles, refresh session */
    public function ensureFresh(int $userId): void
    {
        $cache = \Config\Services::cache();
        $bump  = (int) ($cache->get($this->bumpKey($userId)) ?? 0);
        $last  = (int) (session('perm_last_load') ?? 0);

        if ($bump > $last) {
            $this->primeSession($userId); // reload roles & perms into this session
        }
    }

    /* ---------------- DB fetchers ---------------- */

    protected function getRolesFromDb(int $userId): array
    {
        $sql = "
            SELECT DISTINCT r.name
            FROM user_roles ur
            JOIN roles r ON r.id = ur.role_id
            WHERE ur.user_id = ?
        ";
        return array_column($this->db->query($sql, [$userId])->getResultArray(), 'name');
    }

    protected function getPermsFromDb(int $userId): array
    {
        $sql = "
            SELECT DISTINCT p.name
            FROM user_roles ur
            JOIN role_permissions rp ON rp.role_id = ur.role_id
            JOIN permissions p       ON p.id = rp.permission_id
            WHERE ur.user_id = ?
        ";
        return array_column($this->db->query($sql, [$userId])->getResultArray(), 'name');
    }

    /* ---------------- session priming / clearing ---------------- */

    /** Load latest roles & permissions into current session */
    public function primeSession(int $userId): void
    {
        session()->set([
            'perm_list'      => $this->getPermsFromDb($userId),
            'role_list'      => $this->getRolesFromDb($userId),
            'perm_last_load' => time(),
        ]);
    }

    /** Clear cached roles & perms from current session */
    public function clearSession(): void
    {
        session()->remove(['perm_list', 'role_list', 'perm_last_load']);
    }

    /**
     * Invalidate a user’s cached authz:
     * - If it’s the current session user, clear immediately.
     * - Otherwise, bump a flag so their next request auto-refreshes.
     */
    public function forget(int $userId): void
    {
        $cache = \Config\Services::cache();
        $cache->save($this->bumpKey($userId), time(), 7 * 24 * 3600); // keep bump for 7 days

        if ($userId === (int) session('user_id')) {
            $this->clearSession();
        }
    }

    /* ---------------- checkers ---------------- */

    public function isSuper(int $userId): bool
    {
        $this->ensureFresh($userId);

        foreach ($this->rolesFromSessionOrDb($userId) as $r) {
            if (in_array($this->canon($r), self::BYPASS_ROLE_KEYS, true)) {
                return true;
            }
        }
        return false;
    }

    public function hasRole(int $userId, string $roleName): bool
    {
        $this->ensureFresh($userId);

        $needle = $this->canon($roleName);
        foreach ($this->rolesFromSessionOrDb($userId) as $r) {
            if ($this->canon($r) === $needle) {
                return true;
            }
        }
        return false;
    }

    public function can(int $userId, string $perm): bool
    {
        $this->ensureFresh($userId);

        // Bypass for SUPER ADMIN
        if ($this->isSuper($userId)) {
            return true;
        }

        $perms = (array) (session('perm_list') ?? []);
        if (! $perms) {
            $perms = $this->getPermsFromDb($userId);
            session()->set(['perm_list' => $perms, 'perm_last_load' => time()]);
        }
        return in_array($perm, $perms, true);
    }
}
