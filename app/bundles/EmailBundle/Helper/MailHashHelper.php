<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Helper;

use Mautic\CoreBundle\Helper\CoreParametersHelper;

class MailHashHelper
{
    private CoreParametersHelper $coreParametersHelper;

    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function getEmailHash(string $email): string
    {
        $secret = $this->coreParametersHelper->get('secret_key');

        return self::getEmailHashForSecret($email, $secret);
    }

    public static function getEmailHashForSecret(string $email, string $secret): string
    {
        return hash_hmac('sha256', $email, $secret);
    }
}
