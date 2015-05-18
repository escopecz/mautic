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
     * @var array
     */
    private $bundles = array();

    /**
     * @var array
     */
    private $entities = array();

    /**
     * @var array
     */
    private $folders = array();

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
     * Set new bundle to the bundles array
     *
     * @param array $bundle
     *
     * @return void
     */
    public function addBundle ($bundle)
    {
        $this->bundles[] = $bundle;
    }

    /**
     * Set new entity to the entities[$bundle] array
     *
     * @param string $bundle
     * @param string $entity
     *
     * @return void
     */
    public function addEntity ($bundle, $entity)
    {
        if (!isset($this->entities[$bundle])) {
            $this->entities[$bundle] = array();
        }

        $this->entities[$bundle][$bundle . '.' . $entity] = $entity;
    }

    /**
     * Set new folder to the folders[$folder] array
     *
     * @param string $bundle
     * @param string $folder
     *
     * @return void
     */
    public function addFolder ($bundle, $folder)
    {
        if (!isset($this->folders[$bundle])) {
            $this->folders[$bundle] = array();
        }

        $this->folders[$bundle][$bundle . '.' . $folder] = $folder;
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
     * Returns the entities array
     *
     * @return array
     */
    public function getEntities ()
    {
        return $this->entities;
    }

    /**
     * Returns the folders array
     *
     * @return array
     */
    public function getFolders ()
    {
        return $this->folders;
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
