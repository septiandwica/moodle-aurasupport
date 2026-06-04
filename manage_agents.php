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
require_once($CFG->dirroot . '/local/aurasupport/classes/form/agent_form.php');

$context = context_system::instance();
require_login();
require_capability('moodle/site:config', $context);

$delete = optional_param('delete', 0, PARAM_INT);
if ($delete) {
    require_sesskey();
    $DB->delete_records('local_aurasupport_agents', ['id' => $delete]);
    redirect(new moodle_url('/local/aurasupport/manage_agents.php'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aurasupport/manage_agents.php'));
$PAGE->set_title('Manage Agents');
$PAGE->set_heading('Manage Agents');

$mform = new \local_aurasupport\form\agent_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/aurasupport/index.php'));
} else if ($data = $mform->get_data()) {
    $user = $DB->get_record('user', ['email' => $data->useremail, 'deleted' => 0]);
    if (!$user) {
        \core\notification::add('User not found with that email.', \core\notification::ERROR);
    } else if (empty($data->departmentid)) {
        \core\notification::add('Invalid department.', \core\notification::ERROR);
    } else {
        // Check if already exists
        if (!$DB->record_exists('local_aurasupport_agents', ['userid' => $user->id, 'departmentid' => $data->departmentid])) {
            $agent = new \stdClass();
            $agent->userid = $user->id;
            $agent->departmentid = $data->departmentid;
            $agent->timecreated = time();
            $DB->insert_record('local_aurasupport_agents', $agent);
            \core\notification::add('Agent assigned successfully.', \core\notification::SUCCESS);
        } else {
            \core\notification::add('Agent is already in this department.', \core\notification::WARNING);
        }
        redirect(new moodle_url('/local/aurasupport/manage_agents.php'));
    }
}

echo $OUTPUT->header();

echo html_writer::start_tag('div', ['class' => 'local_aurasupport-container']);
local_aurasupport_print_tabs('agents');

$sql = "SELECT a.id, u.firstname, u.lastname, u.email, d.name AS deptname 
        FROM {local_aurasupport_agents} a
        JOIN {user} u ON a.userid = u.id
        JOIN {local_aurasupport_depts} d ON a.departmentid = d.id
        ORDER BY d.name ASC, u.firstname ASC";
$agents = $DB->get_records_sql($sql);

if (!empty($agents)) {
    $table = new html_table();
    $table->head = ['Agent Name', 'Email', 'Department', 'Action'];
    $table->data = [];
    foreach ($agents as $a) {
        $delurl = new moodle_url('/local/aurasupport/manage_agents.php', ['delete' => $a->id, 'sesskey' => sesskey()]);
        $dellink = html_writer::link($delurl, 'Remove', ['class' => 'text-danger']);
        $table->data[] = [fullname($a), $a->email, $a->deptname, $dellink];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('No agents assigned.', 'info');
}

echo html_writer::tag('hr', '');
echo html_writer::tag('h3', 'Assign New Agent');
$mform->display();

echo html_writer::link(new moodle_url('/local/aurasupport/index.php'), 'Back to Dashboard', ['class' => 'btn btn-secondary mt-3']);
echo html_writer::end_tag('div');

echo $OUTPUT->footer();
