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

namespace local_aurasupport;

defined('MOODLE_INTERNAL') || die();

class email_manager {
    public static function send_ticket_created($ticket) {
        global $CFG, $DB;
        
        // Only send if enabled in settings
        if (!get_config('local_aurasupport', 'enable_email')) {
            return;
        }
        
        $user = $DB->get_record('user', ['id' => $ticket->userid]);
        $subject = get_string('email_created_subject', 'local_aurasupport', $ticket->id);
        $message = get_string('email_created_body', 'local_aurasupport', [
            'subject' => $ticket->subject,
            'url' => $CFG->wwwroot . '/local/aurasupport/view.php?id=' . $ticket->id
        ]);
        
        // In a real plugin, we'd send to all agents of the department.
        // For simplicity, we send to admin.
        $admin = get_admin();
        
        $eventdata = new \core\message\message();
        $eventdata->component         = 'local_aurasupport';
        $eventdata->name              = 'ticketcreated';
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->userto            = $admin;
        $eventdata->subject           = $subject;
        $eventdata->fullmessage       = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        $eventdata->notification      = 1;
        
        message_send($eventdata);
    }
    
    public static function send_ticket_replied($ticket, $replyuserid) {
        global $CFG, $DB;
        
        if (!get_config('local_aurasupport', 'enable_email')) {
            return;
        }
        
        $subject = get_string('email_replied_subject', 'local_aurasupport', $ticket->id);
        $message = get_string('email_replied_body', 'local_aurasupport', [
            'url' => $CFG->wwwroot . '/local/aurasupport/view.php?id=' . $ticket->id
        ]);
        
        // If agent replied, send to user. If user replied, send to agent/admin.
        if ($ticket->userid == $replyuserid) {
            $to = get_admin();
        } else {
            $to = $DB->get_record('user', ['id' => $ticket->userid]);
        }
        
        $eventdata = new \core\message\message();
        $eventdata->component         = 'local_aurasupport';
        $eventdata->name              = 'ticketreplied';
        $eventdata->userfrom          = \core_user::get_noreply_user();
        $eventdata->userto            = $to;
        $eventdata->subject           = $subject;
        $eventdata->fullmessage       = $message;
        $eventdata->fullmessageformat = FORMAT_PLAIN;
        $eventdata->fullmessagehtml   = '';
        $eventdata->smallmessage      = '';
        $eventdata->notification      = 1;
        
        message_send($eventdata);
    }
}
