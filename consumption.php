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
require_once($CFG->dirroot . '/local/aurasupport/classes/ticket.php');

$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context); // Only for site admins

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aurasupport/consumption.php'));
$PAGE->set_title('AI Consumption');
$PAGE->set_heading('AI Consumption Monitoring');

echo $OUTPUT->header();

echo html_writer::start_tag('div', ['class' => 'local_aurasupport-container']);

local_aurasupport_print_tabs('consumption');

global $DB;
$dbman = $DB->get_manager();
$table = new \xmldb_table('local_aurasupport_ai_logs');

if (!$dbman->table_exists($table)) {
    echo html_writer::start_tag('div', ['class' => 'alert alert-warning']);
    echo '<strong>Warning:</strong> Database has not been upgraded yet. Please visit Site Administration > Notifications to upgrade your Moodle database to use AI Consumption Monitoring.';
    echo html_writer::end_tag('div');
} else {
    // Summary Metrics
    $total_prompt = $DB->get_field_sql('SELECT SUM(tokens_prompt) FROM {local_aurasupport_ai_logs}') ?? 0;
    $total_completion = $DB->get_field_sql('SELECT SUM(tokens_completion) FROM {local_aurasupport_ai_logs}') ?? 0;
    $total_requests = $DB->count_records('local_aurasupport_ai_logs');

    echo '<div class="row mb-4">';
    echo '<div class="col-md-4"><div class="card text-white bg-primary mb-3"><div class="card-body"><h5 class="card-title">Total Requests (RPM/RPD)</h5><h2 class="card-text">'.number_format($total_requests).'</h2></div></div></div>';
    echo '<div class="col-md-4"><div class="card text-white bg-info mb-3"><div class="card-body"><h5 class="card-title">Prompt Tokens (TPM)</h5><h2 class="card-text">'.number_format($total_prompt).'</h2></div></div></div>';
    echo '<div class="col-md-4"><div class="card text-white bg-success mb-3"><div class="card-body"><h5 class="card-title">Completion Tokens</h5><h2 class="card-text">'.number_format($total_completion).'</h2></div></div></div>';
    echo '</div>';

    // Detailed Logs
    echo '<h4>Recent AI API Usage Logs</h4>';
    
    $page = optional_param('page', 0, PARAM_INT);
    $perpage = 20;

    $sql = "SELECT l.id, l.userid, l.action, l.tokens_prompt, l.tokens_completion, l.timecreated, u.firstname, u.lastname
            FROM {local_aurasupport_ai_logs} l
            LEFT JOIN {user} u ON l.userid = u.id
            ORDER BY l.timecreated DESC";
            
    $logs = $DB->get_records_sql($sql, [], $page * $perpage, $perpage);
    $totalcount = $DB->count_records('local_aurasupport_ai_logs');

    if (!empty($logs)) {
        echo '<table class="table table-striped table-bordered">';
        echo '<thead><tr><th>Time</th><th>User</th><th>Action / API Endpoint</th><th>Prompt Tokens</th><th>Completion Tokens</th><th>Total Tokens</th></tr></thead>';
        echo '<tbody>';
        foreach ($logs as $log) {
            $total_tokens = $log->tokens_prompt + $log->tokens_completion;
            echo '<tr>';
            echo '<td>' . userdate($log->timecreated) . '</td>';
            echo '<td>' . ($log->firstname ? s($log->firstname . ' ' . $log->lastname) : 'System') . '</td>';
            echo '<td><span class="badge badge-secondary">' . s($log->action) . '</span></td>';
            echo '<td>' . number_format($log->tokens_prompt) . '</td>';
            echo '<td>' . number_format($log->tokens_completion) . '</td>';
            echo '<td><strong>' . number_format($total_tokens) . '</strong></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        
        echo $OUTPUT->paging_bar($totalcount, $page, $perpage, new moodle_url('/local/aurasupport/consumption.php'));
    } else {
        echo '<div class="alert alert-info">No AI API usage logs found yet. Once the AI is used to respond to tickets, logs will appear here.</div>';
    }
}

echo html_writer::end_tag('div'); // Close container

echo $OUTPUT->footer();
