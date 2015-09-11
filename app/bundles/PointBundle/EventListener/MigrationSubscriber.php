<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\EventListener;

use Mautic\MigrationBundle\EventListener\MigrationSubscriber as MigrationParentSubscriber;
use Mautic\MigrationBundle\Event\MigrationEvent;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\PointBundle\EventListener
 */
class MigrationSubscriber extends MigrationParentSubscriber
{
    /**
     * @var string
     */
    protected $bundleName = 'PointBundle';

    /**
     * @var string
     */
    protected $entities = array('LeadPointLog', 'LeadTriggerLog', 'Point', 'Trigger', 'TriggerEvent');

    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onExport(MigrationEvent $event)
    {
        if ($event->getBundle() == $this->bundleName && $event->getEntity() == 'LeadPointLog') {
            $entities = $this->getEntities($event, 'LeadPointLog', 'dateFired');
            $event->setEntities($entities);
        } elseif ($event->getBundle() == $this->bundleName && $event->getEntity() == 'LeadTriggerLog') {
            $entities = $this->getEntities($event, 'LeadTriggerLog', 'dateFired');
            $event->setEntities($entities);
        } else {
            parent::onExport($event);
        }
    }
}
