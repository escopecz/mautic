<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\EventListener;

use Mautic\MigrationBundle\EventListener\MigrationSubscriber as MigrationParentSubscriber;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\WebhookBundle\EventListener
 */
class MigrationSubscriber extends MigrationParentSubscriber
{
    /**
     * @var string
     */
    protected $bundleName = 'WebhookBundle';

    /**
     * @var string
     */
    protected $entities = array('Event', 'Log', 'Webhook', 'WebhookQueue');
}
