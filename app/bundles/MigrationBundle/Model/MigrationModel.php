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
use Symfony\Component\Console\Helper\ProgressBar;

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
     * @param  integer          $batchLimit limit
     * @param  OutputInterface  $output
     * @return array of updated blueprint
     */
    public function triggerExport(Migration $migration, $batchLimit = 1000, $output = null)
    {
        $blueprint = $this->getBlueprint($migration);
        $this->saveBlueprint($migration->getId(), $blueprint);
        $count = 0;

        $maxCount = ($batchLimit < $blueprint['totalEntities']) ? $batchLimit : $blueprint['totalEntities'];
        $dir = $this->factory->getSystemPath('root') . '/exports/' . $migration->getId();

        if ($output) {
            $progress = new ProgressBar($output, $maxCount);
            $progress->start();
        }

        if ($this->dispatcher->hasListeners(MigrationEvents::MIGRATION_ON_EXPORT)) {

            // Make CSV backup of selected entities
            foreach ($blueprint['entities'] as &$props) {
                if ($count >= $batchLimit) {
                    // Bath amount is completed
                    break;
                }
                if ($props['processed'] >= $props['count']) {
                    // Data of this entity is already exported
                    continue;
                }

                $event = new MigrationEvent($this->factory);
                $event->setBundle($props['bundle']);
                $event->setEntity($props['entity']);
                $event->setStart($props['processed']);
                $event->setLimit($batchLimit);

                $this->dispatcher->dispatch(MigrationEvents::MIGRATION_ON_EXPORT, $event);

                $entities = $event->getEntities();
                $file     = $props['bundle'] . '.' . $props['entity'] . '.csv';
                $path     = $dir . '/' . $file;

                if (!is_dir($dir)) {
                    mkdir($dir, 0775, true);
                }

                $handle      = fopen($path, 'a');
                $headerBuilt = false;

                if (is_array($entities)) {
                    foreach ($entities as $entity) {
                        $entityAr = $this->entityToArray($entity);

                        if (!$props['processed'] && $headerBuilt === false) {
                            $headers = array_keys($entityAr);
                            fputcsv($handle, $headers);
                            $headerBuilt = true;
                        }

                        fputcsv($handle, $entityAr);
                    }
                }

                fclose($handle);

                $processed = count($entities);
                $props['processed'] += $processed;
                $blueprint['processedEntities'] += $processed;
                $count += $processed;

                if ($output && $count <= $maxCount) {
                    $progress->setCurrent($count);
                }
            }

            // Copy folders recursivly
            foreach ($blueprint['folders'] as $key => $folder) {
                $dest = $dir . '/' . $key;
                if (!file_exists($dest)) {
                    mkdir($dest, 0755);
                }
                foreach ($iterator = $this->getIterator($folder['path'])as $item) {
                    $destPath = $dest . '/' . $iterator->getSubPathName();
                    if (!file_exists($destPath)) {
                        if ($item->isDir()) {
                            mkdir($destPath, 0755);
                        } else {
                            copy($item, $destPath);
                        }
                        $blueprint['folders'][$key]['processed']++;
                        $blueprint['processedFiles']++;
                    }
                }
            }
        }

        $this->saveBlueprint($migration->getId(), $blueprint);

        // Create a ZIP package of expoted data
        if ($blueprint['totalEntities'] == $blueprint['processedEntities'] && $blueprint['totalFiles'] == $blueprint['processedFiles']) {
            $zip = new \ZipArchive();
            $zipFile = $this->factory->getSystemPath('root') . '/exports/' . $migration->getId() . '.zip';
            if ($zip->open($zipFile, file_exists($zipFile) ? \ZIPARCHIVE::OVERWRITE : \ZIPARCHIVE::CREATE) === true) {
                foreach ($iterator = $this->getIterator($dir) as $item) {
                    $file = $dir . '/' . $iterator->getSubPathName();
                    if (!$item->isDir()) {
                        if (!file_exists($file)) {
                            throw new \Exception($this->translator->trans('mautic.migration.file.do.not.exist', array('%file%' => $file)));
                        }
                        if (!is_readable($file)) {
                            throw new \Exception($this->translator->trans('mautic.migration.file.not.readable', array('%file%' => $file)));
                        }
                        $zip->addFile($file, preg_replace('/^' . preg_quote($dir . '/', '/') . '/', '', $file));
                    }
                }

                $zip->close();

                if (file_exists($zipFile)) {
                    $this->deleteFolderRecursivly($dir . '/');
                } else {
                    throw new \Exception($this->translator->trans('mautic.migration.file.not.created', array('%file%' => $zipFile)));
                }
            }
        }

        if ($output) {
            $progress->finish();
            $output->writeln('');
        }

        return $blueprint;
    }

    /**
     * Recursivly remove all content of a folder
     *
     * @param  string  $path
     *
     * @return void
     */
    function deleteFolderRecursivly($path) {
        if (is_dir($path)) {
            $files = glob($path . '*', GLOB_MARK);

            foreach ($files as $file)
            {
                $this->deleteFolderRecursivly($file);
            }

            if (file_exists($path)) {
                rmdir($path);
            }
        } elseif (is_file($path)) {
            unlink($path);
        }
    }

    /**
     * Get iterator for iterating recursivly over folder's content
     *
     * @param  string  $path
     *
     * @return RecursiveIteratorIterator
     */
    public function getIterator($path)
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
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
            if (!mkdir($dir, 0775, true)) {
                throw new \Exception($this->translator->trans('mautic.migration.file.not.created', array('%folder%' => $dir)));
            }
        }

        if (strnatcmp(phpversion(), '5.4.0') >= 0)
        {
            $content = json_encode($content, JSON_PRETTY_PRINT);
        }
        else
        {
            $content = json_encode($content);
        }

        if (file_put_contents($file, $content) === false) {
            throw new \Exception($this->translator->trans('mautic.migration.file.not.written', array('%file%' => $file)));
        }
    }

    /**
     * Trigger export of a specific migration template
     *
     * @param  Migration        $migration
     * @param  array            $blueprint
     * @param  integer          $batchLimit limit
     * @param  OutputInterface  $output
     * @return array of updated blueprint
     */
    public function buildBlueprint(Migration $migration)
    {
        $blueprint = array(
            'entities' => array(),
            'folders' => array(),
            'totalEntities' => 0,
            'processedEntities' => 0,
            'totalFiles' => 0,
            'processedFiles' => 0
        );

        if ($this->dispatcher->hasListeners(MigrationEvents::MIGRATION_ON_ENTITY_COUNT)) {
            foreach ($migration->getEntities() as $entity) {
                $parts = explode('.', $entity);
                $event = new MigrationCountEvent($this->factory);
                $event->setBundle($parts[0]);
                $event->setEntity($parts[1]);

                $this->dispatcher->dispatch(MigrationEvents::MIGRATION_ON_ENTITY_COUNT, $event);
                $blueprint['totalEntities'] += $event->getCount();
                $blueprint['entities'][$entity] = array(
                    'bundle' => $event->getBundle(),
                    'entity' => $event->getEntity(),
                    'count' => $event->getCount(),
                    'processed' => 0
                );
            }

            foreach ($migration->getFolders() as $folder) {
                $parts = explode('.', $folder);
                $count = 0;
                foreach ($iterator = $this->getIterator($parts[1]) as $item) {
                    $count++;
                }
                $blueprint['totalFiles'] += $count;
                $blueprint['folders'][] = array(
                    'path' => $parts[1],
                    'count' => $count,
                    'processed' => 0,
                    'bundle' => $parts[0]
                );
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
        if (method_exists($entity, 'convertToArray')) {
            $array = $entity->convertToArray();
        } else {
            $serializer = $this->factory->getSerializer();
            $entityJson = $serializer->serialize($entity, 'json');
            $array =  json_decode($entityJson, true);
        }

        foreach ($array as &$item) {
            if (is_array($item)) {
                $item = json_encode($item);
            }
        }

        return $array;
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
