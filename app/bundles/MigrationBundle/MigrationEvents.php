<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautics\MigrationBundle;

/**
 * Class MauticMigrationEvents
 * Events available for MauticMigrationEvents
 *
 * @package Mautic\MigrationBundle
 */
final class MauticMigrationEvents
{
    /**
     * The mautic.migration_client_on_display event is thrown before displaying the migration client content
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_CLIENT_ON_DISPLAY   = 'mautic.migration_client_on_display';

    /**
     * The mautic.migration_client_pre_save event is thrown right before a migration client is persisted.
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_CLIENT_PRE_SAVE   = 'mautic.migration_client_pre_save';

    /**
     * The mautic.migration_client_post_save event is thrown right after a migration client is persisted.
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_CLIENT_POST_SAVE   = 'mautic.migration_client_post_save';

    /**
     * The mautic.migration_client_pre_delete event is thrown prior to when a migration client is deleted.
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_CLIENT_PRE_DELETE   = 'mautic.migration_client_pre_delete';

    /**
     * The mautic.migration_client_post_delete event is thrown after a migration client is deleted.
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_CLIENT_POST_DELETE   = 'mautic.migration_client_post_delete';
}
