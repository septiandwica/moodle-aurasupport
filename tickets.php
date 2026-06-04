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
require_once($CFG->dirroot . '/local/aurasupport/classes/form/ticket_form.php');

$context = context_system::instance();
require_login();
require_capability('local/aurasupport:createticket', $context);

$filterstatus = optional_param('filterstatus', -1, PARAM_INT);
$filterdept = optional_param('filterdept', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aurasupport/tickets.php'));
$PAGE->set_title(get_string('tickets', 'local_aurasupport'));
$PAGE->set_heading(get_string('tickets', 'local_aurasupport'));

// Load DataTables Libraries
$PAGE->requires->css(new moodle_url('https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap4.min.css'));
$PAGE->requires->css(new moodle_url('https://cdn.datatables.net/buttons/2.3.6/css/buttons.bootstrap4.min.css'));

// Removed PAGE->requires->js for CDNs. They will be output manually below to prevent AMD crashes.

$mform = new \local_aurasupport\form\ticket_form();
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/aurasupport/tickets.php'));
} else if ($data = $mform->get_data()) {
    $ticketid = \local_aurasupport\ticket::create($data);
    if (!empty($data->attachments)) {
        file_save_draft_area_files($data->attachments, $context->id, 'local_aurasupport', 'ticket_attachment', $ticketid, ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 5]);
    }
    \core\notification::add('Ticket successfully created.', \core\notification::SUCCESS);
    redirect(new moodle_url('/local/aurasupport/view.php', ['id' => $ticketid]));
}

echo $OUTPUT->header();
local_aurasupport_print_tabs('tickets');

// Filters UI
echo html_writer::start_tag('div', ['class' => 'well box p-3 mb-4 bg-light border rounded']);
echo html_writer::start_tag('form', ['action' => 'tickets.php', 'method' => 'get', 'class' => 'form-inline d-flex align-items-center']);
echo html_writer::tag('label', 'Filter Status: ', ['class' => 'mr-2 font-weight-bold']);
echo html_writer::select([
    -1 => 'All Statuses', 0 => 'Open', 1 => 'Pending', 2 => 'Resolved', 3 => 'Closed'
], 'filterstatus', $filterstatus, false, ['class' => 'custom-select mr-4']);

$depts = $DB->get_records_menu('local_aurasupport_depts', null, 'name ASC', 'id, name');
if (!empty($depts)) {
    echo html_writer::tag('label', 'Department: ', ['class' => 'mr-2 font-weight-bold ml-3']);
    echo html_writer::select([0 => 'All Departments'] + $depts, 'filterdept', $filterdept, false, ['class' => 'custom-select mr-4']);
}

echo html_writer::empty_tag('input', ['type' => 'submit', 'value' => 'Apply Filter', 'class' => 'btn btn-primary ml-3']);
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

// Query Data
$sqlwhere = '1=1';
$params = [];
if (!has_capability('local/aurasupport:manage', $context)) {
    $sqlwhere .= ' AND t.userid = :userid';
    $params['userid'] = $USER->id;
}
if ($filterstatus >= 0) {
    $sqlwhere .= ' AND t.status = :status';
    $params['status'] = $filterstatus;
}
if ($filterdept > 0) {
    $sqlwhere .= ' AND t.departmentid = :dept';
    $params['dept'] = $filterdept;
}

$sql = "SELECT t.id, t.subject, t.priority, t.status, t.timecreated, 
               u.firstname, u.lastname, 
               c.fullname as coursename, 
               COALESCE(d.name, 'General') as department
        FROM {local_aurasupport_tickets} t
        JOIN {user} u ON t.userid = u.id
        LEFT JOIN {course} c ON t.courseid = c.id
        LEFT JOIN {local_aurasupport_depts} d ON t.departmentid = d.id
        WHERE $sqlwhere 
        ORDER BY t.timecreated DESC";
$tickets = $DB->get_records_sql($sql, $params);

$status_map = [
    0 => '<span class="badge badge-danger">Open</span>',
    1 => '<span class="badge badge-warning">Pending</span>',
    2 => '<span class="badge badge-success">Resolved</span>',
    3 => '<span class="badge badge-secondary">Closed</span>'
];
$priority_map = [
    0 => 'Low', 1 => 'Medium', 2 => 'High', 3 => 'Urgent'
];

echo html_writer::start_tag('div', ['class' => 'table-responsive mt-4']);
echo '<table id="ticketstable" class="table table-striped table-bordered" style="width:100%">';
echo '<thead><tr>
        <th>ID</th>
        <th>Subject</th>
        <th>User</th>
        <th>Course</th>
        <th>Department</th>
        <th>Status</th>
        <th>Priority</th>
        <th>Created</th>
        <th>Action</th>
      </tr></thead>';
echo '<tbody>';
foreach ($tickets as $t) {
    $url = new moodle_url('/local/aurasupport/view.php', ['id' => $t->id]);
    $btn = html_writer::link($url, 'View', ['class' => 'btn btn-sm btn-info']);
    
    echo '<tr>';
    echo '<td>' . $t->id . '</td>';
    echo '<td>' . s($t->subject) . '</td>';
    echo '<td>' . s($t->firstname . ' ' . $t->lastname) . '</td>';
    echo '<td>' . ($t->coursename ? s($t->coursename) : '-') . '</td>';
    echo '<td>' . s($t->department) . '</td>';
    echo '<td>' . $status_map[$t->status] . '</td>';
    echo '<td>' . $priority_map[$t->priority] . '</td>';
    echo '<td>' . userdate($t->timecreated) . '</td>';
    echo '<td>' . $btn . '</td>';
    echo '</tr>';
}
echo '</tbody></table>';
echo html_writer::end_tag('div');

$js = "
require.config({
    paths: {
        'datatables.net': 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min',
        'datatables.net-bs4': 'https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap4.min',
        'datatables.net-buttons': 'https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min',
        'datatables.net-buttons-html5': 'https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min',
        'datatables.net-buttons-print': 'https://cdn.datatables.net/buttons/2.3.6/js/buttons.print.min'
    },
    shim: {
        'datatables.net-bs4': ['datatables.net'],
        'datatables.net-buttons': ['datatables.net'],
        'datatables.net-buttons-html5': ['datatables.net-buttons'],
        'datatables.net-buttons-print': ['datatables.net-buttons']
    }
});

require(['jquery', 'datatables.net', 'datatables.net-bs4', 'datatables.net-buttons', 'datatables.net-buttons-html5', 'datatables.net-buttons-print'], function($) {
    $(document).ready(function() {
        $('#ticketstable').DataTable({
            dom: 'Bfrtip',
            buttons: ['copy', 'csv', 'print'],
            order: [[7, 'desc']],
            language: { search: 'Live Search:' }
        });
    });
});
";
$PAGE->requires->js_amd_inline($js);

echo html_writer::tag('hr', ['class' => 'mt-5 mb-4']);
echo html_writer::tag('h3', get_string('createticket', 'local_aurasupport'), ['class' => 'mb-4']);
$mform->display();

echo $OUTPUT->footer();
