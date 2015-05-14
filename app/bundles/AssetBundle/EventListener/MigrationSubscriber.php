<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\MigrationBundle\MigrationEvents;
use Mautic\MigrationBundle\Event\MigrationEditEvent;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class MigrationSubscriber extends CommonSubscriber
{

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            MigrationEvents::MIGRATION_TEMPLATE_ON_EDIT_DISPLAY => array('onMigrationEditGenerate', 0)
        );
    }

    public function onMigrationEditGenerate (MigrationEditEvent $event)
    {

        $event->addForm(array(
            'name'       => 'Assets',
            'formAlias'  => 'assetmigration',
            'formTheme'  => 'MauticAssetBundle:FormTheme\Migration'
        ));
    }
}