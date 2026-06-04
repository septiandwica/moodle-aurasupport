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

$id = optional_param('id', 0, PARAM_INT);
$search = optional_param('search', '', PARAM_TEXT);

$context = context_system::instance();
require_login();
require_capability('local/aurasupport:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/aurasupport/kb.php'));
$PAGE->set_title('Knowledge Base');
$PAGE->set_heading('Knowledge Base');

echo $OUTPUT->header();

echo html_writer::start_tag('div', ['class' => 'local_aurasupport-container']);
local_aurasupport_print_tabs('kb');

echo html_writer::start_tag('div', ['class' => 'jumbotron local_aurasupport-header']);
echo html_writer::tag('h1', 'How can we help you today?');
echo html_writer::start_tag('form', ['action' => 'kb.php', 'method' => 'get']);
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'search', 'value' => $search, 'class' => 'form-control form-control-lg', 'placeholder' => 'Search articles...']);
echo html_writer::end_tag('form');
echo html_writer::end_tag('div');

if ($id) {
    $article = $DB->get_record('local_aurasupport_kb', ['id' => $id], '*', IGNORE_MISSING);
    if ($article) {
        echo html_writer::tag('h2', format_string($article->title));
        echo html_writer::tag('div', format_text($article->content, FORMAT_HTML), ['class' => 'box p-4 bg-white border rounded shadow-sm mt-3']);
    } else {
        echo $OUTPUT->notification('The requested article could not be found. It may have been deleted.', 'error');
    }
    echo html_writer::link(new moodle_url('/local/aurasupport/kb.php'), '&laquo; Back to all articles', ['class' => 'mt-4 d-block']);
} else {
    $sql = "SELECT * FROM {local_aurasupport_kb} ";
    $params = [];
    if (!empty($search)) {
        $sql .= "WHERE " . $DB->sql_like('title', ':search', false, false);
        $params['search'] = '%' . $search . '%';
    }
    $sql .= " ORDER BY timecreated DESC";
    
    $articles = $DB->get_records_sql($sql, $params);
    
    if (empty($articles)) {
        echo $OUTPUT->notification('No articles found matching your criteria.', 'info');
    } else {
        echo html_writer::start_tag('div', ['class' => 'row']);
        foreach ($articles as $a) {
            echo html_writer::start_tag('div', ['class' => 'col-md-4 mb-4']);
            echo html_writer::start_tag('div', ['class' => 'card local_aurasupport-card h-100']);
            echo html_writer::start_tag('div', ['class' => 'card-body']);
            $url = new moodle_url('/local/aurasupport/kb.php', ['id' => $a->id]);
            echo html_writer::tag('h5', html_writer::link($url, format_string($a->title)), ['class' => 'card-title']);
            // Show snippet
            $snippet = shorten_text(strip_tags($a->content), 100);
            echo html_writer::tag('p', $snippet, ['class' => 'card-text text-muted']);
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');
        }
        echo html_writer::end_tag('div');
    }
}

if (is_siteadmin()) {
    echo html_writer::link(new moodle_url('/local/aurasupport/manage_kb.php'), 'Manage Articles', ['class' => 'btn btn-outline-primary mt-4']);
}

echo html_writer::end_tag('div');

echo $OUTPUT->footer();
