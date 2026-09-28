<?php

namespace App\Support;

class Format
{
    public static function bytes(?int $bytes, int $precision = 1): string
    {
        $bytes = max(0, (int) $bytes);
        // Localised unit names (o/Ko/Mo in French, B/KB/MB in English…).
        $units = trans('lms.byte_units');
        if (! is_array($units) || count($units) < 5) {
            $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        }
        $i = 0;
        $value = (float) $bytes;
        while ($value >= 1024 && $i < count($units) - 1) {
            $value /= 1024;
            $i++;
        }
        $separator = trans('lms.decimal_separator');
        $number = number_format($value, $i === 0 ? 0 : $precision, $separator, '');
        if (str_contains($number, $separator)) {
            $number = rtrim(rtrim($number, '0'), $separator);
        }
        return $number . ' ' . $units[$i];
    }

    /** Seconds -> "1:02:03" or "2:03". */
    public static function clock(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        $s = $seconds % 60;
        return $h > 0 ? sprintf('%d:%02d:%02d', $h, $m, $s) : sprintf('%d:%02d', $m, $s);
    }

    /** Signed score difference in points: "+12.5 pts", "-3 pts" (localised), or a dash. */
    public static function points(?float $value): string
    {
        if ($value === null) {
            return '—';
        }
        $number = rtrim(rtrim(number_format(abs($value), 1, trans('lms.decimal_separator'), ''), '0'), trans('lms.decimal_separator'));
        return trans('learn.points', ['value' => ($value > 0 ? '+' : ($value < 0 ? '-' : '')) . $number]);
    }
}
