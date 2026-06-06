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

class ticket {
    public static function create($data) {
        global $DB, $USER;
        $ticket = new \stdClass();
        $ticket->userid = isset($data->userid) ? $data->userid : $USER->id;
        $ticket->courseid = isset($data->courseid) ? $data->courseid : null;
        $ticket->guest_email = isset($data->guest_email) ? $data->guest_email : '';
        $ticket->subject = $data->subject;
        $ticket->description = $data->description['text'];
        $ticket->status = 0; // 0 = Open
        $ticket->priority = $data->priority ?? 1; // Default medium
        $ticket->departmentid = $data->departmentid ?? null;
        $ticket->timecreated = time();
        $ticket->timemodified = time();
        
        $id = $DB->insert_record('local_aurasupport_tickets', $ticket);
        
        $ticket->id = $id;
        \local_aurasupport\email_manager::send_ticket_created($ticket);
        
        return $id;
    }
    
    public static function get_all_for_user($userid) {
        global $DB;
        return $DB->get_records('local_aurasupport_tickets', ['userid' => $userid], 'timecreated DESC');
    }
    
    public static function get_all() {
        global $DB;
        return $DB->get_records('local_aurasupport_tickets', null, 'timecreated DESC');
    }
    
    public static function get_by_id($id) {
        global $DB;
        return $DB->get_record('local_aurasupport_tickets', ['id' => $id], '*', MUST_EXIST);
    }
    
    public static function add_message($ticketid, $userid, $message) {
        global $DB;
        $msg = new \stdClass();
        $msg->ticketid = $ticketid;
        $msg->userid = $userid;
        $msg->message = $message['text'];
        $msg->timecreated = time();
        
        $msgid = $DB->insert_record('local_aurasupport_messages', $msg);
        
        // Update ticket timemodified
        $DB->set_field('local_aurasupport_tickets', 'timemodified', time(), ['id' => $ticketid]);
        
        $ticket = self::get_by_id($ticketid);
        \local_aurasupport\email_manager::send_ticket_replied($ticket, $userid);
        
        return $msgid;
    }
    
    public static function get_messages($ticketid) {
        global $DB;
        return $DB->get_records('local_aurasupport_messages', ['ticketid' => $ticketid], 'timecreated ASC');
    }
    
    public static function update_status($ticketid, $status) {
        global $DB;
        $DB->set_field('local_aurasupport_tickets', 'status', $status, ['id' => $ticketid]);
        $DB->set_field('local_aurasupport_tickets', 'timemodified', time(), ['id' => $ticketid]);
    }

    public static function is_agent($userid) {
        global $DB;
        return $DB->record_exists('local_aurasupport_agents', ['userid' => $userid]);
    }

    public static function is_agent_for_ticket($userid, $ticket) {
        global $DB;
        if (empty($ticket->departmentid)) {
            return self::is_agent($userid); // If no dept, all agents can see? Let's say yes for general tickets
        }
        return $DB->record_exists('local_aurasupport_agents', ['userid' => $userid, 'departmentid' => $ticket->departmentid]);
    }
}
