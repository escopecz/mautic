<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Mautic\ApiBundle\Serializer\Driver\ApiMetadataDriver;
use Mautic\CoreBundle\Doctrine\Mapping\ClassMetadataBuilder;
use Mautic\CoreBundle\Entity\FormEntity;
use JMS\Serializer\Annotation as Serializer;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Mapping\ClassMetadata;

/**
 * Class Migration
 *
 * @package Mautic\MigrationBundle\Entity
 */
class Migration extends FormEntity
{

    /**
     * @var int
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var array
     */
    private $migrate = array();

    /**
     * @var array
     */
    private $entities = array();

    /**
     * @var array
     */
    private $folders = array();

    /**
     * @var array
     */
    private $properties = array();

    public function __clone()
    {
        $this->id = null;
    }

    /**
     * @param ORM\ClassMetadata $metadata
     */
    public static function loadMetadata (ORM\ClassMetadata $metadata)
    {
        $builder = new ClassMetadataBuilder($metadata);

        $builder->setTable('migration_templates')
            ->setCustomRepositoryClass('Mautic\MigrationBundle\Entity\MigrationRepository');

        $builder->addIdColumns();

        $builder->createField('migrate', 'array')
            ->columnName('migrate')
            ->nullable()
            ->build();

        $builder->createField('entities', 'array')
            ->columnName('entities')
            ->nullable()
            ->build();

        $builder->createField('folders', 'array')
            ->columnName('folders')
            ->nullable()
            ->build();

        $builder->createField('properties', 'array')
            ->columnName('properties')
            ->nullable()
            ->build();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Migration
     */
    public function setName($name)
    {
        $this->isChanged('name', $name);
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription ()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Migration
     */
    public function setDescription ($description)
    {
        $this->isChanged('description', $description);

        $this->description = $description;

        return $this;
    }

    /**
     * Get migrate
     *
     * @return array
     */
    public function getMigrate()
    {
        return $this->migrate;
    }

    /**
     * Set migrate
     *
     * @param array $migrate
     *
     * @return Migration
     */
    public function setMigrate($migrate)
    {
        $this->isChanged('migrate', $migrate);

        $this->migrate = $migrate;

        return $this;
    }

    /**
     * Get entities
     *
     * @return array
     */
    public function getEntities()
    {
        return $this->entities;
    }

    /**
     * Set entities
     *
     * @param array $entities
     *
     * @return Migration
     */
    public function setEntities($entities)
    {
        $this->isChanged('entities', $entities);

        $this->entities = $entities;

        return $this;
    }

    /**
     * Get folders
     *
     * @return array
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * Set folders
     *
     * @param array $folders
     *
     * @return Migration
     */
    public function setFolders($folders)
    {
        $this->isChanged('folders', $folders);

        $this->folders = $folders;

        return $this;
    }

    /**
     * Get properties
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Set properties
     *
     * @param array $properties
     *
     * @return Migration
     */
    public function setProperties($properties)
    {
        $this->isChanged('properties', $properties);

        $this->properties = $properties;

        return $this;
    }
}
