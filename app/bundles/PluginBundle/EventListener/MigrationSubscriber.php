<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\EventListener;

use Mautic\MigrationBundle\EventListener\MigrationSubscriber as MigrationParentSubscriber;
use Mautic\MigrationBundle\Event\MigrationEditEvent;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\PluginBundle\EventListener
 */
class MigrationSubscriber extends MigrationParentSubscriber
{
    /**
     * @var string
     */
    protected $bundleName = 'PluginBundle';

    /**
     * @var string
     */
    protected $entities = array('Integration', 'Plugin');

    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onMigrationEditGenerate(MigrationEditEvent $event)
    {
        /** @var \Mautic\PluginBundle\Model\PluginModel $model */
        $model   = $event->getFactory()->getModel('plugin');
        $root    = $event->getFactory()->getSystemPath('root');
        $plugins = $model->getEntities(array('index' => 'bundle'));

        foreach ($plugins as $folder => $plugin) {
            $this->folders[] = $root . '/plugins/' . $folder;
        }

        parent::onMigrationEditGenerate($event);
    }
}
