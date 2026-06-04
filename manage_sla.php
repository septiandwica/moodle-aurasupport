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
require_once($CFG->dirroot . '/local/aurasupport/classes/form/sla_form.php');

$context = context_system::instance();
require_login();
require_capability('local/aurasupport:manage', $context);

$delete = optional_param('delete', 0, PARAM_INT);
if ($delete) {
    require_sesskey();
    $DB->delete_records('local_aurasupport_sla', ['id' => $delete]);
    redirect(new moodle_url('/local/aurasupport/manage_sla.php'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aurasupport/manage_sla.php'));
$PAGE->set_title('Manage SLA Rules');
$PAGE->set_heading('Manage SLA Rules');

$mform = new \local_aurasupport\form\sla_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/aurasupport/index.php'));
} else if ($data = $mform->get_data()) {
    if ($existing = $DB->get_record('local_aurasupport_sla', ['priority' => $data->priority])) {
        $existing->responsetime = $data->responsetime;
        $existing->resolutiontime = $data->resolutiontime;
        $DB->update_record('local_aurasupport_sla', $existing);
        \core\notification::add('SLA Rule updated.', \core\notification::SUCCESS);
    } else {
        $sla = new \stdClass();
        $sla->priority = $data->priority;
        $sla->responsetime = $data->responsetime;
        $sla->resolutiontime = $data->resolutiontime;
        $DB->insert_record('local_aurasupport_sla', $sla);
        \core\notification::add('SLA Rule created.', \core\notification::SUCCESS);
    }
    redirect(new moodle_url('/local/aurasupport/manage_sla.php'));
}

echo $OUTPUT->header();
local_aurasupport_print_tabs('sla');

$slas = $DB->get_records('local_aurasupport_sla', null, 'priority ASC');
if (!empty($slas)) {
    $table = new html_table();
    $table->head = ['Priority', 'Max Response (mins)', 'Max Resolution (mins)', 'Action'];
    $table->data = [];
    
    $priorities = [
        0 => get_string('priority_low', 'local_aurasupport'),
        1 => get_string('priority_medium', 'local_aurasupport'),
        2 => get_string('priority_high', 'local_aurasupport'),
        3 => get_string('priority_urgent', 'local_aurasupport')
    ];
    
    foreach ($slas as $sla) {
        $delurl = new moodle_url('/local/aurasupport/manage_sla.php', ['delete' => $sla->id, 'sesskey' => sesskey()]);
        $dellink = html_writer::link($delurl, 'Delete', ['class' => 'text-danger']);
        $table->data[] = [$priorities[$sla->priority], $sla->responsetime, $sla->resolutiontime, $dellink];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('No SLA rules configured. Defaulting to 24 hours.', 'info');
}

echo html_writer::tag('hr', '');
echo html_writer::tag('h3', 'Configure SLA Rule');
$mform->display();

echo html_writer::link(new moodle_url('/local/aurasupport/index.php'), 'Back to Dashboard', ['class' => 'btn btn-secondary mt-3']);
echo $OUTPUT->footer();
