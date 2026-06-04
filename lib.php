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
 * Extend user menu navigation (Top right profile dropdown).
 * This guarantees the menu appears in modern Moodle 4.x themes.
 */
function local_aurasupport_extend_navigation_user(navigation_node $parent) {
    if (isloggedin() && !isguestuser()) {
        $parent->add(
            get_string('pluginname', 'local_aurasupport') . ' (Helpdesk)', 
            new moodle_url('/local/aurasupport/tickets.php'), 
            navigation_node::TYPE_CUSTOM, 
            null, 
            'local_aurasupport'
        );
    }
}

/**
 * Print tabbed navigation for AuraSupport pages.
 */
function local_aurasupport_print_tabs($current_tab = 'tickets') {
    global $CFG;

    $tabs = [];
    $tabs[] = [
        'id' => 'tickets',
        'name' => 'Tickets',
        'url' => new moodle_url('/local/aurasupport/tickets.php')
    ];
    $tabs[] = [
        'id' => 'kb',
        'name' => 'Knowledge Base',
        'url' => new moodle_url('/local/aurasupport/kb.php')
    ];

    if (has_capability('moodle/site:config', context_system::instance())) {
        $tabs[] = [
            'id' => 'dashboard',
            'name' => 'BI Dashboard',
            'url' => new moodle_url('/local/aurasupport/index.php')
        ];
        $tabs[] = [
            'id' => 'departments',
            'name' => 'Departments',
            'url' => new moodle_url('/local/aurasupport/manage_depts.php')
        ];
        $tabs[] = [
            'id' => 'agents',
            'name' => 'Agents',
            'url' => new moodle_url('/local/aurasupport/manage_agents.php')
        ];
        $tabs[] = [
            'id' => 'sla',
            'name' => 'SLA Rules',
            'url' => new moodle_url('/local/aurasupport/manage_sla.php')
        ];
        $tabs[] = [
            'id' => 'settings',
            'name' => 'Settings',
            'url' => new moodle_url('/admin/settings.php', ['section' => 'local_aurasupport'])
        ];
    }

    echo \html_writer::start_tag('ul', ['class' => 'nav nav-tabs mb-4']);
    foreach ($tabs as $tab) {
        $active = ($tab['id'] === $current_tab) ? ' active font-weight-bold' : '';
        echo \html_writer::start_tag('li', ['class' => 'nav-item']);
        echo \html_writer::link($tab['url'], $tab['name'], ['class' => 'nav-link' . $active]);
        echo \html_writer::end_tag('li');
    }
    echo \html_writer::end_tag('ul');
}
