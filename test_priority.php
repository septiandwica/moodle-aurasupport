<?php
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/classes/analytics.php');
$advdata = \local_aurasupport\analytics::get_advanced_chart_data();
var_dump($advdata['priority_labels']);
var_dump($advdata['priority_data']);
