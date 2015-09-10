<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, Na. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\MigrationBundle\Entity;

use Doctrine\ORM\Tools\Pagination\Paginator;
use Mautic\CoreBundle\Entity\CommonRepository;

/**
 * Class MigrationRepository
 *
 * @package Mautic\MigrationBundle\Entity
 */
class MigrationRepository extends CommonRepository
{

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return array(
            array($this->getTableAlias() . '.name', 'ASC')
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'm';
    }

    /**
     * Count all downloades
     *
     * @return integer
     */
    public function count()
    {
        $count = $this->createQueryBuilder('m')
            ->select('count(d.id)')
            ->getQuery()
            ->getSingleScalarResult();

        return $count;
    }
}
