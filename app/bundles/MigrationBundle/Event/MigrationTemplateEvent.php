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
 * Class MigrationTemplateEvent
 *
 * @package Mautic\MigrationBundle\Event
 */
class MigrationTemplateEvent extends CommonEvent
{
    /**
     * @param Migration $migration
     * @param bool  $isNew
     */
    public function __construct(Migration &$migration, $isNew = false)
    {
        $this->entity =& $migration;
        $this->isNew = $isNew;
    }

    /**
     * Returns the Migration entity
     *
     * @return Migration
     */
    public function getMigration()
    {
        return $this->entity;
    }

    /**
     * Sets the Migration entity
     *
     * @param Migration $migration
     */
    public function setMigration(Migration $migration)
    {
        $this->entity = $migration;
    }
}
