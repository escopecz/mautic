<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Entity\LeadEventLogRepository;
use Mautic\CampaignBundle\Event\DeleteCampaign;
use Mautic\CampaignBundle\Event\DeleteEvent;
use Mautic\CampaignBundle\Helper\CampaignConfig;
use Mautic\CampaignBundle\Model\CampaignModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignEventDeleteSubscriber implements EventSubscriberInterface
{
    /**
     * @var CampaignConfig
     */
    private $campaignConfig;

    /**
     * @var LeadEventLogRepository
     */
    private $leadEventLogRepository;

    /**
     * @var CampaignModel
     */
    private $campaignModel;

    /**
     * @var EventRepository
     */
    private $eventRepository;

    public function __construct(
        LeadEventLogRepository $leadEventLogRepository,
        CampaignConfig $campaignConfig,
        CampaignModel $campaignModel,
        EventRepository $eventRepository
    ) {
        $this->campaignConfig             = $campaignConfig;
        $this->leadEventLogRepository     = $leadEventLogRepository;
        $this->campaignModel              = $campaignModel;
        $this->eventRepository            = $eventRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CampaignEvents::DELETE_RECORDS_ON_CAMPAIGN_DELETE => ['onCampaignDelete', 0],
            CampaignEvents::DELETE_RECORDS_ON_EVENT_DELETE    => ['onEventDelete', 0],
        ];
    }

    public function onCampaignDelete(DeleteCampaign $event): void
    {
        if ($this->campaignConfig->shouldDeleteEventLogInBackground()) {
            return;
        }

        $campaignId = $event->getCampaign()->getId();
        $this->leadEventLogRepository->removeEventLogsByCampaignId($campaignId);
        $this->eventRepository->deleteEventsByCampaignId($campaignId);
        $this->campaignModel->deleteCampaign($event->getCampaign());
    }

    public function onEventDelete(DeleteEvent $event): void
    {
        if ($this->campaignConfig->shouldDeleteEventLogInBackground()) {
            return;
        }
        $eventIds   = $event->getEventIds();
        $this->leadEventLogRepository->removeEventLogs($eventIds);
        $this->eventRepository->deleteEventsByEventIds($eventIds);
    }
}
