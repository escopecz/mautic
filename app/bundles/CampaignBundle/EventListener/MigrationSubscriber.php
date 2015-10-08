<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\MigrationBundle\EventListener\MigrationSubscriber as MigrationParentSubscriber;
use Mautic\MigrationBundle\MigrationEvents;
use Mautic\MigrationBundle\Event\MigrationEditEvent;
use Mautic\MigrationBundle\Event\MigrationCountEvent;
use Mautic\MigrationBundle\Event\MigrationEvent;
use Doctrine\ORM\Query;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\CampaignBundle\EventListener
 */
class MigrationSubscriber extends MigrationParentSubscriber
{
    /**
     * @var string
     */
    protected $bundleName = 'CampaignBundle';

    /**
     * @var string
     */
    protected $entities = array('Campaign', 'Event', 'Lead', 'LeadEventLog');

    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onExport(MigrationEvent $event)
    {
        if ($event->getBundle() == $this->bundleName && $event->getEntity() == 'LeadEventLog') {
            $entities = $this->getEntities(
                $event->getBundle(),
                $event->getEntity(),
                $event->getLimit(),
                $event->getStart(),
                array('event', 'lead', 'campaign')
            );
            $event->setEntities($entities);
        } else {
            parent::onExport($event);
        }
    }
}
