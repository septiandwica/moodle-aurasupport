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

namespace local_aurasupport\form;

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/formslib.php");

class reassign_form extends \moodleform {
    public function definition() {
        global $DB;
        $mform = $this->_form;
        
        $ticketid = $this->_customdata['ticketid'] ?? 0;
        
        $mform->addElement('hidden', 'id', $ticketid);
        $mform->setType('id', PARAM_INT);

        // Get all agents
        $sql = "SELECT u.id, u.firstname, u.lastname, d.name AS deptname 
                FROM {local_aurasupport_agents} a
                JOIN {user} u ON a.userid = u.id
                JOIN {local_aurasupport_depts} d ON a.departmentid = d.id";
        $agents = $DB->get_records_sql($sql);
        $options = [0 => 'Unassigned'];
        foreach ($agents as $a) {
            $options[$a->id] = fullname($a) . ' (' . $a->deptname . ')';
        }
        
        $mform->addElement('select', 'agentid', 'Assign to Agent', $options);
        
        $this->add_action_buttons(false, 'Re-assign Ticket');
    }
}
