<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Security\Permissions;

use Symfony\Component\Form\FormBuilderInterface;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;

/**
 * Class MigrationPermissions
 *
 * @package Mautic\MigrationBundle\Security\Permissions\MigrationPermissions
 */
class MigrationPermissions extends AbstractPermissions
{

    /**
     * {@inheritdoc}
     */
    public function __construct($params)
    {
        parent::__construct($params);
        $this->addStandardPermissions('migrations');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'migration';
    }

    /**
     * {@inheritdoc}
     */
    // public function buildForm(FormBuilderInterface &$builder, array $options, array $data)
    // {
    //     $this->addStandardFormFields('migration', 'migrations', $builder, $data, false);

    //     $builder->add('migration:clients', 'permissionlist', array(
    //         'choices'    => array(
    //             'editname'     => 'mautic.migration.clients.permissions.editname',
    //             'full'         => 'mautic.migration.clients.permissions.editall',
    //         ),
    //         'label'      => 'mautic.migration.permissions.clients',
    //         'data'       => (!empty($data['clients']) ? $data['clients'] : array()),
    //         'bundle'     => 'migration',
    //         'level'      => 'clients'
    //     ));
    // }
}
