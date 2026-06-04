<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * AuraSupport / Kopere File
 *
 * @package   local_aurasupport
 * @copyright 2026 Tateta {@link https://samastanuswantara.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define('AJAX_SCRIPT', true);
require_once('../../config.php');
require_once($CFG->dirroot . '/local/aurasupport/classes/ticket.php');

require_login();

$action = optional_param('action', '', PARAM_TEXT);

if ($action === 'widget_get_tickets') {
    require_sesskey();
    global $DB;
    // Get active/recent tickets for current user
    $sql = "SELECT * FROM {local_aurasupport_tickets} WHERE userid = :userid ORDER BY timecreated DESC LIMIT 10";
    $tickets = $DB->get_records_sql($sql, ['userid' => $USER->id]);
    $res = [];
    foreach ($tickets as $t) {
        $res[] = [
            'id' => $t->id,
            'subject' => format_string($t->subject, true, ['context' => context_system::instance()]),
            'status' => $t->status,
            'timeago' => get_string('ago', 'message', format_time(time() - $t->timecreated))
        ];
    }
    echo json_encode(array_values($res));
    die();
}

if ($action === 'widget_create_ticket') {
    require_sesskey();
    $subject = required_param('subject', PARAM_TEXT);
    $dept = required_param('department', PARAM_INT);
    $priority = required_param('priority', PARAM_INT);
    $desc = required_param('description', PARAM_TEXT);
    
    $ticket = new \stdClass();
    $ticket->userid = $USER->id;
    $ticket->departmentid = $dept;
    $ticket->subject = $subject;
    // Format description as array because ticket::create expects it (from Moodle forms)
    $ticket->description = ['text' => $desc, 'format' => FORMAT_MOODLE];
    $ticket->status = 0;
    $ticket->priority = $priority;
    
    $id = \local_aurasupport\ticket::create($ticket);
    if ($id) {
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
                $fs = get_file_storage();
                $filerecord = array(
                    'contextid' => context_system::instance()->id,
                    'component' => 'local_aurasupport',
                    'filearea'  => 'ticket_attachment',
                    'itemid'    => $id,
                    'filepath'  => '/',
                    'filename'  => $_FILES['attachment']['name']
                );
                $fs->create_file_from_pathname($filerecord, $_FILES['attachment']['tmp_name']);
            }
        }

        // Post-Submit AI Ticket Deflection for High (2) or Urgent (3) priority
        if ($priority == 2 || $priority == 3) {
            require_once($CFG->dirroot . '/local/aurasupport/classes/ai_manager.php');
            $combinedText = $subject . ' ' . $desc;
            $suggested = \local_aurasupport\ai_manager::suggest_kb($combinedText);
            
            if ($suggested) {
                $url = new moodle_url('/local/aurasupport/kb.php', ['id' => $suggested->id]);
                $admin = get_admin();
                $aimsg = "Hi, saya Aura AI. Masalah yang Anda alami sepertinya mirip dengan artikel ini: <br>";
                $aimsg .= "<strong><a href=\"" . $url->out(false) . "\" target=\"_blank\">" . format_string($suggested->title) . "</a></strong><br><br>";
                $aimsg .= "Apakah panduan ini bisa menyelesaikan masalah Anda?";
                
                \local_aurasupport\ticket::add_message($id, $admin->id, ['text' => $aimsg]);
            }
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Could not create ticket']);
    }
    die();
}

$ticketid = required_param('ticketid', PARAM_INT);
$ticket = \local_aurasupport\ticket::get_by_id($ticketid);
if (!$ticket) {
    echo json_encode(['error' => 'Invalid ticket']);
    die();
}

$is_agent_for_this = \local_aurasupport\ticket::is_agent_for_ticket($USER->id, $ticket);
if (!is_siteadmin() && $ticket->userid != $USER->id && !$is_agent_for_this) {
    echo json_encode(['error' => 'No permission']);
    die();
}

