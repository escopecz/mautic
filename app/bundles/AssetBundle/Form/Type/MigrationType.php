<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\AssetBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Mautic\CoreBundle\Form\DataTransformer\ArrayStringTransformer;

/**
 * Class MigrationType
 *
 * @package Mautic\AssetBundle\Form\Type
 */
class MigrationType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('entities', 'choice', array(
            'choices'       => array(
                'asset' => array(
                    'assets'    => 'mautic.asset.migration.form.assets',
                    'hits'      => 'mautic.asset.migration.form.hits',
                    'files'     => 'mautic.asset.migration.form.files'
                )
            ),
            'multiple'      => true,
            'label'         => 'mautic.asset.migration.form.select.entities',
            'label_attr'    => array('class' => 'control-label'),
            'attr'          => array(
                'class'     => 'form-control',
                'tooltip'   => 'mautic.asset.migration.form.select.entities.desc'
            )
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'assetmigration';
    }
}