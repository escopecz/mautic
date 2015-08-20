<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Event;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class MigrationEvent
 * For gathering migration data and exporting/importing.
 *
 * @package Mautic\MigrationBundle\Event
 */
class MigrationEvent extends Event
{
    /**
     * @var array
     */
    private $entities;

    /**
     * @var string
     */
    private $bundle;

    /**
     * @var string
     */
    private $entity;

    /**
     * @var integer
     */
    private $start;

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * Consctructor
     *
     * @param MauticFactory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Set entities
     *
     * @param array $entities
     *
     * @return MigrationEvent
     */
    public function setEntities($entities)
    {
        $this->entities = $entities;

        return $this;
    }

    /**
     * Set bundle neme
     *
     * @param string $bundle
     *
     * @return MigrationEvent
     */
    public function setBundle($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Set entity name
     *
     * @param string $entity
     *
     * @return MigrationEvent
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Set from which entity we should start
     *
     * @param integer $start
     *
     * @return MigrationEvent
     */
    public function setStart($start)
    {
        $this->start = (int) $start;

        return $this;
    }

    /**
     * Set limit of entites
     *
     * @param integer $limit
     *
     * @return MigrationEvent
     */
    public function setLimit($limit)
    {
        $this->limit = (int) $limit;

        return $this;
    }

    /**
     * Returns entities
     *
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * Returns the bundle name
     *
     * @return string
     */
    public function getBundle()
    {
        return $this->bundle;
    }

    /**
     * Returns the entity name
     *
     * @return string
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * Returns the start
     *
     * @return integer
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Returns limit
     *
     * @return integer
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * Returns the factory
     *
     * @return MauticFactory
     */
    public function getFactory()
    {
        return $this->factory;
    }
}
