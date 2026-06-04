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

$ticketid = required_param('ticketid', PARAM_INT);
$lastmsgid = optional_param('lastmsgid', 0, PARAM_INT);

$ticket = \local_aurasupport\ticket::get_by_id($ticketid);
if (!$ticket) {
    echo json_encode(['error' => 'Invalid ticket']);
    die();
}

if (!is_siteadmin() && $ticket->userid != $USER->id) {
    echo json_encode(['error' => 'No permission']);
    die();
}

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
