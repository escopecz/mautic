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
 * Class MigrationCountEvent
 * For gathering migration data and exporting/importing.
 *
 * @package Mautic\MigrationBundle\Event
 */
class MigrationCountEvent extends Event
{
    /**
     * @var string
     */
    private $entity;

    /**
     * @var string
     */
    private $bundle;

    /**
     * @var integer
     */
    private $count;

    /**
     * @var MauticFactory
     */
    private $factory;

    /**
     * Consctructor
     *
     * @param MauticFactory
     */
    public function __construct (MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * Set new entity
     *
     * @param string $entity
     *
     * @return MigrationCountEvent
     */
    public function setEntity ($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * Set new bundle
     *
     * @param string $bundle
     *
     * @return MigrationCountEvent
     */
    public function setBundle ($bundle)
    {
        $this->bundle = $bundle;

        return $this;
    }

    /**
     * Set the entity count
     *
     * @param integer count
     *
     * @return MigrationCountEvent
     */
    public function setCount ($count)
    {
        $this->count = (int) $count;

        return $this;
    }

    /**
     * Returns the entity
     *
     * @return string
     */
    public function getEntity ()
    {
        return $this->entity;
    }

    /**
     * Returns the bundle
     *
     * @return string
     */
    public function getBundle ()
    {
        return $this->bundle;
    }

    /**
     * Returns the count
     *
     * @return integer
     */
    public function getCount ()
    {
        return $this->count;
    }

    /**
     * Returns the factory
     *
     * @return MauticFactory
     */
    public function getFactory ()
    {
        return $this->factory;
    }
}
