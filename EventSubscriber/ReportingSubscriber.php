<?php

/*
 * This file is part of the "CompactWeekBundle" for Kimai.
 * All rights reserved by Christoph Vollmann (https://cloudchristoph.com).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\CompactWeekBundle\EventSubscriber;

use App\Event\ReportingEvent;
use App\Reporting\Report;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class ReportingSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly AuthorizationCheckerInterface $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ReportingEvent::class => ['onReporting'],
        ];
    }

    public function onReporting(ReportingEvent $event): void
    {
        if (!$this->security->isGranted('view_reporting')) {
            return;
        }

        if (!$this->security->isGranted('report:user')) {
            return;
        }

        $event->addReport(new Report('compact_week', 'report_compact_week', 'report_compact_week', 'user'));
    }
}
