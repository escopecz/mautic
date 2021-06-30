<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        https://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\Exception\SkipMigration;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CoreBundle\Doctrine\AbstractMauticMigration;

final class Version20210217115150 extends AbstractMauticMigration
{
    public function preUp(Schema $schema): void
    {
        if ($schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))->hasColumn('deleted') &&
            $schema->getTable($this->getPrefixedTableName(Event::TABLE_NAME))->hasColumn('deleted')
        ) {
            throw new SkipMigration('Deleted column already added in tables');
        }
    }

    public function up(Schema $schema): void
    {
        $schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))
            ->addColumn('deleted', Types::DATETIME_MUTABLE, ['notnull' => false]);

        $schema->getTable($this->getPrefixedTableName(Event::TABLE_NAME))
            ->addColumn('deleted', Types::DATETIME_MUTABLE, ['notnull' => false]);
    }

    private function getPrefixedTableName(string $tableName): string
    {
        return $this->prefix.$tableName;
    }
}
