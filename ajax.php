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
    global $DB;
    // Get active/recent tickets for current user
    $sql = "SELECT * FROM {local_aurasupport_tickets} WHERE userid = :userid ORDER BY timecreated DESC LIMIT 10";
    $tickets = $DB->get_records_sql($sql, ['userid' => $USER->id]);
    $res = [];
    foreach ($tickets as $t) {
        $res[] = [
            'id' => $t->id,
            'subject' => $t->subject,
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
    $ticket->description = $desc;
    $ticket->status = 0;
    $ticket->priority = $priority;
    
    $id = \local_aurasupport\ticket::create($ticket);
    if ($id) {
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
    global $DB;
    $sql = "SELECT m.*, u.firstname, u.lastname 
            FROM {local_aurasupport_messages} m
            JOIN {user} u ON m.userid = u.id
            WHERE m.ticketid = :ticketid
            ORDER BY m.timecreated ASC";
    $messages = $DB->get_records_sql($sql, ['ticketid' => $ticketid]);
    $res = [];
    foreach ($messages as $msg) {
        $res[] = [
            'id' => $msg->id,
            'sender' => fullname($msg),
            'message' => format_text($msg->message),
            'timeago' => get_string('ago', 'message', format_time(time() - $msg->timecreated)),
            'is_mine' => ($msg->userid == $USER->id)
        ];
    }
    echo json_encode(array_values($res));
    die();
}

if ($action === 'widget_send_reply') {
    require_sesskey();
    $message = required_param('message', PARAM_TEXT);
    \local_aurasupport\ticket::add_message($ticketid, $USER->id, $message);
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
foreach ($messages as $msg) {
    $is_admin = ($msg->userid != $ticket->userid);
    $wrapper_class = $is_admin ? 'admin' : 'user';
    $fullname = fullname($msg); // Because we fetched firstname, lastname
    
    $results[] = [
        'id' => $msg->id,
        'html' => \html_writer::start_tag('div', ['class' => 'local_aurasupport-bubble-wrapper ' . $wrapper_class]) .
                  \html_writer::tag('div', '<strong>'.$fullname.'</strong> ('.userdate($msg->timecreated).')', ['class' => 'local_aurasupport-bubble-meta']) .
                  \html_writer::tag('div', format_text($msg->message), ['class' => 'local_aurasupport-bubble']) .
                  \html_writer::end_tag('div')
    ];
}

echo json_encode(['messages' => $results]);
die();
