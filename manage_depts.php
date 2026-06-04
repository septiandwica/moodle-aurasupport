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
require_once($CFG->dirroot . '/local/aurasupport/classes/form/dept_form.php');

$context = context_system::instance();
require_login();
require_capability('local/aurasupport:manage', $context);

// Handle delete
$delete = optional_param('delete', 0, PARAM_INT);
if ($delete) {
    require_sesskey();
    $DB->delete_records('local_aurasupport_depts', ['id' => $delete]);
    redirect(new moodle_url('/local/aurasupport/manage_depts.php'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aurasupport/manage_depts.php'));
$PAGE->set_title(get_string('managedepts', 'local_aurasupport'));
$PAGE->set_heading(get_string('managedepts', 'local_aurasupport'));

$mform = new \local_aurasupport\form\dept_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/aurasupport/index.php'));
} else if ($data = $mform->get_data()) {
    $dept = new \stdClass();
    $dept->name = $data->name;
    $dept->description = $data->description;
    $dept->timecreated = time();
    $DB->insert_record('local_aurasupport_depts', $dept);
    redirect(new moodle_url('/local/aurasupport/manage_depts.php'));
}

echo $OUTPUT->header();
local_aurasupport_print_tabs('departments');

// List departments
$depts = $DB->get_records('local_aurasupport_depts', null, 'name ASC');
if (!empty($depts)) {
    $table = new html_table();
    $table->head = ['ID', get_string('departmentname', 'local_aurasupport'), get_string('description', 'local_aurasupport'), 'Action'];
    $table->data = [];
    foreach ($depts as $dept) {
        $delurl = new moodle_url('/local/aurasupport/manage_depts.php', ['delete' => $dept->id, 'sesskey' => sesskey()]);
        $dellink = html_writer::link($delurl, 'Delete', ['class' => 'text-danger']);
        $table->data[] = [$dept->id, $dept->name, $dept->description, $dellink];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('No departments found.', 'info');
}

echo html_writer::tag('hr', '');
echo html_writer::tag('h3', get_string('adddepartment', 'local_aurasupport'));
$mform->display();

echo html_writer::link(new moodle_url('/local/aurasupport/index.php'), 'Back to Dashboard', ['class' => 'btn btn-secondary mt-3']);
echo $OUTPUT->footer();
