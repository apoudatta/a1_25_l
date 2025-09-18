<?php
namespace App\Commands;

use App\Libraries\PortalClient;
use App\Models\UserModel;
use App\Models\UserRoleModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class SyncPortalUsers extends BaseCommand
{
    protected $group       = 'LMS';
    protected $name        = 'portal:sync-users';
    protected $description = 'Sync SSO ACTIVE users from bKash Portal → LMS (insert/update/deactivate).';
    protected $usage       = 'portal:sync-users [--dry-run]';
    protected $options     = [
        '--dry-run' => 'Show actions without modifying the DB',
    ];

    public function run(array $params)
    {
        $dryRun = in_array('--dry-run', $params, true);

        $client = new PortalClient();
        try {
            $portalUsers = $client->fetchUsers(); // already filtered by portal API
        } catch (\Throwable $e) {
            CLI::error('Portal fetch failed: ' . $e->getMessage());
            return EXIT_ERROR;
        }

        // index portal users by email (lowercased)
        $portalByEmail = [];
        foreach ($portalUsers as $u) {
            $email = strtolower(trim((string) ($u['email'] ?? '')));
            if ($email === '') continue;
            $portalByEmail[$email] = $u;
        }

        $userModel = new UserModel();

        // Load all LMS SSO users (all statuses)
        $lmsRows = $userModel->where('login_method', 'SSO')->findAll();
        $lmsByEmail = [];
        foreach ($lmsRows as $row) {
            $lmsByEmail[strtolower((string) $row['email'])] = $row;
        }

        $toInsert = [];
        $toUpdate = [];
        $toDeactivateEmails = [];

        // INSERT / UPDATE
        foreach ($portalByEmail as $email => $pu) {
            $mapped = $client->mapIncomingUser($pu);
            // ensure email present in mapped data
            $mapped['email'] = $email;

            if (!isset($lmsByEmail[$email])) {
                $toInsert[] = $mapped;

            } else {
                $existing = $lmsByEmail[$email];
                if ($userModel->needsUpdate($existing, $mapped)) {
                    $toUpdate[] = array_merge(['id' => $existing['id']], $mapped);
                }
            }
        }

        // DEACTIVATE LMS users that are ACTIVE+SSO but not in portal list
        foreach ($lmsByEmail as $email => $lms) {
            $isActiveSSO = (strtoupper((string)$lms['status']) === 'ACTIVE') && ((string)$lms['login_method'] === 'SSO');
            if (!$isActiveSSO) continue;
            if (!isset($portalByEmail[$email])) {
                $toDeactivateEmails[] = $email;
            }
        }

        // Plan summary
        CLI::write('Portal (eligible) users: ' . count($portalByEmail));
        CLI::write('LMS SSO users: ' . count($lmsByEmail));
        CLI::write('To INSERT: ' . count($toInsert));
        CLI::write('To UPDATE: ' . count($toUpdate));
        CLI::write('To DEACTIVATE: ' . count($toDeactivateEmails));
        if ($dryRun) {
            CLI::write('DRY RUN: No DB changes.', 'yellow');
            return EXIT_SUCCESS;
        }

        // Apply changes in a transaction
        $db = Database::connect();
        $db->transStart();

        try {
            if (! empty($toInsert)) {
                // 1) Insert new users
                $userModel->insertBatch($toInsert, 100);
            
                // 2) Fetch their IDs by email (emails were lowercased earlier)
                $emailsInserted = array_column($toInsert, 'email');
                $newUsers = $userModel
                    ->select('id, email')
                    ->whereIn('email', $emailsInserted)
                    ->findAll();
            
                // 3) Give them the role (role_id = 5)
                $roleRows = [];
                foreach ($newUsers as $nu) {
                    $roleRows[] = [
                        'user_id' => (int) $nu['id'],
                        'role_id' => 1, // Employee (or your desired role)
                    ];
                }
                
                if (! empty($roleRows)) {
                    // Avoid duplicate key errors if rerun; requires unique index on (user_id, role_id)
                    $db->table('user_roles')->ignore(true)->insertBatch($roleRows, 100);
                }
            }
            

            if (!empty($toUpdate)) {
                $userModel->updateBatch($toUpdate, 'id', 100);
            }
            if (!empty($toDeactivateEmails)) {
                $db->table('users')
                   ->whereIn('email', $toDeactivateEmails)
                   ->where('login_method', 'SSO')
                   ->set('status', 'INACTIVE')
                   ->update();
            }

            $db->transComplete();
        } catch (\Throwable $e) {
            $db->transRollback();
            CLI::error('Sync failed, rolled back. Error: ' . $e->getMessage());
            return EXIT_ERROR;
        }

        if ($db->transStatus() === false) {
            CLI::error('Sync failed (transaction).');
            return EXIT_ERROR;
        }

        CLI::write('Sync completed successfully.', 'green');
        return EXIT_SUCCESS;
    }
}
