<?php

/**
 * The goal of this file is to allow developers a location
 * where they can overwrite core procedural functions and
 * replace them with their own. This file is loaded during
 * the bootstrap process and is called during the framework's
 * execution.
 *
 * This can be looked at as a `master helper` file that is
 * loaded early on, and may also contain additional functions
 * that you'd like to use throughout your entire application
 *
 * @see: https://codeigniter.com/user_guide/extending/common.html
 */

if (! function_exists('thai_weekday_label')) {
    function thai_weekday_label(string $date): string
    {
        $labels = ['อา.', 'จ.', 'อ.', 'พ.', 'พฤ.', 'ศ.', 'ส.'];
        $timestamp = strtotime($date);

        return $timestamp === false ? '' : ($labels[(int) date('w', $timestamp)] ?? '');
    }
}

if (! function_exists('thai_date_short')) {
    function thai_date_short(string $date): string
    {
        $timestamp = strtotime($date);
        if ($timestamp === false) {
            return $date;
        }

        $months = [
            1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
            5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
            9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.',
        ];

        $day = (int) date('j', $timestamp);
        $month = $months[(int) date('n', $timestamp)] ?? date('m', $timestamp);
        $thaiYearShort = ((int) date('Y', $timestamp) + 543) % 100;

        return sprintf('%d %s %02d', $day, $month, $thaiYearShort);
    }
}

if (! function_exists('thai_month_label')) {
    function thai_month_label(int $month): string
    {
        $labels = [
            1 => 'มกราคม', 2 => 'กุมภาพันธ์', 3 => 'มีนาคม', 4 => 'เมษายน',
            5 => 'พฤษภาคม', 6 => 'มิถุนายน', 7 => 'กรกฎาคม', 8 => 'สิงหาคม',
            9 => 'กันยายน', 10 => 'ตุลาคม', 11 => 'พฤศจิกายน', 12 => 'ธันวาคม',
        ];

        return $labels[$month] ?? (string) $month;
    }
}
