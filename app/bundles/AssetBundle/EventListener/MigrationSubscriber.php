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
use Doctrine\ORM\Query;

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
     * @var string
     */
    protected $entities = array('Asset', 'Download');

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
        foreach ($this->entities as $entity) {
            $event->addEntity($this->bundleName, $entity);
        }

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
        if ($event->getBundle() == $this->bundleName) {
            $factory = $event->getFactory();
            $key = array_search($event->getEntity(), $this->entities);
            if ($key !== false) {
                $event->setCount($factory->getEntityManager()->getRepository('Mautic' . $this->bundleName . ':' . $this->entities[$key])->count());
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
        if ($event->getBundle() == $this->bundleName) {
            $factory = $event->getFactory();
            if ($event->getEntity() == 'Asset') {
                $q = $factory->getEntityManager()->getRepository('MauticAssetBundle:Asset')->createQueryBuilder('a');

                $entities = $q
                    ->setMaxResults($event->getLimit())
                    ->setFirstResult($event->getStart())
                    ->orderBy('a.id')
                    ->getQuery()
                    ->getResult(Query::HYDRATE_SCALAR);
                $event->setEntities($entities);
            }
            if ($event->getEntity() == 'Download') {
                $q = $factory->getEntityManager()->getRepository('MauticAssetBundle:Download')->createQueryBuilder('d');

                $entities = $q
                    ->setMaxResults($event->getLimit())
                    ->setFirstResult($event->getStart())
                    ->orderBy('d.id')
                    ->getQuery()
                    ->getResult(Query::HYDRATE_SCALAR);
                $event->setEntities($entities);
            }
        }
    }
}
