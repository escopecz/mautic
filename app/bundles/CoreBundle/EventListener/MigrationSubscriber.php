<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\EventListener;

use Mautic\MigrationBundle\EventListener\MigrationSubscriber as MigrationParentSubscriber;
use Mautic\MigrationBundle\Event\MigrationEditEvent;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\CoreBundle\EventListener
 */
class MigrationSubscriber extends MigrationParentSubscriber
{
    /**
     * @var string
     */
    protected $bundleName = 'CoreBundle';

    /**
     * @var string
     */
    protected $entities = array('AuditLog', 'IpAddress', 'Notification');

    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onMigrationEditGenerate (MigrationEditEvent $event)
    {
        $root   = $event->getFactory()->getSystemPath('root');
        $themes = $event->getFactory()->getInstalledThemes();

        foreach ($themes as $folder => $theme) {
            $this->folders[] = $root . '/themes/' . $folder;
        }

        parent::onMigrationEditGenerate($event);
    }
}
