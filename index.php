<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AuraSupport / Kopere File
 *
 * @package   local_aurasupport
 * @copyright 2026 Tateta {@link https://samastanuswantara.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/aurasupport/classes/analytics.php');

$context = context_system::instance();
require_login();
require_capability('local/aurasupport:manage', $context); // Only managers/agents can see BI

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aurasupport/index.php'));
$PAGE->set_title(get_string('dashboard', 'local_aurasupport'));
$PAGE->set_heading(get_string('dashboard', 'local_aurasupport'));

echo $OUTPUT->header();

// Bypass Moodle's RequireJS for ApexCharts (Standalone UMD)
echo '<script>
    var originalDefine = window.define;
    if (originalDefine && originalDefine.amd) {
        window.moodleAmd = originalDefine.amd;
        originalDefine.amd = false;
    }
</script>';
echo '<script src="https://cdn.jsdelivr.net/npm/apexcharts@5.13.0/dist/apexcharts.min.js"></script>';
echo '<script>
    if (window.originalDefine && window.moodleAmd) {
        window.originalDefine.amd = window.moodleAmd;
    }
</script>';

local_aurasupport_print_tabs('dashboard');

$stats = \local_aurasupport\analytics::get_summary_stats();
$chartdata = \local_aurasupport\analytics::get_chart_data();
$advdata = \local_aurasupport\analytics::get_advanced_chart_data();

// Render template
$templatedata = [
    'total' => $stats->total,
    'open' => $stats->open,
    'resolved' => $stats->resolved,
    'closed' => $stats->closed,
    'chartdata' => json_encode($chartdata),
    'advdata' => json_encode($advdata),
    'leader_html' => $advdata['leader_html'],
    'top_users_html' => $advdata['top_users_html'],
    'avg_response_time' => $advdata['avg_response_time'],
    'recent_unresolved_html' => $advdata['recent_unresolved_html']
];

echo $OUTPUT->render_from_template('local_aurasupport/dashboard', $templatedata);

echo $OUTPUT->footer();
