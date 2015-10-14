<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\MigrationBundle\Entity\Migration;

/**
 * Class MigrationImportEvent
 *
 * @package Mautic\MigrationBundle\Event
 */
class MigrationImportEvent extends CommonEvent
{
    /**
     * @var string
     */
    protected $entity;

    /**
     * @var string
     */
    protected $bundle;

    /**
     * @var array
     */
    protected $row;

    /**
     * @var boolean
     */
    protected $truncated;

    /**
    * @param string  $bundle
    * @param string  $entity
    * @param array   $blueprint
    * @param boolean $truncated
     */
    public function __construct($bundle, $entity, array $row, $truncated)
    {
        $this->bundle    = $bundle;
        $this->entity    = $entity;
        $this->row       = $row;
        $this->truncated = $truncated;
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
     * Returns the row (array) of entity data
     *
     * @return array
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * Returns the flag if the old data has already been truncated or not
     *
     * @return boolean
     */
    public function getTruncated()
    {
        return $this->truncated;
    }

    /**
     * Set the flag if the old data has already been truncated or not
     *
     * @return $this
     */
    public function setTruncated($truncated)
    {
        $this->truncated = $truncated;
        return $this;
    }
}
