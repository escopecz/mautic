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
use Mautic\CoreBundle\Helper\DateTimeHelper;
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
    public function triggerExport(Migration $migration, $batchLimit = null, $output = null)
    {
        if (!$batchLimit) {
            $batchLimit = (int) $this->factory->getParameter('export_batch_limit');
        }

        if (!$batchLimit) {
            $batchLimit = 10000;
        }

        $blueprint = $this->getBlueprint($migration);
        $this->saveExportBlueprint($migration->getId(), $blueprint);
        $count = 0;

        $maxCount = ($batchLimit < $blueprint['totalEntities']) ? $batchLimit : $blueprint['totalEntities'];
        $dir = $this->getMigrationDir($migration->getId());

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
                if ($props['exported'] >= $props['count']) {
                    // Data of this entity is already exported
                    continue;
                }

                $event = new MigrationEvent($this->factory);
                $event->setBundle($props['bundle']);
                $event->setEntity($props['entity']);
                $event->setStart($props['exported']);
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

                        if (!$props['exported'] && $headerBuilt === false) {
                            $headers = array_keys($entityAr);
                            fputcsv($handle, $headers);
                            $headerBuilt = true;
                        }

                        fputcsv($handle, $entityAr);
                    }
                }

                fclose($handle);

                $exported = count($entities);
                $props['exported'] += $exported;
                $blueprint['exportedEntities'] += $exported;
                $count += $exported;

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
                        $blueprint['folders'][$key]['exported']++;
                        $blueprint['exportedFiles']++;
                    }
                }
            }
        }

        $this->saveExportBlueprint($migration->getId(), $blueprint);

        // Create a ZIP package of expoted data
        if ($blueprint['totalEntities'] == $blueprint['exportedEntities'] && $blueprint['totalFiles'] == $blueprint['exportedFiles']) {
            $zip = new \ZipArchive();
            $zipFile = $this->getZipPackagePath($migration->getId());
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
     * Trigger import of a uploaded migration template
     *
     * @param  array $blueprint
     * @param  array $formData
     *
     * @return array of updated blueprint
     */
    public function triggerImport($blueprint, $formData)
    {
        if (!empty($formData['entities'])) {
            ini_set('auto_detect_line_endings', true);

            foreach ($formData['entities'] as $entityKey => $importEntity) {
                $entityKey = str_replace(':', '.', $entityKey);
                if (isset($blueprint['entities'][$entityKey])) {
                    $blueprint['entities'][$entityKey]['allow_import'] = $importEntity;

                    if (!isset($blueprint['entities'][$entityKey]['imported'])) {
                        $blueprint['entities'][$entityKey]['imported'] = 0;
                    }

                    if ($importEntity) {
                        $event = new MigrationEvent($this->factory);
                        $event->setBundle($blueprint['entities'][$entityKey]['bundle']);
                        $event->setEntity($blueprint['entities'][$entityKey]['entity']);
                        $event->setStart($blueprint['entities'][$entityKey]['imported']);
                        $event->setLimit(10); // @todo take this from config

                        $this->dispatcher->dispatch(MigrationEvents::MIGRATION_ON_EXPORT, $event);

                        // $blueprint['entities'][$entityKey]['imported'] = ;
                    }
                }
            }
        }

        $this->saveImportBlueprint($blueprint);
    }

    /**
     * Remove exported files. The folder and the zip package.
     *
     * @param  integer  $id
     *
     * @return void
     */
    public function removeExportedFiles($id) {
        $dir = $this->getMigrationDir($id);
        $zipFile = $this->getZipPackagePath($id);

        $this->deleteFolderRecursivly($dir . '/');
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }
    }

    /**
     * Get path and other info about last exported package
     *
     * @param  integer  $id
     *
     * @return array
     */
    public function getLastPackageInfo($id) {
        $zipFile = $this->getZipPackagePath($id);

        $fileInfo = array(
            'exists'    => false,
            'modified'  => null,
            'file_size' => 0,
            'path'      => realpath($zipFile)
        );

        if (file_exists($zipFile)) {
            $fileInfo['exists'] = true;
            $fileInfo['modified'] = (new DateTimeHelper(filemtime($zipFile), 'U'))->getDateTime();
            $fileInfo['file_size'] = round(filesize($zipFile) / 1000000, 3); // in MB
        }

        return $fileInfo;
    }

    /**
     * Get absolut path where to store migration files
     *
     * @param  integer  $id
     *
     * @return string
     */
    public function getMigrationDir($id) {
        return $this->factory->getParameter('export_dir') . '/' . $id;
    }

    /**
     * Get absolut path where the import should be uploaded
     *
     * @return string
     */
    public function getImportDir() {
        $username  = $this->factory->getUser()->getUsername();
        $dirName   = $username . '_migration_import';
        return $this->factory->getParameter('import_dir') . '/' . $dirName;
    }

    /**
     * Get absolut path and where to store migration files
     *
     * @param  integer  $id
     *
     * @return string
     */
    public function getZipPackagePath($id) {
        return $this->getMigrationDir($id) . '.zip';
    }

    /**
     * Recursivly remove all content of a folder
     *
     * @param  string  $path
     *
     * @return void
     */
    public function deleteFolderRecursivly($path) {
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
     * Get migration blueprint from a existing json file
     *
     * @param  Migration  $migration
     *
     * @return array of updated blueprint | null
     */
    public function getExistingBlueprint($migration)
    {
        $dir     = $this->getMigrationDir($migration->getId());
        $file    = $dir . '/blueprint.json';

        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }

        return null;
    }

    /**
     * Get imported blueprint from a existing json file
     *
     * @return array | null
     */
    public function getImportedBlueprint()
    {
        $dir     = $this->getImportDir();
        $file    = $dir . '/blueprint.json';

        if (file_exists($file)) {
            return json_decode(file_get_contents($file), true);
        }

        return null;
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
        $blueprint = $this->getExistingBlueprint($migration);

        if (!$blueprint) {
            $blueprint = $this->buildBlueprint($migration);
        }

        return $blueprint;
    }

    /**
     * Save migration blueprint to a json file to export folder
     *
     * @param  integer  $id of the migration
     * @param  array    $content of the migration blueprint
     *
     * @return void
     */
    public function saveExportBlueprint($id, array $content)
    {
        $this->saveBlueprint($this->getMigrationDir($id), $content);
    }

    /**
     * Save migration blueprint to a json file to import folder
     *
     * @param  array    $content of the migration blueprint
     *
     * @return void
     */
    public function saveImportBlueprint($content)
    {
        $this->saveBlueprint($this->getImportDir(), $content);
    }

    /**
     * Save migration blueprint to a json file
     *
     * @param  string   $dir path
     * @param  array    $content of the migration blueprint
     *
     * @return void
     */
    public function saveBlueprint($dir, array $content)
    {
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

        $file = 'blueprint.json';

        if (file_put_contents($dir . '/' . $file, $content) === false) {
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
            'exportedEntities' => 0,
            'totalFiles' => 0,
            'exportedFiles' => 0
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
                    'exported' => 0
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
                    'exported' => 0,
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
