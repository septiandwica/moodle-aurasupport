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

class sla_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        $priorities = [
            0 => get_string('priority_low', 'local_aurasupport'),
            1 => get_string('priority_medium', 'local_aurasupport'),
            2 => get_string('priority_high', 'local_aurasupport'),
            3 => get_string('priority_urgent', 'local_aurasupport')
        ];
        $mform->addElement('select', 'priority', get_string('priority', 'local_aurasupport'), $priorities);

        $mform->addElement('text', 'responsetime', 'Max Response Time (minutes)', 'maxlength="10" size="10"');
        $mform->setType('responsetime', PARAM_INT);
        $mform->addRule('responsetime', null, 'required', null, 'client');
        $mform->addRule('responsetime', null, 'numeric', null, 'client');

        $mform->addElement('text', 'resolutiontime', 'Max Resolution Time (minutes)', 'maxlength="10" size="10"');
        $mform->setType('resolutiontime', PARAM_INT);
        $mform->addRule('resolutiontime', null, 'required', null, 'client');
        $mform->addRule('resolutiontime', null, 'numeric', null, 'client');

        $this->add_action_buttons(false, 'Save SLA Rule');
    }
}
