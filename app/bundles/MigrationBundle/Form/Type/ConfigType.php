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
use Symfony\Component\Validator\Constraints\NotBlank;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;

/**
 * Class ConfigType
 *
 * @package Mautic\MigrationBundle\Form\Type
 */
class ConfigType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('export_dir', 'text', array(
            'label'       => 'mautic.migration.config.form.export.dir',
            'label_attr'  => array('class' => 'control-label'),
            'attr'        => array(
                'class' => 'form-control',
                'tooltip' => 'mautic.migration.config.form.export.dir.tooltip'
                ),
            'constraints' => array(
                new NotBlank(array(
                    'message' => 'mautic.core.value.required'
                ))
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'migrationconfig';
    }
}
