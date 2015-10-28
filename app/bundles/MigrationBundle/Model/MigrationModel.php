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
use Mautic\MigrationBundle\Event\MigrationImportEvent;
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
     * @return string
     */
    public function getPermissionBase()
    {
        return 'mauticMigration:migrations';
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
     * Trigger export of a specific migration template
     *
     * @param  array            $blueprint
     * @param  integer          $batchLimit limit
     * @param  OutputInterface  $output
     * @return array of updated blueprint
     */
    public function triggerExport($batchLimit = null, $output = null)
    {
        if (!$batchLimit) {
            $batchLimit = (int) $this->factory->getParameter('export_batch_limit');
        }

        if (!$batchLimit) {
            $batchLimit = 10000;
        }

        $blueprint = $this->getBlueprint();
        $this->saveExportBlueprint($blueprint);
        $count = 0;

        $maxCount = ($batchLimit < $blueprint['totalEntities']) ? $batchLimit : $blueprint['totalEntities'];
        $dir = $this->getExportDir() . '/in_progress';

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
                        if (!$props['exported'] && $headerBuilt === false) {
                            $headers = array_keys($entity);
                            fputcsv($handle, $headers);
                            $headerBuilt = true;
                        }

                        fputcsv($handle, $entity);
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

        $this->saveExportBlueprint($blueprint);

        // Create a ZIP package of expoted data
        if ($blueprint['totalEntities'] == $blueprint['exportedEntities'] && $blueprint['totalFiles'] == $blueprint['exportedFiles']) {
            $zip = new \ZipArchive();
            $zipFile = $this->getZipPackagePath();
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
                    $this->deleteFolderRecursivly($dir);
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

            if (!isset($blueprint['importedEntities'])) {
                $blueprint['importedEntities'] = 0;
            }

            if (!isset($blueprint['import_errors'])) {
                $blueprint['import_errors'] = array();
            }

            if (!isset($blueprint['indexes_dropped'])) {
                $sqlDrop        = array();
                $sqlCreate      = array();
                $connection     = $this->factory->getEntityManager()->getConnection();
                $schemaManager  = $connection->getSchemaManager();
                $platform       = $schemaManager->getDatabasePlatform();
                $tables         = $schemaManager->listTableNames();
                $blueprint['executeSql'] = array();

                foreach ($tables as $table) {
                    //drop old indexes
                    $indexes = $schemaManager->listTableIndexes($table);

                    /** @var \Doctrine\DBAL\Schema\Index $oldIndex */
                    foreach ($indexes as $indexName => $index) {
                        if ($indexName == 'primary') {
                            continue;
                        }

                        if (strpos($indexName, '_pkey') !== false) {
                            $sql[] = $platform->getDropConstraintSQL($indexName, $table);
                            $blueprint['executeSql'][] = $platform->getCreateConstraintSQL($index, $table);
                        } else {
                            $sql[] = $platform->getDropIndexSQL($index, $table);
                            $blueprint['executeSql'][] = $platform->getCreateIndexSQL($index, $table);
                        }
                    }

                    // drop foreign keys
                    $restraints = $schemaManager->listTableForeignKeys($table);

                    foreach ($restraints as $restraint) {
                        $sql[] = $platform->getDropForeignKeySQL($restraint, $table);
                        $blueprint['executeSql'][] = $platform->getCreateForeignKeySQL($restraint, $table);
                    }
                }

                if (!empty($sql)) {
                    foreach ($sql as $query) {
                        try {
                            $connection->query($query);
                        } catch (\Exception $e) {die($e->getMessage());
                            $blueprint['import_errors'][] = $e->getMessage();
                        }
                    }
                }
                $blueprint['indexes_dropped'] = true;
            }

            $batchLimit = 500;
            $batchImported = 0;

            foreach ($formData['entities'] as $entityKey => $importEntity) {
                $entityKey = str_replace(':', '.', $entityKey);
                if (isset($blueprint['entities'][$entityKey]) && $batchImported <= $batchLimit) {
                    $blueprintEntity = &$blueprint['entities'][$entityKey];
                    $blueprintEntity['allow_import'] = $importEntity;

                    if (!isset($blueprintEntity['imported'])) {
                        $blueprintEntity['imported'] = 0;
                    }

                    $csvFile = $this->getImportDir() . '/' . $entityKey . '.csv';

                    if ($importEntity && file_exists($csvFile) && $blueprintEntity['exported'] > $blueprintEntity['imported']) {

                        $fh = fopen($csvFile, 'r');
                        $header = fgetcsv($fh);
                        $cursor = 0;

                        while ($line = fgetcsv($fh)) {
                            if ($cursor >= $blueprintEntity['imported'] && $batchImported <= $batchLimit) {
                                if (!isset($blueprintEntity['truncated'])) {
                                    $blueprintEntity['truncated'] = false;
                                }
                                $row = array_combine($header, $line);
                                $event = new MigrationImportEvent($blueprintEntity['bundle'], $blueprintEntity['entity'], $row, $blueprintEntity['truncated']);
                                $this->dispatcher->dispatch(MigrationEvents::MIGRATION_ON_IMPORT, $event);

                                $blueprintEntity['truncated'] = $event->getTruncated();

                                $blueprint['importedEntities']++;
                                $batchImported++;
                            }
                            $cursor++;
                        }

                        $blueprintEntity['imported'] += $batchImported;

                        fclose($fh);
                    }
                }
            }

            $connection = $this->factory->getEntityManager()->getConnection();

            if (empty($blueprint['sqlExecuted']) && !empty($blueprint['executeSql']) && $blueprint['importedEntities'] == $blueprint['exportedEntities']) {
                foreach ($blueprint['executeSql'] as $query) {
                    try {
                        $connection->query($query);
                    } catch (\Exception $e) {die($e->getMessage());
                        $blueprint['import_errors'][] = $e->getMessage();
                    }
                }
                $blueprint['sqlExecuted'] = true;
            }
        }

        $this->saveImportBlueprint($blueprint);
    }

    /**
     * Remove exported files. The folder and the zip package.
     *
     * @return void
     */
    public function removeExportedFiles() {
        $dir = $this->getExportDir();
        $zipFile = $this->getZipPackagePath();

        $this->deleteFolderRecursivly($dir);
        if (file_exists($zipFile)) {
            unlink($zipFile);
        }
    }

    /**
     * Get path and other info about last exported package
     *
     * @return array
     */
    public function getLastPackageInfo() {
        $zipFile = $this->getZipPackagePath();

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
     * @return string
     */
    public function getExportDir() {
        return $this->factory->getParameter('export_dir');
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
     * Get absolute path and where to store migration files
     *
     * @return string
     */
    public function getZipPackagePath() {
        return $this->getExportDir() . '/backup_' . (new DateTimeHelper)->getString() . '.zip';
    }

    /**
     * Recursivly remove all content of a folder
     *
     * @param  string  $path
     *
     * @return void
     */
    public function deleteFolderRecursivly($path) {
        $iterator = $this->getIterator($path);

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        rmdir($path);
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
            \RecursiveIteratorIterator::CHILD_FIRST
        );
    }

    /**
     * Get migration blueprint from a existing json file
     *
     * @return array of updated blueprint | null
     */
    public function getExistingBlueprint()
    {
        $dir     = $this->getExportDir();
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
     * @return array of updated blueprint
     */
    public function getBlueprint()
    {
        $blueprint = $this->getExistingBlueprint();

        if (!$blueprint) {
            $blueprint = $this->buildBlueprint();
        }

        return $blueprint;
    }

    /**
     * Save migration blueprint to a json file to export folder
     *
     * @param  array    $content of the migration blueprint
     *
     * @return void
     */
    public function saveExportBlueprint(array $content)
    {
        $this->saveBlueprint($this->getExportDir(), $content);
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
     * @param  array            $blueprint
     * @param  integer          $batchLimit limit
     * @param  OutputInterface  $output
     * @return array of updated blueprint
     */
    public function buildBlueprint()
    {
        $blueprint = array(
            'entities' => array(),
            'folders' => array(),
            'totalEntities' => 0,
            'exportedEntities' => 0,
            'totalFiles' => 0,
            'exportedFiles' => 0
        );

        $event      = new MigrationEditEvent($this->factory);
        $dispatcher = $this->factory->getDispatcher();
        $dispatcher->dispatch(MigrationEvents::MIGRATION_TEMPLATE_ON_EDIT_DISPLAY, $event);
        $bundles    = $event->getEntities();
        $foldersB   = $event->getFolders();

        if ($this->dispatcher->hasListeners(MigrationEvents::MIGRATION_ON_ENTITY_COUNT)) {
            foreach ($bundles as $bundle => $entities) {
                foreach ($entities as $entity => $name) {
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
            }

            foreach ($foldersB as $key => $folders) {
                $parts = explode('.', $key);
                $bundle = $parts[0];
                foreach ($folders as $folder) {
                    $count = 0;
                    foreach ($iterator = $this->getIterator($folder) as $item) {
                        $count++;
                    }
                    $blueprint['totalFiles'] += $count;
                    $blueprint['folders'][] = array(
                        'path' => $folder,
                        'count' => $count,
                        'exported' => 0,
                        'bundle' => $bundle
                    );
                }
            }
        }

        return $blueprint;
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
