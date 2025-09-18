<?php
namespace App\Libraries;

use Config\Services;

class PortalClient
{
    protected string $baseUrl;
    protected string $usersEndpoint;
    private $API_KEY  = '#2025';

    public function __construct()
    {
        $this->baseUrl       = env('PORTAL_URL');
        $this->usersEndpoint = "/api/get-all-sso-users";
    }

    /**
     * Fetch SSO ACTIVE users eligible for LMS.
     * No JWT / API key — plain GET inside your private network.
     * The portal endpoint already filters to ACTIVE + SSO + (LMS|ALL).
     */
    public function fetchUsers(): array
    {
        if ($this->baseUrl === '') {
            throw new \RuntimeException('PortalClient: PORTAL_API_BASE_URL is not set.');
        }

        $client = Services::curlrequest([
            'http_errors' => false,
            'timeout'     => 30,
            'headers'     => ['Accept' => 'application/json'],
        ]);

        $url  = $this->baseUrl . $this->usersEndpoint;
        $resp = $client->get($url, [
            'headers' => [
                'X-API-KEY' => $this->API_KEY
            ],
            'http_errors' => false
        ]);

        if ($resp->getStatusCode() !== 200) {
            throw new \RuntimeException('PortalClient: fetch failed: ' . $resp->getStatusCode() . ' ' . $resp->getBody());
        }

        $data = json_decode((string) $resp->getBody(), true);
        if (!is_array($data)) {
            throw new \RuntimeException('PortalClient: invalid JSON payload.');
        }
        return $data;
    }

    /**
     * Map portal row → LMS row (fields you care about).
     */
    public function mapIncomingUser(array $u): array
    {
        $nz = static fn($v) => $v === null ? '' : (string) $v;

        return [
            'employee_id'     => $nz($u['employee_id'] ?? ''),
            'azure_id'        => $nz($u['azure_id'] ?? ''),
            'name'            => $nz($u['name'] ?? $u['display_name'] ?? ''),
            'email'           => $nz($u['email'] ?? ''),
            'phone'           => $nz($u['phone'] ?? ''),
            'department'      => $nz($u['department'] ?? ''),
            'designation'     => $nz($u['designation'] ?? ''),
            'division'        => $nz($u['division'] ?? ''),
            'user_type'       => 'EMPLOYEE',        // LMS enum
            'login_method'    => 'SSO',             // LMS enum
            'local_user_type' => $nz($u['local_user_type'] ?? 'ADFS'), // LMS NOT NULL enum
            'status'          => strtoupper($nz($u['status'] ?? 'ACTIVE')) === 'INACTIVE' ? 'INACTIVE' : 'ACTIVE',
            // password_hash stays NULL for SSO
        ];
    }
}
