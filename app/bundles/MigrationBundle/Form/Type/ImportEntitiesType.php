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
 * Class ImportEntitiesType
 *
 * @package Mautic\MigrationBundle\Form\Type
 */
class ImportEntitiesType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['entities'])) {
            foreach ($options['entities'] as $key => $entity) {
                $key = str_replace('.', ':', $key);

                if (isset($options['data'][$key])) {
                    $value = $options['data'][$key];
                } elseif (isset($entity['allow_import'])) {
                    $value = $entity['allow_import'];
                } elseif (!empty($entity['warnings'])) {
                    $value = false;
                } else {
                    $value = true;
                }

                $builder->add($key, 'yesno_button_group', array(
                    'label'       => 'mautic.migration.import.this.entity',
                    'data'        => (bool) $value
                ));
            }
        }

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('entities'));
    }

    /**
     * @return string
     */
    public function getName ()
    {
        return "migration_import_entities";
    }
}
