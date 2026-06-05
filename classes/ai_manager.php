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

    private static function log_usage($action, $prompt_tokens, $completion_tokens) {
        global $DB, $USER;
        $userid = (isloggedin() && !isguestuser() && isset($USER->id)) ? $USER->id : 0;
        
        // Ensure table exists (in case user hasn't upgraded DB yet)
        $dbman = $DB->get_manager();
        $table = new \xmldb_table('local_aurasupport_ai_logs');
        if ($dbman->table_exists($table)) {
            $record = new \stdClass();
            $record->userid = $userid;
            $record->action = $action;
            $record->tokens_prompt = $prompt_tokens;
            $record->tokens_completion = $completion_tokens;
            $record->timecreated = time();
            $DB->insert_record('local_aurasupport_ai_logs', $record);
        }
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
        $prompt .= "1. ALWAYS start with the exact greeting: 'Hi {$submitter_name},'.\n";
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
        
        if (isset($result->usageMetadata)) {
            self::log_usage('generate_reply', $result->usageMetadata->promptTokenCount ?? 0, $result->usageMetadata->candidatesTokenCount ?? 0);
        }

        if (isset($result->candidates[0]->content->parts[0]->text)) {
            // Convert simple markdown-like output to HTML for Moodle editor
            $text = $result->candidates[0]->content->parts[0]->text;
            $html = format_text($text, FORMAT_MARKDOWN);
            return $html;
        }

        return "<p>Failed to parse AI response.</p>";
    }

    public static function process_auto_response($ticketid, $userid, $ticket_subject, $ticket_description, $priority) {
        global $DB;

        if (!self::is_enabled()) {
            return;
        }

        $priority_limit = get_config('local_aurasupport', 'auto_reply_priority');
        if ($priority_limit === false) {
            $priority_limit = 2; // Default
        }

        // Check if disabled
        if ($priority_limit == -1) {
            return;
        }

        // Check priority criteria
        $should_reply = false;
        if ($priority_limit == 0) {
            $should_reply = true; // All
        } else if ($priority_limit == 1 && $priority >= 1) {
            $should_reply = true; // Medium, High, Urgent
        } else if ($priority_limit == 2 && $priority >= 2) {
            $should_reply = true; // High, Urgent
        }

        if (!$should_reply) {
            return;
        }

        $mode = get_config('local_aurasupport', 'auto_reply_mode');
        if ($mode === false) {
            $mode = 1; // Default to KB Suggestion
        }

        $admin = get_admin();
        $user = $DB->get_record('user', ['id' => $userid]);
        $firstname = $user ? $user->firstname : 'User';

        if ($mode == 1) {
            // KB Suggestion Only
            $combinedText = $ticket_subject . ' ' . strip_tags($ticket_description);
            $suggested = self::suggest_kb($combinedText);
            
            if ($suggested) {
                $url = new \moodle_url('/local/aurasupport/kb.php', ['id' => $suggested->id]);
                $aimsg = "Hi {$firstname}, I am Aura AI. The issue you are experiencing seems to be related to this article: <br>";
                $aimsg .= "<strong><a href=\"" . $url->out(false) . "\" target=\"_blank\">" . format_string($suggested->title) . "</a></strong><br><br>";
                $aimsg .= "Does this guide help solve your problem?";
                
                \local_aurasupport\ticket::add_message($ticketid, $admin->id, ['text' => $aimsg]);
            }
        } else if ($mode == 2) {
            // Full AI Reply
            $reply_html = self::generate_reply($ticket_subject, strip_tags($ticket_description), '', $firstname, 'Aura AI', 'Support Team');
            if ($reply_html && strpos($reply_html, 'Error from AI Provider') === false) {
                \local_aurasupport\ticket::add_message($ticketid, $admin->id, ['text' => $reply_html]);
            }
        }
    }

    public static function suggest_kb($user_text) {
        global $DB, $CFG;
        require_once($CFG->libdir . '/filelib.php');

        if (!self::is_enabled()) {
            return null;
        }

        // Fetch all KB articles (only active ones ideally, but we'll assume all are for now)
        $kbs = $DB->get_records('local_aurasupport_kb', null, '', 'id, title');
        if (empty($kbs)) {
            return null;
        }

        $kb_list = [];
        foreach ($kbs as $kb) {
            $kb_list[] = "ID: {$kb->id} | Title: {$kb->title}";
        }
        $kb_str = implode("\n", $kb_list);

        $apikey = get_config('local_aurasupport', 'gemini_api_key');
        $model = get_config('local_aurasupport', 'gemini_model');
        if (empty($model)) {
            $model = 'gemini-3.5-flash';
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $apikey;

        $prompt = "You are an intelligent support routing AI.\n";
        $prompt .= "A user is writing a support ticket with the following issue description/subject:\n";
        $prompt .= "\"" . $user_text . "\"\n\n";
        $prompt .= "Here is a list of our Knowledge Base (KB) articles:\n";
        $prompt .= $kb_str . "\n\n";
        $prompt .= "Your task is to determine if any of these articles is highly relevant and likely to solve the user's issue.\n";
        $prompt .= "If you find a highly relevant article, return ONLY its ID (an integer).\n";
        $prompt .= "If no article is highly relevant, return ONLY the number 0.\n";
        $prompt .= "Output ONLY a single integer. No other text.";

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'maxOutputTokens' => 10
            ]
        ];

        $curl = new \curl();
        $curl->setHeader('Content-Type: application/json');
        
        $response = $curl->post($url, json_encode($data));
        
        if ($curl->get_info()['http_code'] === 200) {
            $result = json_decode($response);
            
            if (isset($result->usageMetadata)) {
                self::log_usage('suggest_kb', $result->usageMetadata->promptTokenCount ?? 0, $result->usageMetadata->candidatesTokenCount ?? 0);
            }

            if (isset($result->candidates[0]->content->parts[0]->text)) {
                $output = trim($result->candidates[0]->content->parts[0]->text);
                $id = (int) $output;
                if ($id > 0 && isset($kbs[$id])) {
                    return $kbs[$id];
                }
            }
        }
        return null;
    }
}
