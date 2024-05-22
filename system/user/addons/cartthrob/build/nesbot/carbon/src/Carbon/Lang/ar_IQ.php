<?php

namespace CartThrob\Dependency;

/**
 * This file is part of the Carbon package.
 *
 * (c) Brian Nesbitt <brian@nesbot.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
/*
 * Authors:
 * - IBM Globalization Center of Competency, Yamato Software Laboratory    bug-glibc-locales@gnu.org
 */
return \array_replace_recursive(require __DIR__ . '/ar.php', ['formats' => ['L' => 'DD MMM, YYYY'], 'months' => ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'], 'months_short' => ['كانون الثاني', 'شباط', 'آذار', 'نيسان', 'أيار', 'حزيران', 'تموز', 'آب', 'أيلول', 'تشرين الأول', 'تشرين الثاني', 'كانون الأول'], 'weekdays' => ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'], 'weekdays_short' => ['ح', 'ن', 'ث', 'ر', 'خ', 'ج', 'س'], 'weekdays_min' => ['ح', 'ن', 'ث', 'ر', 'خ', 'ج', 'س'], 'first_day_of_week' => 6, 'day_of_first_week_of_year' => 1]);
