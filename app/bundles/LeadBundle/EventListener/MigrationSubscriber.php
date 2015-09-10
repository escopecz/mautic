<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\EventListener;

use Mautic\MigrationBundle\EventListener\MigrationSubscriber as MigrationParentSubscriber;
use Mautic\MigrationBundle\MigrationEvents;
use Mautic\MigrationBundle\Event\MigrationEditEvent;
use Mautic\MigrationBundle\Event\MigrationCountEvent;
use Mautic\MigrationBundle\Event\MigrationEvent;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\LeadBundle\EventListener
 */
class MigrationSubscriber extends MigrationParentSubscriber
{
    /**
     * @var string
     */
    protected $bundleName = 'LeadBundle';

    /**
     * @var string
     */
    protected $entities = array('Lead', 'LeadField', 'LeadList', 'LeadNote', 'ListLead', 'PointsChangeLog', 'Tag');


    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onExport(MigrationEvent $event)
    {
        if ($event->getBundle() == $this->bundleName && $event->getEntity() == 'ListLead') {
            $entities = $this->getEntities($event, 'ListLead', 'dateAdded');
            $event->setEntities($entities);
        } else {
            parent::onExport($event);
        }
    }
}
