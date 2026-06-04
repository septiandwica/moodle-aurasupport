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

class ai_manager {
    public static function is_enabled() {
        return get_config('local_aurasupport', 'enable_ai') && !empty(get_config('local_aurasupport', 'gemini_api_key'));
    }

    public static function generate_reply($ticket_subject, $ticket_description, $history = '', $submitter_name = 'User', $agent_name = 'Agent', $dept_name = '') {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $apikey = get_config('local_aurasupport', 'gemini_api_key');
        if (empty($apikey)) {
            return "Error: Gemini API key is not configured.";
        }

        $model = get_config('local_aurasupport', 'gemini_model');
        if (empty($model)) {
            $model = 'gemini-3.5-flash';
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apikey;

        $prompt = "You are a highly professional, empathetic, and solution-oriented IT Support/Customer Service agent. Your task is to draft a reply for a user's (student/teacher/staff) support ticket.\n\n";
        $prompt .= "Ticket Subject: " . $ticket_subject . "\n";
        $prompt .= "Ticket Description: " . $ticket_description . "\n";
        if (!empty($history)) {
            $prompt .= "\nPrevious Conversation History:\n" . $history . "\n";
        }
        
        $dept_str = !empty($dept_name) ? " - " . $dept_name : "";

        $prompt .= "\nInstructions for AI:\n";
        $prompt .= "1. ALWAYS start with the exact greeting: 'Hi there {$submitter_name},'.\n";
        $prompt .= "2. Show empathy and apologize for any inconvenience if the user is reporting an error or issue.\n";
        $prompt .= "3. Provide technical solutions or steps that are clear, logical, and easy to follow. Use HTML bullet points/lists if you need to explain steps.\n";
        $prompt .= "4. End the reply EXACTLY with this format:\n";
        $prompt .= "   Best regards,\n";
        $prompt .= "   {$agent_name}{$dept_str}\n";
        $prompt .= "5. DO NOT reply with introductory meta-text like 'Sure, here is the draft'. Output ONLY the body of the reply itself.\n";
        $prompt .= "6. Format the response using clean, pure HTML elements (use <p>, <ul>, <li>, <strong>, <br>).\n";

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.4
            ]
        ];

        $curl = new \curl();
        $curl->setHeader('Content-Type: application/json');
        
        $response = $curl->post($url, json_encode($data));
        
        if ($curl->get_info()['http_code'] !== 200) {
            return "<p><em>Error from AI Provider:</em> " . s($response) . "</p>";
        }

        $result = json_decode($response);
        if (isset($result->candidates[0]->content->parts[0]->text)) {
            // Convert simple markdown-like output to HTML for Moodle editor
            $text = $result->candidates[0]->content->parts[0]->text;
            $html = format_text($text, FORMAT_MARKDOWN);
            return $html;
        }

        return "<p>Failed to parse AI response.</p>";
    }
}
