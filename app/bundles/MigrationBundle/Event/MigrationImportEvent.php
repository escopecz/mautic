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
    * @param string $bundle
    * @param string $entity
    * @param array  $blueprint
     */
    public function __construct($bundle, $entity, array $row)
    {
        $this->bundle = $bundle;
        $this->entity = $entity;
        $this->row = $row;
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
}
