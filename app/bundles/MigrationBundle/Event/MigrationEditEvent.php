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
 * Class MigrationEditEvent
 *
 * @package Mautic\MigrationBundle\Event
 */
class MigrationEditEvent extends Event
{

    /**
     * @var array
     */
    private $forms = array();

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
     * Set new form to the forms array
     *
     * @param array $form
     *
     * @return void
     */
    public function addForm ($form)
    {
        $this->forms[$form['formAlias']] = $form;
    }

    /**
     * Returns the forms array
     *
     * @return array
     */
    public function getForms ()
    {
        return $this->forms;
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
