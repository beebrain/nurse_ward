<?php

declare(strict_types=1);

/**
 * This file is part of CodeIgniter Shield.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Config;

use CodeIgniter\Shield\Config\AuthGroups as ShieldAuthGroups;

class AuthGroups extends ShieldAuthGroups
{
    /**
     * --------------------------------------------------------------------
     * Default Group
     * --------------------------------------------------------------------
     * The group that a newly registered user is added to.
     */
    public string $defaultGroup = 'nurse';

    /**
     * --------------------------------------------------------------------
     * Groups
     * --------------------------------------------------------------------
     * An associative array of the available groups in the system, where the keys
     * are the group names and the values are arrays of the group info.
     *
     * Whatever value you assign as the key will be used to refer to the group
     * when using functions such as:
     *      $user->addGroup('superadmin');
     *
     * @var array<string, array<string, string>>
     *
     * @see https://codeigniter4.github.io/shield/quick_start_guide/using_authorization/#change-available-groups for more info
     */
    public array $groups = [
        'superadmin' => [
            'title'       => 'Super Admin',
            'description' => 'Complete control of the system, including user approval and ward management.',
        ],
        'manager' => [
            'title'       => 'Manager',
            'description' => 'View statistical summaries and dashboards.',
        ],
        'nurse' => [
            'title'       => 'Nurse',
            'description' => 'Record daily patient counts for assigned wards.',
        ],
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions
     * --------------------------------------------------------------------
     * The available permissions in the system.
     *
     * If a permission is not listed here it cannot be used.
     */
    public array $permissions = [
        'admin.access'    => 'Can access the admin area',
        'users.manage'    => 'Can approve and manage users',
        'wards.manage'    => 'Can manage wards and departments',
        'reports.view'    => 'Can view monthly reports',
        'dashboards.view' => 'Can view interactive dashboards',
        'census.record'   => 'Can record daily patient census',
    ];

    /**
     * --------------------------------------------------------------------
     * Permissions Matrix
     * --------------------------------------------------------------------
     * Maps permissions to groups.
     *
     * This defines group-level permissions.
     */
    public array $matrix = [
        'superadmin' => [
            'admin.access',
            'users.manage',
            'wards.manage',
            'reports.view',
            'dashboards.view',
            'census.record',
        ],
        'manager' => [
            'reports.view',
            'dashboards.view',
        ],
        'nurse' => [
            'census.record',
        ],
    ];
}
