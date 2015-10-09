<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ApiBundle\EventListener;

use Mautic\MigrationBundle\EventListener\MigrationSubscriber as MigrationParentSubscriber;
use Mautic\MigrationBundle\MigrationEvents;
use Mautic\MigrationBundle\Event\MigrationEditEvent;
use Mautic\MigrationBundle\Event\MigrationCountEvent;
use Mautic\MigrationBundle\Event\MigrationEvent;
use Doctrine\ORM\Query;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\ApiBundle\EventListener
 */
class MigrationSubscriber extends MigrationParentSubscriber
{
    /**
     * @var string
     */
    protected $bundleName = 'ApiBundle';

    /**
     * @var string
     */
    protected $entities = array(
        'oAuth1\AccessToken', 'oAuth1\Consumer', 'oAuth1\Nonce', 'oAuth1\RequestToken',
        'oAuth2\AccessToken', 'oAuth2\AuthCode', 'oAuth2\Client', 'oAuth2\RefreshToken'
    );

    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onExport(MigrationEvent $event)
    {
        if ($event->getBundle() == $this->bundleName && $event->getEntity() == 'oAuth1\Nonce') {
            $entities = $this->getEntities(
                $event->getBundle(),
                $event->getEntity(),
                $event->getLimit(),
                $event->getStart(),
                array('timestamp')
            );
            $event->setEntities($entities);
        } else {
            parent::onExport($event);
        }
    }
}
