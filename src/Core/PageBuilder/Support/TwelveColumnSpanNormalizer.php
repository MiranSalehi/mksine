<?php

declare(strict_types=1);

namespace Miran\Mksine\Core\PageBuilder\Support;

/**
 * Normalizes page-builder column widths as shares of a 12-column CSS grid track.
 */
final class TwelveColumnSpanNormalizer
{
    public const MIN_COLUMNS = 2;

    public const MAX_COLUMNS = 12;

    /**
     * Map asymmetrical preset layout keys to col-span weights (must match column count).
     *
     * @return list<int>
     */
    public static function layoutPresetSpans(string $layout, int $columns): array
    {
        return match ($layout) {
            '1-2' => $columns === 2 ? [4, 8] : [],
            '2-1' => $columns === 2 ? [8, 4] : [],
            '1-3' => $columns === 2 ? [3, 9] : [],
            '3-1' => $columns === 2 ? [9, 3] : [],
            '1-2-1' => $columns === 3 ? [3, 6, 3] : [],
            '2-1-1' => $columns === 3 ? [6, 3, 3] : [],
            '1-1-2' => $columns === 3 ? [3, 3, 6] : [],
            default => [],
        };
    }

    /**
     * @param  array<int, array{span?: mixed}|mixed>  $rows
     * @return list<array{span: int}>
     */
    public static function normalizeRepeaterSpans(array $rows, int $columns): array
    {
        $columns = max(self::MIN_COLUMNS, min(self::MAX_COLUMNS, $columns));
        $weights = [];
        foreach ($rows as $row) {
            $w = is_array($row) ? (int) ($row['span'] ?? 0) : (int) $row;
            $weights[] = max(1, min(12, $w > 0 ? $w : 1));
        }

        while (count($weights) < $columns) {
            $weights[] = 1;
        }
        $weights = array_slice($weights, 0, $columns);

        $sum = array_sum($weights);
        if ($sum === 12) {
            return array_map(fn (int $s): array => ['span' => $s], $weights);
        }

        $spans = [];
        $used = 0;
        foreach ($weights as $i => $w) {
            if ($i === $columns - 1) {
                $spans[] = max(1, 12 - $used);

                break;
            }
            $share = (int) floor(12 * $w / max(1, $sum));
            $share = max(1, $share);
            $remainingSlots = $columns - $i - 1;
            $share = min($share, 12 - $used - $remainingSlots);
            $spans[] = $share;
            $used += $share;
        }

        return array_map(fn (int $s): array => ['span' => max(1, $s)], $spans);
    }

    /**
     * Equal split of 12 grid units.
     *
     * @return list<int>
     */
    public static function equalSplit(int $columns): array
    {
        $columns = max(self::MIN_COLUMNS, min(self::MAX_COLUMNS, $columns));
        $base = intdiv(12, $columns);
        $rem = 12 % $columns;
        $out = [];
        for ($i = 0; $i < $columns; $i++) {
            $out[] = $base + ($i < $rem ? 1 : 0);
        }

        return $out;
    }
}
