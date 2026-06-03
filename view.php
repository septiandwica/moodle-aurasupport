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
require_once($CFG->dirroot . '/local/aurasupport/classes/form/reply_form.php');
require_once($CFG->dirroot . '/local/aurasupport/classes/form/status_form.php');
require_once($CFG->dirroot . '/local/aurasupport/classes/form/reassign_form.php');
require_once($CFG->dirroot . '/local/aurasupport/classes/ai_manager.php');

$id = required_param('id', PARAM_INT);
$ticket = \local_aurasupport\ticket::get_by_id($id);

$context = context_system::instance();
require_login();

if (!has_capability('local/aurasupport:manage', $context) && $ticket->userid != $USER->id) {
    print_error('nopermissions', 'error', '', 'view this ticket');
}

$draft_text = '';
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'generate_ai' && has_capability('local/aurasupport:manage', $context) && \local_aurasupport\ai_manager::is_enabled()) {
    $messages = \local_aurasupport\ticket::get_messages($id);
    $history = '';
    foreach ($messages as $m) {
        $history .= "User " . $m->userid . ": " . strip_tags($m->message) . "\n";
    }
    $draft_text = \local_aurasupport\ai_manager::generate_reply($ticket->subject, strip_tags($ticket->description), $history);
    \core\notification::add('AI Draft generated. Please review before submitting.', \core\notification::INFO);
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aurasupport/view.php', ['id' => $id]));
$PAGE->set_title($ticket->subject);
$PAGE->set_heading(get_string('ticketdetails', 'local_aurasupport'));

$mform = new \local_aurasupport\form\reply_form(null, ['ticketid' => $id]);
if ($draft_text) {
    $mform->set_data(['message' => ['text' => $draft_text, 'format' => FORMAT_HTML]]);
}

$statusform = null;
$reassignform = null;
if (has_capability('local/aurasupport:manage', $context)) {
    // Status Form
    $statusform = new \local_aurasupport\form\status_form(null, ['ticketid' => $id]);
    $statusform->set_data(['status' => $ticket->status]);
    
    if ($statusform->is_submitted() && $statusform->is_validated() && $statusdata = $statusform->get_data()) {
        \local_aurasupport\ticket::update_status($id, $statusdata->status);
        redirect(new moodle_url('/local/aurasupport/view.php', ['id' => $id]), get_string('statusupdated', 'local_aurasupport'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
    
    // Re-assign Form
    $reassignform = new \local_aurasupport\form\reassign_form(null, ['ticketid' => $id]);
    $reassignform->set_data(['agentid' => $ticket->agentid]);
    
    if ($reassignform->is_submitted() && $reassignform->is_validated() && $reassigndata = $reassignform->get_data()) {
        $DB->set_field('local_aurasupport_tickets', 'agentid', $reassigndata->agentid, ['id' => $id]);
        redirect(new moodle_url('/local/aurasupport/view.php', ['id' => $id]), 'Agent re-assigned successfully.', null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/aurasupport/view.php', ['id' => $id]));
} else if ($data = $mform->get_data()) {
    $msgid = \local_aurasupport\ticket::add_message($id, $USER->id, $data->message);
    
    // Save attachments
    if (!empty($data->attachments)) {
        file_save_draft_area_files($data->attachments, $context->id, 'local_aurasupport', 'message_attachment', $msgid, ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 5]);
    }
    
    redirect(new moodle_url('/local/aurasupport/view.php', ['id' => $id]));
}

echo $OUTPUT->header();

echo html_writer::tag('h3', format_string($ticket->subject));
echo html_writer::tag('div', format_text($ticket->description), ['class' => 'local_aurasupport-msg']);

$messages = \local_aurasupport\ticket::get_messages($id);
foreach ($messages as $msg) {
    $msgclass = 'local_aurasupport-msg';
    if ($msg->userid != $ticket->userid) {
        $msgclass .= ' admin';
    }
    echo html_writer::tag('div', format_text($msg->message), ['class' => $msgclass]);
}

echo html_writer::tag('hr', '');
echo html_writer::tag('h4', get_string('reply', 'local_aurasupport'));

if (has_capability('local/aurasupport:manage', $context) && \local_aurasupport\ai_manager::is_enabled()) {
    $ai_url = new moodle_url('/local/aurasupport/view.php', ['id' => $id, 'action' => 'generate_ai']);
    echo html_writer::link($ai_url, '✨ Draft Response with Gemini AI', ['class' => 'btn btn-outline-primary mb-3']);
}

$mform->display();

if ($statusform) {
    echo html_writer::tag('hr', '');
    echo html_writer::start_tag('div', ['class' => 'row']);
    echo html_writer::start_tag('div', ['class' => 'col-md-6']);
    echo html_writer::tag('h4', get_string('updatestatus', 'local_aurasupport'));
    $statusform->display();
    echo html_writer::end_tag('div');
    
    echo html_writer::start_tag('div', ['class' => 'col-md-6']);
    echo html_writer::tag('h4', 'Re-assign Agent');
    $reassignform->display();
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');
}

echo html_writer::link(new moodle_url('/local/aurasupport/tickets.php'), get_string('backtotickets', 'local_aurasupport'), ['class' => 'btn btn-secondary mt-3']);

echo $OUTPUT->footer();
