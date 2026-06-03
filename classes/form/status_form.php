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

class status_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;
        
        $ticketid = $this->_customdata['ticketid'] ?? 0;
        
        $mform->addElement('hidden', 'id', $ticketid);
        $mform->setType('id', PARAM_INT);

        $statuses = [
            0 => get_string('status_open', 'local_aurasupport'),
            1 => get_string('status_pending', 'local_aurasupport'),
            2 => get_string('status_resolved', 'local_aurasupport'),
            3 => get_string('status_closed', 'local_aurasupport')
        ];
        $mform->addElement('select', 'status', get_string('status', 'local_aurasupport'), $statuses);
        
        $this->add_action_buttons(false, get_string('updatestatus', 'local_aurasupport'));
    }
}
