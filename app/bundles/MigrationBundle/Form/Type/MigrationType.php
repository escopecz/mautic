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
 * Class MigrationType
 *
 * @package Mautic\MigrationBundle\Form\Type
 */
class MigrationType extends AbstractType
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
        $builder->add('title', 'text', array(
            'label'      => 'mautic.core.title',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control')
        ));

        $builder->add('description', 'textarea', array(
            'label'      => 'mautic.core.description',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control editor'),
            'required'   => false
        ));

        $event          = new MigrationEditEvent($this->factory);
        $dispatcher     = $this->factory->getDispatcher();
        $dispatcher->dispatch(MigrationEvents::MIGRATION_TEMPLATE_ON_EDIT_DISPLAY, $event);
        $eventForms     = $event->getForms();
        $eventEntities  = $event->getEntities();
        $eventFolders   = $event->getFolders();


        $builder->add('entities', 'choice', array(
            'choices'    => $eventEntities,
            'label'      => 'mautic.migration.form.entities',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false,
            'multiple'   => true
        ));

        $builder->add('folders', 'choice', array(
            'choices'    => $eventFolders,
            'label'      => 'mautic.migration.form.folders',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false,
            'multiple'   => true
        ));

        $builder->add('properties', 'event_properties', array(
            'label'         => false,
            'eventForms'    => $eventForms,
            'required'      => false,
            'data'          => $options['data']->getProperties()
        ));

        $builder->add('buttons', 'form_buttons', array());

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Mautic\MigrationBundle\Entity\Migration'
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "migration";
    }
}
