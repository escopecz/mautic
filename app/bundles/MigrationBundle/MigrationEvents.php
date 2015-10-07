<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle;

/**
 * Class MauticMigrationEvents
 * Events available for MauticMigrationEvents
 *
 * @package Mautic\MigrationBundle
 */
final class MigrationEvents
{
    /**
     * The mautic.migration_template_on_edit_display event is thrown before displaying the migration template edit form
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_TEMPLATE_ON_EDIT_DISPLAY   = 'mautic.migration_template_on_edit_display';

    /**
     * The mautic.migration_template_on_display event is thrown before displaying the migration template content
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_TEMPLATE_ON_DISPLAY   = 'mautic.migration_template_on_display';

    /**
     * The mautic.migration_template_pre_save event is thrown right before a migration template is persisted.
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_TEMPLATE_PRE_SAVE   = 'mautic.migration_template_pre_save';

    /**
     * The mautic.migration_template_post_save event is thrown right after a migration template is persisted.
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_TEMPLATE_POST_SAVE   = 'mautic.migration_template_post_save';

    /**
     * The mautic.migration_template_pre_delete event is thrown prior to when a migration template is deleted.
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_TEMPLATE_PRE_DELETE   = 'mautic.migration_template_pre_delete';

    /**
     * The mautic.migration_template_post_delete event is thrown after a migration template is deleted.
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_TEMPLATE_POST_DELETE   = 'mautic.migration_template_post_delete';

    /**
     * The mautic.migration_on_entity_count event is thrown on entity count
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_ON_ENTITY_COUNT   = 'mautic.migration_on_entity_count';

    /**
     * The mautic.migration_on_export event is thrown on entity export
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticMigrationEvents instance.
     *
     * @var string
     */
    const MIGRATION_ON_EXPORT   = 'mautic.migration_on_export';

    /**
     * The mautic.migration_import_progress_on_generate event is thrown on import progress
     *
     * The event listener receives a
     * Mautic\MigrationBundle\Event\MauticProgressEvents instance.
     *
     * @var string
     */
    const MIGRATION_IMPORT_PROGRESS_ON_GENERATE   = 'mautic.migration_import_progress_on_generate';
}
