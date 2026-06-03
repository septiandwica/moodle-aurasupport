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

require_once('../../config.php');
require_once($CFG->dirroot . '/local/aurasupport/classes/form/kb_form.php');

$context = context_system::instance();
require_login();
require_capability('local/aurasupport:manage', $context);

$delete = optional_param('delete', 0, PARAM_INT);
if ($delete) {
    require_sesskey();
    $DB->delete_records('local_aurasupport_kb', ['id' => $delete]);
    redirect(new moodle_url('/local/aurasupport/manage_kb.php'));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aurasupport/manage_kb.php'));
$PAGE->set_title('Manage Knowledge Base');
$PAGE->set_heading('Manage Knowledge Base');

$mform = new \local_aurasupport\form\kb_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/aurasupport/kb.php'));
} else if ($data = $mform->get_data()) {
    $article = new \stdClass();
    $article->title = $data->title;
    $article->content = $data->content['text'];
    $article->authorid = $USER->id;
    $article->timecreated = time();
    $article->timemodified = time();
    
    $DB->insert_record('local_aurasupport_kb', $article);
    \core\notification::add('Article published.', \core\notification::SUCCESS);
    redirect(new moodle_url('/local/aurasupport/manage_kb.php'));
}

echo $OUTPUT->header();

$articles = $DB->get_records('local_aurasupport_kb', null, 'timecreated DESC');
if (!empty($articles)) {
    $table = new html_table();
    $table->head = ['Title', 'Created', 'Action'];
    $table->data = [];
    foreach ($articles as $a) {
        $delurl = new moodle_url('/local/aurasupport/manage_kb.php', ['delete' => $a->id, 'sesskey' => sesskey()]);
        $dellink = html_writer::link($delurl, 'Delete', ['class' => 'text-danger']);
        $viewurl = new moodle_url('/local/aurasupport/kb.php', ['id' => $a->id]);
        $viewlink = html_writer::link($viewurl, format_string($a->title));
        $table->data[] = [$viewlink, userdate($a->timecreated), $dellink];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('No articles found.', 'info');
}

echo html_writer::tag('hr', '');
echo html_writer::tag('h3', 'Add New Article');
$mform->display();

echo html_writer::link(new moodle_url('/local/aurasupport/kb.php'), 'Back to Knowledge Base', ['class' => 'btn btn-secondary mt-3']);
echo $OUTPUT->footer();
