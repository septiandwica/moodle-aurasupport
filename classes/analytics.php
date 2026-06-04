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

class analytics {
    public static function get_summary_stats() {
        global $DB;
        
        $stats = new \stdClass();
        $stats->total = $DB->count_records('local_aurasupport_tickets');
        $stats->open = $DB->count_records('local_aurasupport_tickets', ['status' => 0]);
        $stats->resolved = $DB->count_records('local_aurasupport_tickets', ['status' => 2]);
        $stats->closed = $DB->count_records('local_aurasupport_tickets', ['status' => 3]);
        
        return $stats;
    }
    
    public static function get_chart_data() {
        global $DB;
        
        // Count tickets by status for a pie chart
        $statuses = [
            'Open' => (int)$DB->count_records('local_aurasupport_tickets', ['status' => 0]),
            'Pending' => (int)$DB->count_records('local_aurasupport_tickets', ['status' => 1]),
            'Resolved' => (int)$DB->count_records('local_aurasupport_tickets', ['status' => 2]),
            'Closed' => (int)$DB->count_records('local_aurasupport_tickets', ['status' => 3]),
        ];
        
        $chartdata = [
            'status_series' => array_values($statuses),
            'status_labels' => array_keys($statuses)
        ];
        
        return $chartdata;
    }

    public static function get_advanced_chart_data($days = 30) {
        global $DB;
        $cutoff = time() - ($days * 24 * 60 * 60);
        
        // 1. Line Chart (Tickets over time)
        $tickets = $DB->get_records_select('local_aurasupport_tickets', 'timecreated > ?', [$cutoff], 'timecreated ASC', 'id, timecreated');
        $trend_data = [];
        foreach ($tickets as $t) {
            $date = date('M d', $t->timecreated);
            if (!isset($trend_data[$date])) {
                $trend_data[$date] = 0;
            }
            $trend_data[$date]++;
        }
        if (empty($trend_data)) {
            $trend_data[date('M d')] = 0;
        }
        
        // 2. Bar Chart (By Department)
        $sql = "SELECT d.name, COUNT(t.id) AS count 
                FROM {local_aurasupport_tickets} t
                LEFT JOIN {local_aurasupport_depts} d ON t.departmentid = d.id
                GROUP BY d.name";
        $dept_counts = $DB->get_records_sql($sql);
        $dept_data = [];
        $dept_labels = [];
        foreach ($dept_counts as $dc) {
            $name = $dc->name ? $dc->name : 'General';
            $dept_labels[] = $name;
            $dept_data[] = (int)$dc->count;
        }
        if (empty($dept_labels)) {
            $dept_labels = ['No Data'];
            $dept_data = [0];
        }
        
        // 3. Leaderboard
        $sql = "SELECT u.id, u.firstname, u.lastname, COUNT(t.id) AS count
                FROM {local_aurasupport_tickets} t
                JOIN {user} u ON t.agentid = u.id
                WHERE t.status >= 2
                GROUP BY u.id, u.firstname, u.lastname
                ORDER BY count DESC
                LIMIT 5";
        $leaders = $DB->get_records_sql($sql);
        $leader_html = '';
        foreach ($leaders as $l) {
            $leader_html .= "<tr><td>" . fullname($l) . "</td><td>" . $l->count . "</td></tr>";
        }

        // 4. Top Ticket Openers
        $sql = "SELECT u.id, u.firstname, u.lastname, COUNT(t.id) AS count
                FROM {local_aurasupport_tickets} t
                JOIN {user} u ON t.userid = u.id
                GROUP BY u.id, u.firstname, u.lastname
                ORDER BY count DESC
                LIMIT 5";
        $top_users = $DB->get_records_sql($sql);
        $top_users_html = '';
        foreach ($top_users as $u) {
            $top_users_html .= "<tr><td>" . fullname($u) . "</td><td>" . $u->count . "</td></tr>";
        }
        
        // 5. Avg Resolution Time
        $sql = "SELECT AVG(timemodified - timecreated) AS avg_time
                FROM {local_aurasupport_tickets}
                WHERE status >= 2 AND timemodified > timecreated";
        $avg_time_rec = $DB->get_record_sql($sql);
        $avg_time_str = 'N/A';
        if ($avg_time_rec && $avg_time_rec->avg_time) {
            $hours = round($avg_time_rec->avg_time / 3600, 1);
            $avg_time_str = $hours . ' Hours';
        }
        
        // 6. Tickets by Priority
        $sql = "SELECT priority, COUNT(id) AS count FROM {local_aurasupport_tickets} GROUP BY priority";
        $priority_counts = $DB->get_records_sql($sql);
        $priority_labels = ['Low', 'Medium', 'High', 'Urgent'];
        $priority_data = [0, 0, 0, 0];
        foreach ($priority_counts as $pc) {
            $p = (int)$pc->priority;
            if ($p >= 0 && $p <= 3) {
                $priority_data[$p] = (int)$pc->count;
            }
        }

        // 7. Tickets by Course
        $sql = "SELECT c.shortname, COUNT(t.id) AS count 
                FROM {local_aurasupport_tickets} t
                JOIN {course} c ON t.courseid = c.id
                GROUP BY c.shortname";
        $course_counts = $DB->get_records_sql($sql);
        $course_labels = [];
        $course_data = [];
        foreach ($course_counts as $cc) {
            $course_labels[] = $cc->shortname;
            $course_data[] = (int)$cc->count;
        }
        if (empty($course_labels)) {
            $course_labels = ['No Data'];
            $course_data = [0];
        }

        // 8. Recent Unresolved Tickets
        $sql = "SELECT t.id, t.subject, u.firstname, u.lastname, t.timecreated
                FROM {local_aurasupport_tickets} t
                JOIN {user} u ON t.userid = u.id
                WHERE t.status < 2
                ORDER BY t.timecreated DESC LIMIT 5";
        $recent_unresolved = $DB->get_records_sql($sql);
        $recent_unresolved_html = '';
        foreach ($recent_unresolved as $ru) {
            $recent_unresolved_html .= "<tr><td>" . s($ru->subject) . "</td><td>" . fullname($ru) . "</td><td>" . date('d/m/Y', $ru->timecreated) . "</td></tr>";
        }
        
        return [
            'trend_dates' => array_keys($trend_data),
            'trend_counts' => array_values($trend_data),
            'dept_labels' => $dept_labels,
            'dept_data' => $dept_data,
            'leader_html' => $leader_html,
            'top_users_html' => $top_users_html,
            'avg_response_time' => $avg_time_str,
            'priority_labels' => $priority_labels,
            'priority_data' => $priority_data,
            'course_labels' => $course_labels,
            'course_data' => $course_data,
            'recent_unresolved_html' => $recent_unresolved_html
        ];
    }
}
