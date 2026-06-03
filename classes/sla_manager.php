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

class sla_manager {
    /**
     * Get the target response timestamp for a ticket
     */
    public static function get_target_response_time($ticket) {
        global $DB;
        $sla = $DB->get_record('local_aurasupport_sla', ['priority' => $ticket->priority]);
        if (!$sla) {
            // Default 24 hours if SLA not defined
            return $ticket->timecreated + (24 * 60 * 60);
        }
        return $ticket->timecreated + ($sla->responsetime * 60);
    }
    
    /**
     * Check if a ticket has breached its SLA response time
     */
    public static function is_breached($ticket) {
        // If it's already resolved or closed, it's not currently breaching
        if ($ticket->status >= 2) {
            return false;
        }
        
        $target = self::get_target_response_time($ticket);
        return (time() > $target);
    }
    
    /**
     * Get the time remaining (or overdue by) in seconds
     */
    public static function get_time_remaining($ticket) {
        $target = self::get_target_response_time($ticket);
        return $target - time();
    }
}
