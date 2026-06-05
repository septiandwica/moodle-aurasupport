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
            'AuraSupport', 
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

    if (is_siteadmin()) {
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
            'id' => 'consumption',
            'name' => 'AI Consumption',
            'url' => new moodle_url('/local/aurasupport/consumption.php')
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

/**
 * Inject the floating widget into the footer of every Moodle page.
 */
function local_aurasupport_before_footer() {
    global $CFG, $USER, $PAGE, $OUTPUT;
    
    // Only show for logged in users
    if (!isloggedin() || isguestuser()) {
        return '';
    }
    
    // Check if widget is enabled in settings
    if (!get_config('local_aurasupport', 'enable_widget')) {
        // We can default to enabled if not set, but let's assume it's always enabled for now.
        // Actually, we'll just show it.
    }
    
    global $DB;
    // Fetch departments for the ticket creation form
    $depts = $DB->get_records('local_aurasupport_depts', null, 'name ASC');
    $dept_arr = [];
    foreach ($depts as $d) {
        $dept_arr[] = ['id' => $d->id, 'name' => $d->name];
    }
    
    // Priorities
    $priorities = [
        ['id' => 0, 'name' => 'Low', 'is_normal' => false],
        ['id' => 1, 'name' => 'Normal', 'is_normal' => true],
        ['id' => 2, 'name' => 'High', 'is_normal' => false],
        ['id' => 3, 'name' => 'Urgent', 'is_normal' => false]
    ];

    // Fetch courses
    $course_arr = [];
    if (is_siteadmin()) {
        $all_courses = $DB->get_records('course', null, 'fullname ASC', 'id, fullname');
        foreach ($all_courses as $c) {
            $course_arr[] = ['id' => $c->id, 'name' => format_string($c->fullname)];
        }
    } else {
        require_once($CFG->dirroot.'/enrol/locallib.php');
        $enrolled_courses = enrol_get_users_courses($USER->id, true);
        foreach ($enrolled_courses as $c) {
            $course_arr[] = ['id' => $c->id, 'name' => format_string($c->fullname)];
        }
    }

    // Get plugin release version
    $pluginman = \core_plugin_manager::instance();
    $plugininfo = $pluginman->get_plugin_info('local_aurasupport');
    $version_text = $plugininfo ? $plugininfo->release : 'v1.0.0';
    
    // Check if user has overridden the footer text in settings
    $footer_text = get_config('local_aurasupport', 'widget_footer_text');
    if ($footer_text === false || trim($footer_text) === '') {
        $footer_text = 'AuraSupport ' . $version_text;
    } else {
        // Allow placeholder {{version}} in the setting
        $footer_text = str_replace('{{version}}', $version_text, $footer_text);
    }

    $template_data = [
        'wwwroot' => $CFG->wwwroot,
        'sesskey' => sesskey(),
        'departments' => $dept_arr,
        'priorities' => $priorities,
        'courses' => $course_arr,
        'footer_text' => $footer_text
    ];

    // Render the widget template
    return $OUTPUT->render_from_template('local_aurasupport/widget', $template_data);
}

/**
 * Serves the files from the local_aurasupport file areas.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param context $context The context object.
 * @param string $filearea The file area.
 * @param array $args The file arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options Additional options.
 * @return void|false
 */
function local_aurasupport_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    global $DB, $USER;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    if ($filearea !== 'ticket_attachment' && $filearea !== 'message_attachment') {
        return false;
    }

    $itemid = (int)array_shift($args);
    
    // Check permissions
    if ($filearea === 'ticket_attachment') {
        $ticket = $DB->get_record('local_aurasupport_tickets', ['id' => $itemid], '*', MUST_EXIST);
    } else {
        $msg = $DB->get_record('local_aurasupport_messages', ['id' => $itemid], '*', MUST_EXIST);
        $ticket = $DB->get_record('local_aurasupport_tickets', ['id' => $msg->ticketid], '*', MUST_EXIST);
    }
    
    $is_agent_for_this = \local_aurasupport\ticket::is_agent_for_ticket($USER->id, $ticket);
    if (!is_siteadmin() && $ticket->userid != $USER->id && !$is_agent_for_this) {
        return false; // No permission
    }

    $filename = array_pop($args);
    if (!$args) {
        $filepath = '/';
    } else {
        $filepath = '/'.implode('/', $args).'/';
    }

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_aurasupport', $filearea, $itemid, $filepath, $filename);

    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options);
}
