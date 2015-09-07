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
use Mautic\MigrationBundle\Event\MigrationEvent;
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
     * @param  array            $blueprint
     * @param  integer          $batch limit
     * @param  OutputInterface  $output
     * @return array of updated blueprint
     */
    public function triggerExport(Migration $migration, $batch, $output)
    {
        $blueprint = $this->getBlueprint($migration);

        if ($this->dispatcher->hasListeners(MigrationEvents::MIGRATION_ON_EXPORT)) {
            foreach ($blueprint['entities'] as $entity => $props) {
                if ($props['count'] == $props['processed']) {
                    continue;
                }

                $event = new MigrationEvent($this->factory);
                $event->setBundle($props['bundle']);
                $event->setEntity($props['entity']);
                $event->setStart($props['processed']);
                $event->setLimit(10);

                $this->dispatcher->dispatch(MigrationEvents::MIGRATION_ON_EXPORT, $event);

                $entities = $event->getEntities();
                $dir      = $this->factory->getSystemPath('root') . '/exports/' . $migration->getId();
                $file     = $props['bundle'] . '.' . $props['entity'] . '.csv';
                $path     = $dir . '/' . $file;

                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                $handle      = fopen($path, 'w');
                $headerBuilt = false;

                foreach ($entities as $entity) {
                    $entityAr = $this->entityToArray($entity);

                    if (!$props['processed'] && $headerBuilt === false) {
                        $headers = array_keys($entityAr);
                        fputcsv($handle, $headers);
                        $headerBuilt = true;
                    }

                    fputcsv($handle, $entityAr);
                }

                fclose($handle);

                break; // Process only one batch.
            }
        }

        $this->saveBlueprint($migration->getId(), $blueprint);
    }

    /**
     * Get migration blueprint from a json file or create fresh one
     *
     * @param  Migration  $migration
     *
     * @return array of updated blueprint
     */
    public function getBlueprint($migration)
    {
        $dir     = $this->factory->getSystemPath('root') . '/exports/' . $migration->getId();
        $file    = $dir . '/blueprint.json';

        if (file_exists($file)) {
            $blueprint = json_decode(file_get_contents($file), true);
        } else {
            $blueprint = $this->buildBlueprint($migration);
        }

        return $blueprint;
    }

    /**
     * Save migration blueprint to a json file
     *
     * @param  integer  $id of the migration
     * @param  array    $content of the migration blueprint
     *
     * @return void
     */
    public function saveBlueprint($id, array $content)
    {
        $dir     = $this->factory->getSystemPath('root') . '/exports/' . $id;
        $file    = $dir . '/blueprint.json';

        if (!is_dir($dir)) {
            if (mkdir($dir, 0775, true)) {
                throw new \Exception($translator->trans('mautic.migration.folder.not.written', array('%folder%' => $dir)));
            }
        }

        if (file_put_contents($file, json_encode($content)) === false) {
            throw new \Exception($translator->trans('mautic.migration.file.not.written', array('%file%' => $file)));
        }
    }

    /**
     * Trigger export of a specific migration template
     *
     * @param  Migration        $migration
     * @param  array            $blueprint
     * @param  integer          $batch limit
     * @param  OutputInterface  $output
     * @return array of updated blueprint
     */
    public function buildBlueprint(Migration $migration)
    {
        $blueprint = array('entities' => array(), 'folders' => array());

        if ($this->dispatcher->hasListeners(MigrationEvents::MIGRATION_ON_ENTITY_COUNT)) {
            foreach ($migration->getEntities() as $entity) {
                $parts = explode('.', $entity);
                $event = new MigrationCountEvent($this->factory);
                $event->setBundle($parts[0]);
                $event->setEntity($parts[1]);

                $this->dispatcher->dispatch(MigrationEvents::MIGRATION_ON_ENTITY_COUNT, $event);
                $blueprint['entities'][$entity] = array(
                    'bundle' => $event->getBundle(),
                    'entity' => $event->getEntity(),
                    'count' => $event->getCount(),
                    'processed' => 0
                );
            }

            foreach ($migration->getFolders() as $folder) {
                $parts = explode('.', $folder);
                $files = new \FilesystemIterator($parts[1], \FilesystemIterator::SKIP_DOTS);
                $blueprint['folders'][$folder] = array('count' => iterator_count($files), 'processed' => 0);
            }
        }

        return $blueprint;
    }

    /**
     * Convert an entity to array
     *
     * @param  object $entity
     * @return array
     */
    protected function entityToArray($entity)
    {
        $serializer = $this->factory->getSerializer();
        $entityJson = $serializer->serialize($entity, 'json');
        return json_decode($entityJson, true);
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
