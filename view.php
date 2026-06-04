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

if (!is_siteadmin() && $ticket->userid != $USER->id) {
    print_error('nopermissions', 'error', '', 'view this ticket');
}

$draft_text = '';
$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'generate_ai' && is_siteadmin() && \local_aurasupport\ai_manager::is_enabled()) {
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
if (is_siteadmin()) {
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

// Start Premium Container
echo html_writer::start_tag('div', ['class' => 'local_aurasupport-container']);

// Ticket Header
echo html_writer::start_tag('div', ['class' => 'local_aurasupport-ticket-header']);
echo html_writer::tag('h2', format_string($ticket->subject));
$status_map = [
    0 => '<span class="badge badge-aura badge-warning">Open</span>',
    1 => '<span class="badge badge-aura badge-info">Pending</span>',
    2 => '<span class="badge badge-aura badge-success">Resolved</span>',
    3 => '<span class="badge badge-aura badge-secondary">Closed</span>'
];
echo html_writer::tag('div', $status_map[$ticket->status]);
echo html_writer::end_tag('div'); // End Ticket Header

echo html_writer::start_tag('div', ['class' => 'row']);

// Left Column (Chat Bubbles)
$left_col_class = $statusform ? 'col-lg-8' : 'col-lg-12';
echo html_writer::start_tag('div', ['class' => $left_col_class]);

echo html_writer::start_tag('div', ['class' => 'local_aurasupport-chat-container']);

// Initial Ticket Description Bubble
$creator = $DB->get_record('user', ['id' => $ticket->userid]);
echo html_writer::start_tag('div', ['class' => 'local_aurasupport-bubble-wrapper user']);
echo html_writer::tag('div', '<strong>'.fullname($creator).'</strong> ('.userdate($ticket->timecreated).')', ['class' => 'local_aurasupport-bubble-meta']);
echo html_writer::tag('div', format_text($ticket->description), ['class' => 'local_aurasupport-bubble']);
echo html_writer::end_tag('div');

$lastmsgid = 0;
$messages = \local_aurasupport\ticket::get_messages($id);
foreach ($messages as $msg) {
    if ($msg->id > $lastmsgid) {
        $lastmsgid = $msg->id;
    }
    $is_admin = ($msg->userid != $ticket->userid);
    $wrapper_class = $is_admin ? 'admin' : 'user';
    $sender = $DB->get_record('user', ['id' => $msg->userid]);
    
    echo html_writer::start_tag('div', ['class' => 'local_aurasupport-bubble-wrapper ' . $wrapper_class]);
    echo html_writer::tag('div', '<strong>'.fullname($sender).'</strong> ('.userdate($msg->timecreated).')', ['class' => 'local_aurasupport-bubble-meta']);
    echo html_writer::tag('div', format_text($msg->message), ['class' => 'local_aurasupport-bubble']);
    echo html_writer::end_tag('div');
}
echo html_writer::end_tag('div'); // End Chat Container

$ajaxurl = new moodle_url('/local/aurasupport/ajax.php');
$js = "
require(['jquery'], function($) {
    var lastmsgid = {$lastmsgid};
    var ticketid = {$id};
    var isPolling = false;
    
    // Scroll to bottom initially
    var chatContainer = $('.local_aurasupport-chat-container');
    chatContainer.scrollTop(chatContainer[0].scrollHeight);

    setInterval(function() {
        if (isPolling) return;
        isPolling = true;
        $.ajax({
            url: '{$ajaxurl}',
            data: { ticketid: ticketid, lastmsgid: lastmsgid },
            dataType: 'json',
            success: function(response) {
                if (response.messages && response.messages.length > 0) {
                    var needsScroll = false;
                    // Check if user is scrolled to the bottom before appending
                    if (chatContainer.scrollTop() + chatContainer.innerHeight() >= chatContainer[0].scrollHeight - 50) {
                        needsScroll = true;
                    }
                    
                    $.each(response.messages, function(i, msg) {
                        chatContainer.append(msg.html);
                        lastmsgid = msg.id;
                    });
                    
                    if (needsScroll) {
                        chatContainer.scrollTop(chatContainer[0].scrollHeight);
                    }
                }
            },
            complete: function() {
                isPolling = false;
            }
        });
    }, 5000);
});
";
$PAGE->requires->js_amd_inline($js);

// Reply Section
echo html_writer::start_tag('div', ['class' => 'local_aurasupport-card mb-4']);
echo html_writer::tag('div', get_string('reply', 'local_aurasupport'), ['class' => 'card-header']);
echo html_writer::start_tag('div', ['class' => 'card-body']);

if (is_siteadmin() && \local_aurasupport\ai_manager::is_enabled()) {
    $ai_url = new moodle_url('/local/aurasupport/view.php', ['id' => $id, 'action' => 'generate_ai']);
    echo html_writer::link($ai_url, '✨ Draft Response with Gemini AI', ['class' => 'btn btn-outline-primary mb-3']);
}

$mform->display();
echo html_writer::end_tag('div'); // End Card Body
echo html_writer::end_tag('div'); // End Card

echo html_writer::link(new moodle_url('/local/aurasupport/tickets.php'), get_string('backtotickets', 'local_aurasupport'), ['class' => 'btn btn-secondary mt-3']);

echo html_writer::end_tag('div'); // End Left Column

// Right Column (Action Panel for Admins)
if ($statusform) {
    echo html_writer::start_tag('div', ['class' => 'col-lg-4']);
    echo html_writer::start_tag('div', ['class' => 'local_aurasupport-action-panel']);
    
    echo html_writer::tag('h5', get_string('updatestatus', 'local_aurasupport'), ['class' => 'font-weight-bold mb-3']);
    $statusform->display();
    
    echo html_writer::tag('hr', '', ['class' => 'my-4']);
    
    echo html_writer::tag('h5', 'Re-assign Agent', ['class' => 'font-weight-bold mb-3']);
    $reassignform->display();
    
    echo html_writer::end_tag('div'); // End Action Panel
    echo html_writer::end_tag('div'); // End Right Column
}

echo html_writer::end_tag('div'); // End Row
echo html_writer::end_tag('div'); // End Premium Container

echo $OUTPUT->footer();
