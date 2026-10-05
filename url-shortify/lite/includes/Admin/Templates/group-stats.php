<?php
/**
 * Group statistics.
 *
 * Group and tag statistics are the same screen with a different noun, so both
 * render through entity-stats.php.
 *
 * @var array $data Prepared by GroupStatsController.
 *
 * @package KaizenCoders\URL_Shortify
 */

$kc_us_entity = 'group';

require __DIR__ . '/entity-stats.php';
