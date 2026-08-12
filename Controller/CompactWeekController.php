<?php

/*
 * This file is part of the "CompactWeekBundle" for Kimai.
 * All rights reserved by Christoph Vollmann (https://cloudchristoph.com).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\CompactWeekBundle\Controller;

use App\Controller\AbstractController;
use App\Entity\Project;
use App\Model\DailyStatistic;
use App\Model\Statistic\StatisticDate;
use App\Repository\ProjectRepository;
use App\Repository\Query\TimesheetStatisticQuery;
use App\Timesheet\TimesheetStatisticService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/reporting/user')]
#[IsGranted('view_reporting')]
#[IsGranted('report:user')]
final class CompactWeekController extends AbstractController
{
    /**
     * Allowed rounding modes: minutes and whether to round up or to the nearest interval.
     * The key is used as URL parameter and translation suffix.
     *
     * @var array<string, array{minutes: int, up: bool}>
     */
    private const ROUNDING_MODES = [
        'none' => ['minutes' => 0, 'up' => false],
        'nearest_15' => ['minutes' => 15, 'up' => false],
        'nearest_30' => ['minutes' => 30, 'up' => false],
        'nearest_60' => ['minutes' => 60, 'up' => false],
        'up_15' => ['minutes' => 15, 'up' => true],
        'up_30' => ['minutes' => 30, 'up' => true],
        'up_60' => ['minutes' => 60, 'up' => true],
    ];

    public function __construct(
        private readonly TimesheetStatisticService $statisticService,
        private readonly ProjectRepository $projectRepository
    ) {
    }

    #[Route(path: '/compact-week', name: 'report_compact_week', methods: ['GET'])]
    public function report(Request $request): Response
    {
        $user = $this->getUser();
        $dateTimeFactory = $this->getDateTimeFactory($user);

        $date = null;
        $dateParam = $request->query->get('date');
        if (\is_string($dateParam) && $dateParam !== '') {
            $date = $dateTimeFactory->createDateTimeFromFormat('Y-m-d', $dateParam);
            if ($date === false) {
                $date = null;
            }
        }

        $decimal = $request->query->getBoolean('decimal', true);

        $rounding = $request->query->getString('rounding', 'none');
        if (!\array_key_exists($rounding, self::ROUNDING_MODES)) {
            $rounding = 'none';
        }
        $roundingMode = self::ROUNDING_MODES[$rounding];

        $start = $dateTimeFactory->getStartOfWeek($date);
        $end = $dateTimeFactory->getEndOfWeek($date);

        $previous = clone $start;
        $previous->modify('-1 week');

        $next = clone $start;
        $next->modify('+1 week');

        $stats = $this->statisticService->getDailyStatisticsGrouped(new TimesheetStatisticQuery($start, $end, [$user]));
        $userData = array_pop($stats) ?? [];

        $days = [];
        $period = new \DatePeriod(\DateTimeImmutable::createFromInterface($start), new \DateInterval('P1D'), 6);
        foreach ($period as $day) {
            $days[$day->format('Y-m-d')] = $day;
        }

        $rows = [];
        $dayTotals = array_fill_keys(array_keys($days), 0);
        $grandTotal = 0;

        foreach ($userData as $projectId => $projectValues) {
            $project = $this->projectRepository->find($projectId);
            if (!$project instanceof Project) {
                continue;
            }

            $durations = array_fill_keys(array_keys($days), 0);
            $total = 0;

            foreach ($projectValues['activities'] as $activityValues) {
                /** @var DailyStatistic $dailyStatistic */
                $dailyStatistic = $activityValues['data'];
                /** @var StatisticDate $statisticDate */
                foreach ($dailyStatistic->getData() as $statisticDate) {
                    $duration = $statisticDate->getTotalDuration();
                    if ($duration === 0) {
                        continue;
                    }
                    $dateKey = $statisticDate->getDate()->format('Y-m-d');
                    if (!\array_key_exists($dateKey, $durations)) {
                        continue;
                    }
                    $durations[$dateKey] += $duration;
                }
            }

            // round each cell first, totals are calculated from the rounded
            // values afterwards, so they always match what is displayed
            foreach ($durations as $dateKey => $duration) {
                $duration = $this->roundDuration($duration, $roundingMode['minutes'], $roundingMode['up']);
                $durations[$dateKey] = $duration;
                $dayTotals[$dateKey] += $duration;
                $total += $duration;
                $grandTotal += $duration;
            }

            $rows[] = [
                'project' => $project,
                'durations' => $durations,
                'total' => $total,
            ];
        }

        usort($rows, function (array $a, array $b): int {
            $customerCompare = strcasecmp(
                (string) $a['project']->getCustomer()?->getName(),
                (string) $b['project']->getCustomer()?->getName()
            );
            if ($customerCompare !== 0) {
                return $customerCompare;
            }

            return strcasecmp((string) $a['project']->getName(), (string) $b['project']->getName());
        });

        return $this->render('@CompactWeek/report.html.twig', [
            'report_title' => 'report_compact_week',
            'rounding' => $rounding,
            'user' => $user,
            'current' => $start,
            'begin' => $start,
            'end' => $end,
            'previous' => $previous,
            'next' => $next,
            'decimal' => $decimal,
            'days' => $days,
            'rows' => $rows,
            'dayTotals' => $dayTotals,
            'grandTotal' => $grandTotal,
        ]);
    }

    /**
     * Rounds a duration (in seconds) to an interval (in minutes),
     * either to the nearest interval or always up.
     */
    private function roundDuration(int $seconds, int $roundingMinutes, bool $roundUp): int
    {
        if ($roundingMinutes <= 0 || $seconds === 0) {
            return $seconds;
        }

        $interval = $roundingMinutes * 60;

        if ($roundUp) {
            return (int) ceil($seconds / $interval) * $interval;
        }

        return (int) round($seconds / $interval) * $interval;
    }
}
