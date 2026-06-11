<?php

declare(strict_types=1);

namespace CreatorzHive\Support;

use CreatorzHive\Core\Database\Connection;

final class AnalyticsReportHelper
{
    /** @var Connection */
    private $db;

    public function __construct(Connection $db)
    {
        $this->db = $db;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function resolvePeriodRange(string $period, string $startIn, string $endIn): array
    {
        $end = date('Y-m-d');

        if ($period === 'custom' && $startIn !== '' && $endIn !== '') {
            return [$startIn, $endIn];
        }

        switch ($period) {
            case '7d':
                $start = date('Y-m-d', strtotime('-6 days'));
                break;
            case '90d':
                $start = date('Y-m-d', strtotime('-89 days'));
                break;
            default:
                $start = date('Y-m-d', strtotime('-29 days'));
                break;
        }

        return [$start, $end];
    }

    public function percentChange(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0.0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }

    /**
     * @return list<int>
     */
    public function postsPublishedSparkline(int $userId, string $rangeEnd, int $days): array
    {
        $days = max(1, min(90, $days));
        $start = date('Y-m-d', strtotime($rangeEnd . ' -' . ($days - 1) . ' days'));

        $rows = $this->db->fetchAll(
            'SELECT DATE(published_at) AS d, COUNT(*) AS c
             FROM posts
             WHERE user_id = :uid AND is_deleted = 0 AND status = \'published\'
             AND DATE(published_at) >= :start AND DATE(published_at) <= :end
             GROUP BY DATE(published_at)',
            ['uid' => $userId, 'start' => $start, 'end' => $rangeEnd]
        );

        $map = [];
        foreach ($rows as $row) {
            $map[(string) ($row['d'] ?? '')] = (int) ($row['c'] ?? 0);
        }

        $out = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime($rangeEnd . ' -' . $i . ' days'));
            $out[] = $map[$d] ?? 0;
        }

        return $out;
    }

    /**
     * @param list<array<string, mixed>> $rows
     * @return list<array{week: string, count: int}>
     */
    public function formatPostingFrequency(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'week' => (string) ($row['bucket'] ?? ''),
                'count' => (int) ($row['cnt'] ?? 0),
            ];
        }

        return $out;
    }
}
