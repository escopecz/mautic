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
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Doctrine\PreUpAssertionMigration;
use Doctrine\DBAL\Types\Types;

final class Version20210217115150 extends PreUpAssertionMigration
{
    protected function preUpAssertions(): void
    {
        $this->skipAssertion(function (Schema $schema) {
            return $schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))->hasColumn('deleted')
                && $schema->getTable($this->getPrefixedTableName(Event::TABLE_NAME))->hasColumn('deleted');
        }, 'Migration already executed');
    }

    public function up(Schema $schema): void
    {
        if (!$schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))->hasColumn('deleted'))
        {
            $schema->getTable($this->getPrefixedTableName(Campaign::TABLE_NAME))
                ->addColumn('deleted', Types::DATETIME_MUTABLE, ['notnull' => false]);
        }

        if (!$schema->getTable($this->getPrefixedTableName(Event::TABLE_NAME))->hasColumn('deleted'))
        {
            $schema->getTable($this->getPrefixedTableName(Event::TABLE_NAME))
                ->addColumn('deleted', Types::DATETIME_MUTABLE, ['notnull' => false]);
        }
    }
}
