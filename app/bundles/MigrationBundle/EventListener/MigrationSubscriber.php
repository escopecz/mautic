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
                    $entities = $this->getEntities($event, $entity);
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
                    $this->importEntity($event->getBundle(), $event->getEntity(), $event->getRow());
                }
            }
        }
    }

    /**
     * Save entity row to the database
     *
     * @param  string $bundleName
     * @param  string $entityName
     * @param  array  $row
     *
     * @return void
     */
    public function importEntity($bundleName, $entityName, array $row)
    {
        $entityClass = '\\' . $this->classPrefix . '\\' . $bundleName . '\\Entity\\' . $entityName;
        $entity = new $entityClass;
        $entity = $this->populateEntityFromArray($entity, $row);
        $em = $this->factory->getEntityManager();
        $em->persist($entity);
        $em->flush();
    }

    /**
     * @param $className
     * @param $data
     *
     * @return mixed
     * @throws \Doctrine\ORM\Mapping\MappingException
     * @throws \Exception
     */
    public function populateEntityFromArray($entity, $data)
    {
        foreach ($data as $column => $value) {
            $column = $this->underscoresToCamelCase($column);
            $setter = 'set' . $column;
            if (method_exists($entity, $setter)) {
                $entity->$setter($value);
            }
        }

        return $entity;
    }

    public function underscoresToCamelCase($string)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $string)));
    }

    /**
     * Get rows from a Entity
     *
     * @param  MigrationEvent $event
     * @param  string         $entityName
     * @param  string         $KeyName
     *
     * @return array
     */
    public function getEntities($event, $entityName, $keyName = 'id')
    {
        $tableAlias = 'ta';
        $q = $event->getFactory()->getEntityManager()
            ->getRepository($this->classPrefix . $this->bundleName . ':' . $entityName)
            ->createQueryBuilder($tableAlias);

        return $q
            ->setMaxResults($event->getLimit())
            ->setFirstResult($event->getStart())
            ->orderBy($tableAlias . '.' . $keyName)
            ->getQuery()
            ->getArrayResult(Query::HYDRATE_SCALAR);
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
