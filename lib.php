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

defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation for AuraSupport.
 */
function local_aurasupport_extend_navigation(global_navigation $navigation) {
    global $USER;
    
    // Add Helpdesk node to navigation
    if (isloggedin() && !isguestuser()) {
        $helpdesk = $navigation->add(get_string('pluginname', 'local_aurasupport'), new moodle_url('/local/aurasupport/tickets.php'), navigation_node::TYPE_CUSTOM, null, 'local_aurasupport');
        $helpdesk->showinflatnavigation = true;
        
        $helpdesk->add(get_string('tickets', 'local_aurasupport'), new moodle_url('/local/aurasupport/tickets.php'));
        $helpdesk->add('Knowledge Base', new moodle_url('/local/aurasupport/kb.php'));
        
        // Add Dashboard and Management links for managers
        if (has_capability('local/aurasupport:manage', context_system::instance())) {
            $helpdesk->add(get_string('dashboard', 'local_aurasupport'), new moodle_url('/local/aurasupport/index.php'));
            $helpdesk->add('Manage Departments', new moodle_url('/local/aurasupport/manage_depts.php'));
            $helpdesk->add('Manage Agents', new moodle_url('/local/aurasupport/manage_agents.php'));
            $helpdesk->add('Manage SLA Rules', new moodle_url('/local/aurasupport/manage_sla.php'));
        }
    }
}
