<?php

namespace MauticPlugin\MauticFocusBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use Mautic\FormBundle\Entity\Form;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Mapping\ClassMetadata;

class Focus extends FormEntity
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @var string|null
     */
    private $editor;

    /**
     * @var string|null
     */
    private $html;

    /**
     * @var string|null
     */
    private $htmlMode;

    /**
     * @var string
     */
    private $name;

    private $category;

    /**
     * @var string
     */
    private $type;

    /**
     * @var string|null
     */
    private $website;

    /**
     * @var string
     */
    private $style;

    /**
     * @var \DateTimeInterface
     */
    private $publishUp;

    /**
     * @var \DateTimeInterface
     */
    private $publishDown;

    private $properties = [];

    /**
     * @var array
     */
    private $utmTags = [];

    /**
     * @var int|null
     */
    private $form;

    /**
     * @var string|null
     */
    private $cache;

    public static function loadValidatorMetadata(ClassMetadata $metadata): void
    {
        $metadata->addPropertyConstraint(
            'name',
            new NotBlank(
                [
                    'message' => 'mautic.core.name.required',
                ]
            )
        );

        $metadata->addPropertyConstraint(
            'type',
            new NotBlank(
                ['message' => 'mautic.focus.error.select_type']
            )
        );

        $metadata->addPropertyConstraint(
            'style',
            new NotBlank(
                ['message' => 'mautic.focus.error.select_style']
            )
        );
    }

    public function __clone()
    {
        $this->id = null;

        parent::__clone();
    }

    public static function loadMetadata(ORM\ClassMetadata $metadata): void
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('focus')
            ->setCustomRepositoryClass(FocusRepository::class)
            ->addIndex(['focus_type'], 'focus_type')
            ->addIndex(['style'], 'focus_style')
            ->addIndex(['form_id'], 'focus_form')
            ->addIndex(['name'], 'focus_name');

        $builder->addIdColumns();

        $builder->addCategory();

        $builder->addNamedField('type', 'string', 'focus_type');

        $builder->addField('style', 'string');

        $builder->addNullableField('website', 'string');

        $builder->addPublishDates();

        $builder->addNullableField('properties', 'array');

        $builder->createField('utmTags', 'array')
            ->columnName('utm_tags')
            ->nullable()
            ->build();

        $builder->addNamedField('form', 'integer', 'form_id', true);

        $builder->addNullableField('cache', 'text');

        $builder->createField('htmlMode', 'string')
            ->columnName('html_mode')
            ->nullable()
            ->build();

        $builder->addNullableField('editor', 'text');

        $builder->addNullableField('html', 'text');
    }

    /**
     * Prepares the metadata for API usage.
     */
    public static function loadApiMetadata(ApiMetadataDriver $metadata): void
    {
        $metadata
            ->addListProperties(
                [
                    'id',
                    'name',
                    'category',
                ]
            )
            ->addProperties(
                [
                    'description',
                    'type',
                    'website',
                    'style',
                    'publishUp',
                    'publishDown',
                    'properties',
                    'utmTags',
                    'form',
                    'htmlMode',
                    'html',
                    'editor',
                    'cache',
                ]
            )
            ->build();
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @return Focus
     */
    public function setDescription($description)
    {
        $this->isChanged('description', $description);

        $this->description = $description;

        return $this;
    }

    public function getEditor()
    {
        return $this->editor;
    }

    /**
     * @return Focus
     */
    public function setEditor($editor)
    {
        $this->isChanged('editor', $editor);

        $this->editor = $editor;

        return $this;
    }

    public function getHtml()
    {
        return $this->html;
    }

    /**
     * @return Focus
     */
    public function setHtml($html)
    {
        $this->isChanged('html', $html);

        $this->html = $html;

        return $this;
    }

    public function getHtmlMode()
    {
        return $this->htmlMode;
    }

    /**
     * @return Focus
     */
    public function setHtmlMode($htmlMode)
    {
        $this->isChanged('htmlMode', $htmlMode);

        $this->htmlMode = $htmlMode;

        return $this;
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Focus
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);

        $this->name = $name;

        return $this;
    }

    public function getCategory()
    {
        return $this->category;
    }

    /**
     * @return Focus
     */
    public function setCategory($category)
    {
        $this->isChanged('category', $category);

        $this->category = $category;

        return $this;
    }

    public function getPublishUp()
    {
        return $this->publishUp;
    }

    /**
     * @return Focus
     */
    public function setPublishUp($publishUp)
    {
        $this->isChanged('publishUp', $publishUp);

        $this->publishUp = $publishUp;

        return $this;
    }

    public function getPublishDown()
    {
        return $this->publishDown;
    }

    /**
     * @return Focus
     */
    public function setPublishDown($publishDown)
    {
        $this->isChanged('publishDown', $publishDown);

        $this->publishDown = $publishDown;

        return $this;
    }

    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * @return Focus
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;

        return $this;
    }

    /**
     * @return array
     */
    public function getUtmTags()
    {
        return $this->utmTags;
    }

    /**
     * @param array $utmTags
     */
    public function setUtmTags($utmTags)
    {
        $this->isChanged('utmTags', $utmTags);
        $this->utmTags = $utmTags;

        return $this;
    }

    public function getType()
    {
        return $this->type;
    }

    /**
     * @return Focus
     */
    public function setType($type)
    {
        $this->isChanged('type', $type);

        $this->type = $type;

        return $this;
    }

    public function getStyle()
    {
        return $this->style;
    }

    /**
     * @return Focus
     */
    public function setStyle($style)
    {
        $this->isChanged('style', $style);

        $this->style = $style;

        return $this;
    }

    public function getWebsite()
    {
        return $this->website;
    }

    /**
     * @return Focus
     */
    public function setWebsite($website)
    {
        $this->isChanged('website', $website);

        $this->website = $website;

        return $this;
    }

    public function getForm()
    {
        return $this->form;
    }

    /**
     * @return Focus
     */
    public function setForm($form)
    {
        if ($form instanceof Form) {
            $form = $form->getId();
        }

        $this->isChanged('form', $form);

        $this->form = $form;

        return $this;
    }

    public function getCache()
    {
        return $this->cache;
    }

    /**
     * @return Focus
     */
    public function setCache($cache)
    {
        $this->cache = $cache;

        return $this;
    }
}
