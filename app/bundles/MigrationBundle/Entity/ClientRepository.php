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
 * Class ClientRepository
 *
 * @package Mautic\MigrationBundle\Entity
 */
class ClientRepository extends CommonRepository
{

    /**
     * @return string
     */
    protected function getDefaultOrder()
    {
        return array(
            array('c.title', 'ASC')
        );
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTableAlias()
    {
        return 'c';
    }
}
