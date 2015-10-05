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
            'mautic_migration_index'                   => array(
                'path'       => '/migrations/{page}',
                'controller' => 'MauticMigrationBundle:Migration:index'
            ),
            'mautic_migration_action'                  => array(
                'path'       => '/migrations/{objectAction}/{objectId}',
                'controller' => 'MauticMigrationBundle:Migration:execute'
            )
        )
    ),

    'menu'       => array(
        'admin' => array(
            'priority' => 51,
            'items'    => array(
                'mautic.migration.menu.index' => array(
                    'route'     => 'mautic_migration_index',
                    'id'        => 'mautic_config_index',
                    'iconClass' => 'fa-suitcase',
                    'access'    => 'admin',
                )
            )
        )
    ),

    'services' => array(
        'events' => array(
            'mautic.migration.migrationbundle.subscriber' => array(
                'class' => 'Mautic\MigrationBundle\EventListener\MigrationSubscriber'
            ),
            'mautic.migration.configbundle.subscriber' => array(
                'class' => 'Mautic\MigrationBundle\EventListener\ConfigSubscriber'
            ),
        ),
        'forms' => array(
            'mautic.form.type.migration' => array(
                'class' => 'Mautic\MigrationBundle\Form\Type\MigrationType',
                'arguments' => 'mautic.factory',
                'alias' => 'migration'
            ),
            'mautic.form.type.migration.event.properties' => array(
                'class' => 'Mautic\MigrationBundle\Form\Type\EventPropertiesType',
                'arguments' => 'mautic.factory',
                'alias' => 'event_properties'
            ),
            'mautic.form.type.migration.config' => array(
                'class' => 'Mautic\MigrationBundle\Form\Type\ConfigType',
                'arguments' => 'mautic.factory',
                'alias' => 'migrationconfig'
            ),
            'mautic.form.type.migration.import' => array(
                'class' => 'Mautic\MigrationBundle\Form\Type\ImportType',
                'arguments' => 'mautic.factory',
                'alias' => 'migration_import'
            )
        )
    ),

    'parameters' => array(
        'export_dir'  => '%kernel.root_dir%/../exports',
        'export_batch_limit' => 10000
    )
);
