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

class agent_form extends \moodleform {
    public function definition() {
        global $DB;
        $mform = $this->_form;

        $mform->addElement('text', 'useremail', 'User Email', 'maxlength="100" size="30"');
        $mform->setType('useremail', PARAM_EMAIL);
        $mform->addRule('useremail', null, 'required', null, 'client');
        $mform->addElement('static', 'useremail_help', '', 'Enter the exact email of the user to make them an agent.');

        $depts = $DB->get_records_menu('local_aurasupport_depts', null, 'name ASC', 'id, name');
        if (empty($depts)) {
            $depts = [0 => 'No departments found. Please create one first.'];
        }
        $mform->addElement('select', 'departmentid', get_string('department', 'local_aurasupport'), $depts);

        $this->add_action_buttons(false, 'Assign Agent');
    }
}
