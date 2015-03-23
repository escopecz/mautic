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
use Mautic\MigrationBundle\Entity\Client;
use Mautic\MigrationBundle\MauticMigrationEvents;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

/**
 * Class ClientModel
 */
class ClientModel extends FormModel
{
    /**
     * {@inheritdoc}
     */
    public function saveEntity($entity, $unlock = true)
    {
        // if (empty($this->inConversion)) {
        //     $alias = $entity->getAlias();
        //     if (empty($alias)) {
        //         $alias = strtolower(InputHelper::alphanum($entity->getTitle(), false, '-'));
        //     } else {
        //         $alias = strtolower(InputHelper::alphanum($alias, false, '-'));
        //     }

        //     //make sure alias is not already taken
        //     $repo      = $this->getRepository();
        //     $testAlias = $alias;
        //     $count     = $repo->checkUniqueAlias($testAlias, $entity);
        //     $aliasTag  = $count;

        //     while ($count) {
        //         $testAlias = $alias . $aliasTag;
        //         $count     = $repo->checkUniqueAlias($testAlias, $entity);
        //         $aliasTag++;
        //     }
        //     if ($testAlias != $alias) {
        //         $alias = $testAlias;
        //     }
        //     $entity->setAlias($alias);
        // }

        // //set the author for new asset
        // if (!$entity->isNew()) {
        //     //increase the revision
        //     $revision = $entity->getRevision();
        //     $revision++;
        //     $entity->setRevision($revision);
        // }

        // parent::saveEntity($entity, $unlock);
    }

    /**
     * @return \Mautic\AssetBundle\Entity\AssetRepository
     */
    public function getRepository()
    {
        return $this->em->getRepository('MigrationBundle:Client');
    }

    /**
     * @return string
     */
    public function getPermissionBase()
    {
        return 'mauticMigration:clients';
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
        // if (!$entity instanceof Asset) {
        //     throw new MethodNotAllowedHttpException(array('Asset'));
        // }
        // $params = (!empty($action)) ? array('action' => $action) : array();
        // return $formFactory->create('asset', $entity, $params);
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
            $entity = new Client();
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
        if (!$entity instanceof Client) {
            throw new MethodNotAllowedHttpException(array('Client'));
        }

        switch ($action) {
            case "pre_save":
                $name = MauticMigrationEvents::MIGRATION_CLIENT_PRE_SAVE;
                break;
            case "post_save":
                $name = MauticMigrationEvents::MIGRATION_CLIENT_POST_SAVE;
                break;
            case "pre_delete":
                $name = MauticMigrationEvents::MIGRATION_CLIENT_PRE_DELETE;
                break;
            case "post_delete":
                $name = MauticMigrationEvents::MIGRATION_CLIENT_POST_DELETE;
                break;
            default:
                return false;
        }

        if ($this->dispatcher->hasListeners($name)) {
            if (empty($event)) {
                $event = new ClientEvent($entity, $isNew);
                $event->setEntityManager($this->em);
            }

            $this->dispatcher->dispatch($name, $event);
            return $event;
        } else {
            return false;
        }
    }
}
