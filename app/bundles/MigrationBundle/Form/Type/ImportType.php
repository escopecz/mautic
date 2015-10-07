<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ImportType
 *
 * @package Mautic\MigrationBundle\Form\Type
 */
class ImportType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['blueprint']['entities'])) {
            $builder->add('entities', 'migration_import_entities', array(
                'entities' => $options['blueprint']['entities']
            ));
        }

        $builder->add('start', 'submit', array(
            'attr'  => array(
                'class'   => 'btn btn-primary pull-right',
                'icon'    => 'fa fa-upload',
                'onclick' => "mQuery(this).prop('disabled', true); mQuery('form[name=\'migration_import\']').submit();"
            ),
            'label' => 'mautic.migration.import.btn'
        ));

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('blueprint'));
    }

    /**
     * @return string
     */
    public function getName ()
    {
        return "migration_import";
    }
}
