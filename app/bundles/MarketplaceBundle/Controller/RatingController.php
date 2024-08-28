<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Controller;

use Doctrine\Persistence\ManagerRegistry;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Factory\ModelFactory;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\CoreBundle\Service\FlashBag;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\MarketplaceBundle\Form\Type\RatingType;
use Mautic\MarketplaceBundle\Security\Permissions\MarketplacePermissions;
use Mautic\MarketplaceBundle\Service\Allowlist;
use Mautic\MarketplaceBundle\Service\Config;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

class RatingController extends CommonController
{
    public function __construct(
        private Config $config,
        private Allowlist $allowlist,
        ManagerRegistry $doctrine,
        MauticFactory $factory,
        ModelFactory $modelFactory,
        UserHelper $userHelper,
        CoreParametersHelper $coreParametersHelper,
        EventDispatcherInterface $dispatcher,
        Translator $translator,
        FlashBag $flashBag,
        RequestStack $requestStack,
        CorePermissions $security
    ) {
        parent::__construct($doctrine, $factory, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

    public function rateAction(): Response
    {
        if (!$this->config->marketplaceIsEnabled()) {
            return $this->notFound();
        }

        if (!$this->security->isGranted(MarketplacePermissions::CAN_VIEW_PACKAGES)) {
            return $this->accessDenied();
        }

        $form = $this->createForm(
            RatingType::class,
            // null,
            // [
            //     'type'         => ContentPreviewSettingsType::TYPE_EMAIL,
            //     'objectId'     => $email->getId(),
            //     'variants'     => $variants,
            //     'translations' => $translations,
            // ]
        )->createView();
        return $this->delegateView(
            [
                'viewParameters' => [
                    'form' => $form,
                ],
                'contentTemplate' => '@Marketplace/Package/rating.html.twig',
            ]
        );
    }
}
