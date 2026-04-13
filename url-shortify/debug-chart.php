<?php

require_once __DIR__ . '/../../../wp-load.php';

$spline = US()->db->clicks->get_spline_chart_data();
$heatmap = US()->db->clicks->get_heatmap_intensity_data();

echo 'Spline rows: ' . count( $spline ) . PHP_EOL;
echo 'Heatmap rows: ' . count( $heatmap ) . PHP_EOL;
print_r( array_slice( $spline, 0, 3 ) );
print_r( array_slice( $heatmap, 0, 3 ) );
