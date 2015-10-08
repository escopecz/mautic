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
 * Class MigrationImportViewEvent
 *
 * @package Mautic\MigrationBundle\Event
 */
class MigrationImportViewEvent extends CommonEvent
{
    protected $blueprint;

    /**
     * @param array $blueprint
     */
    public function __construct(array $blueprint)
    {
        $this->blueprint = $blueprint;
    }

    /**
     * Returns the blueprint array
     *
     * @return array
     */
    public function getBlueprint()
    {
        return $this->blueprint;
    }

    /**
     * Sets the blueprint array
     *
     * @param array $blueprint
     */
    public function setBlueprint(array $blueprint)
    {
        $this->blueprint = $blueprint;
    }
}
