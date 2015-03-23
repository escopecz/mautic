<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return array(
    'name'        => 'Migrations',
    'description' => 'Export / Import of data',
    'version'     => '0.1',
    'author'      => 'Mautic',

    'routes'     => array(
        'main' => array(
            // Clients
            'mautic_migrationclient_index'                   => array(
                'path'       => '/migrationclients/{page}',
                'controller' => 'MigrationBundle:Client:index'
            ),
            'mautic_migrationclient_action'                  => array(
                'path'       => '/migrationclients/{objectAction}/{objectId}',
                'controller' => 'MigrationBundle:Client:execute'
            )
        )
    ),

    'menu'       => array(
        'admin' => array(
            'items'    => array(
                'mautic.migration.clients.menu.index' => array(
                    'route'     => 'mautic_migrationclient_index',
                    'iconClass' => 'fa-envelope-square',
                    // 'access'    => 'migration:clients:view',
                )
            )
        )
    )
);