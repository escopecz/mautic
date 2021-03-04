<?php

declare(strict_types=1);

/*
 * @copyright 2021 Mautic Contributors. All rights reserved
 * @author Mautic
 *
 * @link http://mautic.org
 *
 * @license GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Tests\Command;

use Mautic\CampaignBundle\Command\CampaignDeleteEventLogsCommand;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\LeadEventLog;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;

class CampaignDeleteEventLogsCommandFunctionalTest extends MauticMysqlTestCase
{
    public function testWithoutEventIds(): void
    {
        $applicationTester = $this->createApplicationTester();

        $exitCode = $applicationTester->run(['command' => CampaignDeleteEventLogsCommand::COMMAND_NAME]);
        Assert::assertSame(1, $exitCode);
        Assert::assertStringContainsString('Not enough arguments (missing: "campaign_event_ids")', $applicationTester->getDisplay());
    }

    public function testWithEventIds(): void
    {
        $applicationTester = $this->createApplicationTester();
        $lead              = $this->createLead();
        $campaign          = $this->createCampaign();
        $event1            = $this->createEvent('Event 1', $campaign);
        $event2            = $this->createEvent('Event 2', $campaign);
        $this->createEventLog($lead, $event1);
        $this->createEventLog($lead, $event2);

        $exitCode = $applicationTester->run(['command' => CampaignDeleteEventLogsCommand::COMMAND_NAME, 'campaign_event_ids' => [$event1->getId(), $event2->getId()]]);
        Assert::assertSame(0, $exitCode);

        $campaign = $this->em->getRepository(Campaign::class)->findAll();
        Assert::assertCount(1, $campaign);

        $eventLogs = $this->em->getRepository(LeadEventLog::class)->findAll();
        Assert::assertCount(0, $eventLogs);
    }

    public function testWithEventIdsAndCampaignId(): void
    {
        $applicationTester = $this->createApplicationTester();
        $lead              = $this->createLead();
        $campaign          = $this->createCampaign();
        $event1            = $this->createEvent('Event 1', $campaign);
        $event2            = $this->createEvent('Event 2', $campaign);
        $this->createEventLog($lead, $event1);
        $this->createEventLog($lead, $event2);

        $exitCode = $applicationTester->run(
            [
                'command'       => CampaignDeleteEventLogsCommand::COMMAND_NAME,
                'campaign_event_ids' => [$event1->getId(), $event2->getId()],
                '--campaign-id' => $campaign->getId(),
            ]
        );

        Assert::assertSame(0, $exitCode);

        $campaign = $this->em->getRepository(Campaign::class)->findAll();
        Assert::assertCount(0, $campaign);

        $eventLogs = $this->em->getRepository(LeadEventLog::class)->findAll();
        Assert::assertCount(0, $eventLogs);
    }

    private function createApplicationTester(): ApplicationTester
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);

        return new ApplicationTester($application);
    }

    private function createLead(): Lead
    {
        $lead = new Lead();
        $lead->setFirstname('Test');
        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }

    private function createCampaign(): Campaign
    {
        $campaign = new Campaign();
        $campaign->setName('My campaign');
        $this->em->persist($campaign);
        $this->em->flush();

        return $campaign;
    }

    private function createEvent(string $name, Campaign $campaign): Event
    {
        $event = new Event();
        $event->setName($name);
        $event->setCampaign($campaign);
        $event->setType('email.send');
        $event->setEventType('action');
        $this->em->persist($event);
        $this->em->flush();

        return $event;
    }

    private function createEventLog(Lead $lead, Event $event): LeadEventLog
    {
        $leadEventLog = new LeadEventLog();
        $leadEventLog->setLead($lead);
        $leadEventLog->setEvent($event);
        $this->em->persist($leadEventLog);
        $this->em->flush();

        return $leadEventLog;
    }
}
