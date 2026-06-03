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

class ticket_form extends \moodleform {
    public function definition() {
        global $DB, $CFG;
        $mform = $this->_form;

        $mform->addElement('header', 'ticketheader', get_string('createticket', 'local_aurasupport'));

        $mform->addElement('text', 'subject', get_string('subject', 'local_aurasupport'), 'maxlength="255" size="50"');
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', get_string('required'), 'required', null, 'client');

        $context = \context_system::instance();
        if (has_capability('local/aurasupport:manage', $context)) {
            // Admin can select user
            $users = $DB->get_records_menu('user', ['deleted' => 0], 'firstname ASC', 'id, ' . $DB->sql_fullname());
            $mform->addElement('select', 'userid', 'For User (Admin Only)', [0 => 'Me'] + $users);
            $mform->setType('userid', PARAM_INT);
        }

        // Course selector
        $courses = $DB->get_records_menu('course', [], 'fullname ASC', 'id, fullname');
        if (!empty($courses)) {
            $mform->addElement('select', 'courseid', 'Related Course (Optional)', [0 => 'None'] + $courses);
            $mform->setType('courseid', PARAM_INT);
        }

        $depts = $DB->get_records_menu('local_aurasupport_depts', null, 'name ASC', 'id, name');
        if (!empty($depts)) {
            $mform->addElement('select', 'departmentid', get_string('department', 'local_aurasupport'), $depts);
            $mform->setType('departmentid', PARAM_INT);
            $mform->addRule('departmentid', get_string('required'), 'required', null, 'client');
        }

        $mform->addElement('editor', 'description', get_string('description', 'local_aurasupport'));
        $mform->setType('description', PARAM_CLEANHTML);
        $mform->addRule('description', null, 'required', null, 'client');

        $priorities = [
            0 => get_string('priority_low', 'local_aurasupport'),
            1 => get_string('priority_medium', 'local_aurasupport'),
            2 => get_string('priority_high', 'local_aurasupport'),
            3 => get_string('priority_urgent', 'local_aurasupport')
        ];
        $mform->addElement('select', 'priority', get_string('priority', 'local_aurasupport'), $priorities);
        $mform->setDefault('priority', 1);

        $mform->addElement('filemanager', 'attachments', get_string('attachments', 'local_aurasupport'), null, ['subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 5]);

        $this->add_action_buttons(true, get_string('submit', 'local_aurasupport'));
    }
}
