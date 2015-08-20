<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Model;

use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\CoreBundle\Model\FormModel;
use Mautic\MigrationBundle\Entity\Migration;
use Mautic\MigrationBundle\Event\MigrationTemplateEvent;
use Mautic\MigrationBundle\Event\MigrationEditEvent;
use Mautic\MigrationBundle\Event\MigrationCountEvent;
use Mautic\MigrationBundle\MigrationEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MigrationModel
 */
class MigrationModel extends FormModel
{
    /**
     * @return \Mautic\AssetBundle\Entity\AssetRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MauticMigrationBundle:Migration');
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'mauticMigration:migrations';
    }

    /**
     * @return string
     */
    public function getNameGetter()
    {
        return "getName";
    }

    /**
     * {@inheritdoc}
     *
     * @throws NotFoundHttpException
     */
    public function createForm($entity, $formFactory, $action = null, $options = array())
    {
        if (!$entity instanceof Migration) {
            throw new MethodNotAllowedHttpException(array('Migration'));
        }
        if ($action) {
            $options['action'] = $action;
        }
        return $formFactory->create('migration', $entity, $options);
    }

    /**
     * Get a specific entity or generate a new one if id is empty
     *
     * @param $id
     * @return null|object
     */
    public function getEntity($id = null)
    {
        if ($id === null) {
            $entity = new Migration();
        } else {
            $entity = parent::getEntity($id);
        }

        return $entity;
    }

    /**
     * Trigger export of a specific migration template
     *
     * @param  Migration        $migration
     * @param  array            $progress
     * @param  integer          $batch limit
     * @param  OutputInterface  $output
     * @return array of updated progress
     */
    public function triggerExport(Migration $migration, $progress, $batch, $output)
    {
        if (!$progress) {
            $progress = $this->buildProgress($migration);
        }

        if ($this->dispatcher->hasListeners(MigrationEvents::MIGRATION_ON_EXPORT)) {
            foreach ($progress['entities'] as $entity) {
                // TODO implement entity export
                // $event = new MigrationEvent($entity, $isNew);
                // $event->setMigration($migration);
                // $this->dispatcher->dispatch(MigrationEvents::MIGRATION_ON_EXPORT, $event);
            }
            // TODO implement file export
        }

        return $progress;
    }

    /**
     * Trigger export of a specific migration template
     *
     * @param  Migration        $migration
     * @param  array            $progress
     * @param  integer          $batch limit
     * @param  OutputInterface  $output
     * @return array of updated progress
     */
    public function buildProgress(Migration $migration)
    {
        $progress = array('entities' => array(), 'folders' => array());

        if ($this->dispatcher->hasListeners(MigrationEvents::MIGRATION_ON_ENTITY_COUNT)) {
            foreach ($migration->getEntities() as $entity) {
                $parts = explode('.', $entity);
                $event = new MigrationCountEvent($this->factory);
                $event->setBundle($parts[0]);
                $event->setEntity($parts[1]);

                $this->dispatcher->dispatch(MigrationEvents::MIGRATION_ON_ENTITY_COUNT, $event);
                $progress['entities'][$entity] = array('count' => $event->getCount(), 'processed' => 0);
            }

            foreach ($migration->getFolders() as $folder) {
                $parts = explode('.', $folder);
                $files = new \FilesystemIterator($parts[1], \FilesystemIterator::SKIP_DOTS);
                $progress['folders'][$folder] = array('count' => iterator_count($files), 'processed' => 0);
            }
        }

        return $progress;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param boolean $isNew
     * @param Symfony\Component\EventDispatcher\Event $event
     *
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, Event $event = NULL)
    {
        if (!$entity instanceof Migration) {
            throw new MethodNotAllowedHttpException(array('Migration'));
        }

        switch ($action) {
            case "pre_save":
                $name = MigrationEvents::MIGRATION_TEMPLATE_PRE_SAVE;
                break;
            case "post_save":
                $name = MigrationEvents::MIGRATION_TEMPLATE_POST_SAVE;
                break;
            case "pre_delete":
                $name = MigrationEvents::MIGRATION_TEMPLATE_PRE_DELETE;
                break;
            case "post_delete":
                $name = MigrationEvents::MIGRATION_TEMPLATE_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new MigrationTemplateEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return null;
        }
    }
}