if ($action === 'widget_get_chat') {
    require_sesskey();
    global $DB;
    $sql = "SELECT m.*, u.firstname, u.lastname 
            FROM {local_aurasupport_messages} m
            JOIN {user} u ON m.userid = u.id
            WHERE m.ticketid = :ticketid
            ORDER BY m.timecreated ASC";
    $messages = $DB->get_records_sql($sql, ['ticketid' => $ticketid]);
    $res = [];
    
    $fs = get_file_storage();
    $syscontext = context_system::instance();

    // Add initial ticket description as the first message
    $desc_html = format_text($ticket->description);
    $ticket_files = $fs->get_area_files($syscontext->id, 'local_aurasupport', 'ticket_attachment', $ticket->id, 'filename', false);
    foreach ($ticket_files as $f) {
        $url = moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(), $f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename());
        $desc_html .= '<br><a href="'.$url.'" target="_blank"><img src="'.$url.'" style="max-width:100%; border-radius:8px; margin-top:5px; border: 1px solid #ddd;"></a>';
    }
    
    $creator = clone $USER;
    if ($ticket->userid != $USER->id) {
        $creator = $DB->get_record('user', ['id' => $ticket->userid]);
    }
    $res[] = [
        'id' => 0,
        'sender' => fullname($creator),
        'message' => $desc_html,
        'timeago' => get_string('ago', 'message', format_time(time() - $ticket->timecreated)),
        'is_mine' => ($ticket->userid == $USER->id)
    ];

    foreach ($messages as $msg) {
        $message_html = format_text($msg->message);
        
        // Fetch attachments
        $files = $fs->get_area_files($syscontext->id, 'local_aurasupport', 'message_attachment', $msg->id, 'filename', false);
        foreach ($files as $f) {
            $url = moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(), $f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename());
            $message_html .= '<br><a href="'.$url.'" target="_blank"><img src="'.$url.'" style="max-width:100%; border-radius:8px; margin-top:5px; border: 1px solid #ddd;"></a>';
        }

        $res[] = [
            'id' => $msg->id,
            'sender' => fullname($msg),
            'message' => $message_html,
            'timeago' => get_string('ago', 'message', format_time(time() - $msg->timecreated)),
            'is_mine' => ($msg->userid == $USER->id)
        ];
    }
    $ticket = \local_aurasupport\ticket::get_by_id($ticketid);
    echo json_encode([
        'messages' => array_values($res),
        'status' => $ticket->status
    ]);
    die();
}

if ($action === 'widget_send_reply') {
    require_sesskey();
    $message = optional_param('message', '', PARAM_TEXT);
    
    if ($ticket->status == 2 || $ticket->status == 3) {
        echo json_encode(['error' => 'This ticket is already resolved or closed.']);
        die();
    }
    
    // Create the message
    $msgid = \local_aurasupport\ticket::add_message($ticketid, $USER->id, ['text' => $message, 'format' => FORMAT_MOODLE]);
    
    // Handle attachment
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['attachment']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg', 'jpeg', 'png'])) {
            $fs = get_file_storage();
            $filerecord = array(
                'contextid' => context_system::instance()->id,
                'component' => 'local_aurasupport',
                'filearea'  => 'message_attachment',
                'itemid'    => $msgid,
                'filepath'  => '/',
                'filename'  => $_FILES['attachment']['name']
            );
            $fs->create_file_from_pathname($filerecord, $_FILES['attachment']['tmp_name']);
        }
    }
    
    echo json_encode(['success' => true]);
    die();
}

// Default action: Long-polling (Backward compatibility for view.php)
$lastmsgid = optional_param('lastmsgid', 0, PARAM_INT);
global $DB;
$sql = "SELECT m.*, u.firstname, u.lastname 
        FROM {local_aurasupport_messages} m
        JOIN {user} u ON m.userid = u.id
        WHERE m.ticketid = :ticketid AND m.id > :lastmsgid
        ORDER BY m.timecreated ASC";
$messages = $DB->get_records_sql($sql, ['ticketid' => $ticketid, 'lastmsgid' => $lastmsgid]);

$results = [];
$fs = get_file_storage();
$syscontext = context_system::instance();

foreach ($messages as $msg) {
    $is_admin = ($msg->userid != $ticket->userid);
    $wrapper_class = $is_admin ? 'admin' : 'user';
    $fullname = fullname($msg); // Because we fetched firstname, lastname
    
    $msg_html = format_text($msg->message);
    
    // Fetch message attachments
    $msgfiles = $fs->get_area_files($syscontext->id, 'local_aurasupport', 'message_attachment', $msg->id, 'filename', false);
    foreach ($msgfiles as $f) {
        $url = moodle_url::make_pluginfile_url($f->get_contextid(), $f->get_component(), $f->get_filearea(), $f->get_itemid(), $f->get_filepath(), $f->get_filename());
        $msg_html .= '<br><a href="'.$url.'" target="_blank"><img src="'.$url.'" style="max-width:100%; max-height: 400px; border-radius:8px; margin-top:5px; border: 1px solid #ddd;"></a>';
    }
    
    $results[] = [
        'id' => $msg->id,
        'html' => \html_writer::start_tag('div', ['class' => 'local_aurasupport-bubble-wrapper ' . $wrapper_class]) .
                  \html_writer::tag('div', '<strong>'.$fullname.'</strong> ('.userdate($msg->timecreated).')', ['class' => 'local_aurasupport-bubble-meta']) .
                  \html_writer::tag('div', $msg_html, ['class' => 'local_aurasupport-bubble']) .
                  \html_writer::end_tag('div')
    ];
}

echo json_encode(['messages' => $results]);
die();
