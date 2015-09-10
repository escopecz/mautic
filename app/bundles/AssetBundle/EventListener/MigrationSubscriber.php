<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\EventListener;

use Mautic\MigrationBundle\EventListener\MigrationSubscriber as MigrationParentSubscriber;
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
class MigrationSubscriber extends MigrationParentSubscriber
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
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onMigrationEditGenerate (MigrationEditEvent $event)
    {
        $this->folders[] = $event->getFactory()->getParameter('upload_dir');

        parent::onMigrationEditGenerate($event);
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
                $entities = $this->getEntities($event, 'Asset', 'a');
                $event->setEntities($entities);
            }
            if ($event->getEntity() == 'Download') {
                $entities = $this->getEntities($event, 'Download', 'd');
                $event->setEntities($entities);
            }
        }
    }
}
