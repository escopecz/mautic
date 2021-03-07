<?php

declare(strict_types=1);

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Tests\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\FormBundle\Form\Type\FormFieldConditionType;
use Mautic\FormBundle\Helper\PropertiesAccessor;
use Mautic\FormBundle\Model\FieldModel;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class FormFieldConditionTypeTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MockObject|FieldModel
     */
    private $fieldModel;

    /**
     * @var MockObject|PropertiesAccessor
     */
    private $propertiesAccessor;

    /**
     * @var MockObject|FormBuilderInterface
     */
    private $formBuilder;

    /**
     * @var FormFieldConditionType
     */
    private $form;

    protected function setUp(): void
    {
        $this->fieldModel         = $this->createMock(FieldModel::class);
        $this->propertiesAccessor = $this->createMock(PropertiesAccessor::class);
        $this->formBuilder        = $this->createMock(FormBuilderInterface::class);
        $this->form               = new FormFieldConditionType(
            $this->fieldModel,
            $this->propertiesAccessor
        );
    }

    public function testBuildFormIfParentIsEmpty(): void
    {
        $options = [];

        $this->fieldModel->expects($this->never())
            ->method('getSessionFields');

        $this->propertiesAccessor->expects($this->never())
            ->method('getChoices');

        $this->formBuilder->expects($this->at(1))
            ->method('add')
            ->with(
                'values',
                ChoiceType::class,
                [
                    'choices'  => [],
                    'multiple' => true,
                    'label'    => false,
                    'attr'     => [
                        'class'        => 'form-control',
                        'data-show-on' => '{"formfield_conditions_any_0": "checked","formfield_conditions_expr": "notIn"}',
                    ],
                    'required' => false,
                ]
            );

        $this->formBuilder->expects($this->at(2))
            ->method('add')
            ->with(
                'any',
                YesNoButtonGroupType::class,
                [
                    'label' => 'mautic.form.field.form.condition.any_value',
                    'attr'  => [
                        'data-show-on' => '{"formfield_conditions_expr": "in"}',
                    ],
                    'data' => isset($options['data']['any']) ? $options['data']['any'] : false,
                ]
            );

        $this->formBuilder->expects($this->at(3))
            ->method('add')
            ->with(
                'expr',
                ChoiceType::class,
                [
                    'choices'  => [
                        'mautic.core.operator.in'    => 'in',
                        'mautic.core.operator.notin' => 'notIn',
                    ],
                    'label'       => false,
                    'placeholder' => false,
                    'attr'        => [
                        'class' => 'form-control',
                    ],
                    'required' => false,
                ]
            );

        $this->form->buildForm($this->formBuilder, $options);
    }

    public function testBuildFormIfParentExists(): void
    {
        $options = [
            'parent' => 'parent_field_id',
            'formId' => 'form_id',
        ];

        $this->fieldModel->expects($this->once())
            ->method('getSessionFields')
            ->with('form_id')
            ->willReturn(['parent_field_id' => ['some_field_props_here']]);

        $this->propertiesAccessor->expects($this->once())
            ->method('getProperties')
            ->with(['some_field_props_here'])
            ->willReturn(['some_choice_here' => 'Some choice here']);

        $this->propertiesAccessor->expects($this->once())
            ->method('getChoices')
            ->with(['some_choice_here' => 'Some choice here'])
            ->willReturn(['some_choice_here' => 'Some choice here']);

        $this->formBuilder->expects($this->at(0))
            ->method('add')
            ->with(
                'values',
                ChoiceType::class,
                [
                    'choices'  => ['some_choice_here' => 'Some choice here'],
                    'multiple' => true,
                    'label'    => false,
                    'attr'     => [
                        'class'        => 'form-control',
                        'data-show-on' => '{"formfield_conditions_any_0": "checked","formfield_conditions_expr": "notIn"}',
                    ],
                    'required' => false,
                ]
            );

        $this->form->buildForm($this->formBuilder, $options);
    }
}
