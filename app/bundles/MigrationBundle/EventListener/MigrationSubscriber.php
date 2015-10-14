<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\MigrationBundle\MigrationEvents;
use Mautic\MigrationBundle\Event\MigrationEditEvent;
use Mautic\MigrationBundle\Event\MigrationCountEvent;
use Mautic\MigrationBundle\Event\MigrationEvent;
use Mautic\MigrationBundle\Event\MigrationImportEvent;
use Mautic\MigrationBundle\Event\MigrationImportViewEvent;
use Symfony\Component\Serializer\Normalizer\GetSetMethodNormalizer;
use Doctrine\ORM\Query;

/**
 * Class MigrationSubscriber
 *
 * @package Mautic\MigrationBundle\EventListener
 */
class MigrationSubscriber extends CommonSubscriber
{
    /**
     * @var string
     */
    protected $classPrefix = 'Mautic';

    /**
     * @var string
     */
    protected $bundleName = 'MigrationBundle';

    /**
     * @var array
     */
    protected $entities = array('Migration');

    /**
     * @var array
     */
    protected $folders = array();

    /**
     * @return array
     */
    static public function getSubscribedEvents()
    {
        return array(
            MigrationEvents::MIGRATION_TEMPLATE_ON_EDIT_DISPLAY => array('onMigrationEditGenerate', 0),
            MigrationEvents::MIGRATION_ON_ENTITY_COUNT => array('onEntityCount', 0),
            MigrationEvents::MIGRATION_ON_EXPORT => array('onExport', 0),
            MigrationEvents::MIGRATION_ON_IMPORT => array('onImport', 0),
            MigrationEvents::MIGRATION_IMPORT_PROGRESS_ON_GENERATE => array('onImportProgressGenerate', 0)
        );
    }

    /**
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onMigrationEditGenerate(MigrationEditEvent $event)
    {
        foreach ($this->entities as $entity) {
            $event->addEntity($this->bundleName, $entity);
        }

        foreach ($this->folders as $folder) {
            $event->addFolder($this->bundleName, realpath($folder));
        }

        // No need for special form for now, but it can be done like this
        // $event->addForm(array(
        //     'name'       => 'Assets',
        //     'formAlias'  => 'assetmigration',
        //     'formTheme'  => 'MauticAssetBundle:FormTheme\Migration'
        // ));
    }

    /**
     * Called on entity count
     *
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function onEntityCount(MigrationCountEvent $event)
    {
        if ($event->getBundle() == $this->bundleName) {
            $factory = $event->getFactory();
            $key = array_search($event->getEntity(), $this->entities);
            if ($key !== false) {
                $count = $this->countRowsForEntity($this->bundleName, $this->entities[$key], $this->classPrefix);
                $event->setCount($count);
            }
        }
    }

    /**
     * Called on entity count
     *
     * @param  MigrationTemplateEvent $event
     *
     * @return void
     */
    public function countRowsForEntity($bundleName, $entityName, $prefix = 'Mautic')
    {
        $repositoryName = $prefix . $bundleName . ':' . $entityName;
        $repository = $this->factory->getEntityManager()->getRepository($repositoryName);

        if (method_exists($repository, 'count')) {
            $count = $repository->count();
        } else {
            $count = $repository->createQueryBuilder('e')
                ->select('count(e)')
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $count;
    }

    /**
     * Method executed on migration export
     *
     * @param  MigrationEvent $event
     *
     * @return void
     */
    public function onExport(MigrationEvent $event)
    {
        if ($event->getBundle() == $this->bundleName) {
            foreach ($this->entities as $entity) {
                if ($event->getEntity() == $entity) {
                    $entities = $this->getEntities(
                        $event->getBundle(),
                        $event->getEntity(),
                        $event->getLimit(),
                        $event->getStart()
                    );
                    $event->setEntities($entities);
                }
            }
        }
    }

    /**
     * Method executed on migration import
     *
     * @param  MigrationImportEvent $event
     *
     * @return void
     */
    public function onImport(MigrationImportEvent $event)
    {
        if ($event->getBundle() == $this->bundleName) {
            foreach ($this->entities as $entity) {
                if ($event->getEntity() == $entity) {
                    $entityClass = $this->classPrefix . '\\' . $event->getBundle() . '\\Entity\\' . $event->getEntity();
                    $metadata = $this->factory->getEntityManager()->getClassMetadata($entityClass);
                    $table = $metadata->table['name'];
                    $row = $event->getRow();

                    if ($event->getTruncated() === false) {
                        $event->setTruncated($this->truncateTable($table));
                    }


                    $row = $this->prepareForImport($metadata->fieldMappings, $row);
                    $this->importEntity($table, $row);
                }
            }
        }
    }

    /**
     * Prepare row value types for import
     *
     * @param  array $fieldMappings
     * @param  array $row
     *
     * @return array
     */
    public function prepareForImport($fieldMappings, array $row)
    {
        foreach ($fieldMappings as $mapping) {
            if (isset($row[$mapping['columnName']])) {
                switch ($mapping['type']) {
                    case 'boolean':
                        $row[$mapping['columnName']] = (int) $row[$mapping['columnName']];
                        break;
                    case 'datetime':
                        if (empty($row[$mapping['columnName']])) {
                            $row[$mapping['columnName']] = null;
                        }
                        break;
                }
            }
        }

        return $row;
    }

    /**
     * Save entity row to the database
     *
     * @param  string $table
     * @param  array  $row
     *
     * @return void
     */
    public function importEntity($table, array $row)
    {
        $em = $this->factory->getEntityManager();
        $connection = $em->getConnection();
        
        return $connection->insert($table, $row);
    }

    /**
     * Save entity row to the database
     *
     * @param  string $table
     *
     * @return void
     */
    public function truncateTable($table)
    {
        $em = $this->factory->getEntityManager();
        $connection = $em->getConnection();

        $query = $connection->prepare('TRUNCATE TABLE ' . $table . '');

        return $query->execute();
    }

    /**
     * Get rows from a Entity
     *
     * @param  string  $bundleName
     * @param  string  $entityName
     * @param  integer $limit
     * @param  integer $offset
     * @param  array   $orderByKeys
     *
     * @return array
     */
    public function getEntities($bundleName, $entityName, $limit, $offset, $orderByKeys = array('id'))
    {
        $em = $this->factory->getEntityManager();
        $query = $em->getConnection()->createQueryBuilder();
        $metadata = $em->getClassMetadata($this->classPrefix . '\\' . $bundleName . '\\Entity\\' . $entityName);

        $query->select('e.*')
            ->from($metadata->table['name'], 'e')
            ->orderBy('e.' . implode(', e.', $orderByKeys), 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        return $query->execute()->fetchAll();
    }

    /**
     * Listen to import progress and generate warnings
     *
     * @param  MigrationImportViewEvent $event
     *
     * @return array
     */
    public function onImportProgressGenerate(MigrationImportViewEvent $event)
    {
        $blueprint = $event->getBlueprint();
        $translator = $this->factory->getTranslator();

        if (!empty($blueprint['entities'])) {
            foreach ($blueprint['entities'] as &$entity) {
                $count = $this->countRowsForEntity($entity['bundle'], $entity['entity']); // @todo add prefix

                if ($count) {
                    $entity['warning'] = $translator->trans('mautic.migration.import.not.empty.data.waring', array(
                        '%count%' => $count
                    ));
                }
            }
        }
        $event->setBlueprint($blueprint);
    }
}
