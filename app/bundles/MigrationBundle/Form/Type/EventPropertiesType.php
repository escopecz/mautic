<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Mautic\MigrationBundle\MigrationEvents;
use Mautic\MigrationBundle\Event\MigrationEditEvent;

/**
 * Class EventPropertiesType
 *
 * @package Mautic\MigrationBundle\Form\Type
 */
class EventPropertiesType extends AbstractType
{

    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        if (isset($options['eventForms']) && is_array($options['eventForms'])) {
            foreach ($options['eventForms'] as $form) {
                if (isset($form['formAlias'])) {
                    $builder->add($form['formAlias'], $form['formAlias'], array(
                        'data' => isset($options['data'][$form['formAlias']]) ? $options['data'][$form['formAlias']] : null,
                        'label' => false
                    ));
                }
            }
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(array('eventForms'));
    }

    /**
     * @return string
     */
    public function getName() {
        return "event_properties";
    }
}
