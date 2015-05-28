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
use Mautic\MigrationBundle\MigrationEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class MigrationModel
 */
class MigrationModel extends FormModel
{
    /**
     * {@inheritdoc}
     */
    public function saveEntity($entity, $unlock = true)
    {
        parent::saveEntity($entity, $unlock);
    }

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
        return "getTitle";
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
    public function triggerExport($migration, $progress, $batch, $output)
    {
        if ($id === null) {
            $entity = new Migration();
        } else {
            $entity = parent::getEntity($id);
        }

        return $entity;
    }

    /**
     * {@inheritdoc}
     *
     * @param $action
     * @param $event
     * @param $entity
     * @param $isNew
     * @throws \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException
     */
    protected function dispatchEvent($action, &$entity, $isNew = false, $event = false)
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
                $event = new MigrationEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return false;
        }
    }
}
