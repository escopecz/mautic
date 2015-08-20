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
use Mautic\MigrationBundle\Event\MigrationCountEvent;
use Mautic\MigrationBundle\Event\MigrationEvent;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\AssetBundle\EventListener
 */
class MigrationSubscriber extends CommonSubscriber
{
    /**
     * @var string
     */
    protected $bundleName = 'AssetBundle';

    /**
     * @return array
     */
    static public function getSubscribedEvents ()
    {
        return array(
            MigrationEvents::MIGRATION_TEMPLATE_ON_EDIT_DISPLAY => array('onMigrationEditGenerate', 0),
            MigrationEvents::MIGRATION_ON_ENTITY_COUNT => array('onEntityCount', 0),
            MigrationEvents::MIGRATION_ON_EXPORT => array('onExport', 0)
        );
    }

    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onMigrationEditGenerate (MigrationEditEvent $event)
    {
        $event->addEntity($this->bundleName, 'Asset');
        $event->addEntity($this->bundleName, 'Download');
        $event->addFolder($this->bundleName, realpath($event->getFactory()->getParameter('upload_dir')));

        // No need for special form for now
        // $event->addForm(array(
        //     'name'       => 'Assets',
        //     'formAlias'  => 'assetmigration',
        //     'formTheme'  => 'MauticAssetBundle:FormTheme\Migration'
        // ));
    }

    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onEntityCount (MigrationCountEvent $event)
    {
        if ($event->getBundle() == 'AssetBundle') {
            $factory = $event->getFactory();
            if ($event->getEntity() == 'Asset') {
                $event->setCount($factory->getEntityManager()->getRepository('MauticAssetBundle:Asset')->count());
            }
        }
    }

    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onExport (MigrationEvent $event)
    {
        if ($event->getBundle() == 'AssetBundle') {
            $factory = $event->getFactory();
            if ($event->getEntity() == 'Asset') {
                $model = $this->factory->getModel('asset.asset');

                $event->setEntities(
                    $model->getEntities(
                        array(
                            'start'      => $event->getStart(),
                            'limit'      => $event->getLimit(),
                            'orderBy'    => 'a.id',
                            'orderByDir' => 'asc'
                        )
                    )
                );
            }
        }
    }
}
