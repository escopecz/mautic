<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Exception;

class ApiErrorException extends \Exception
{

    public function __construct($message = 'API error', $code = 0, \Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
