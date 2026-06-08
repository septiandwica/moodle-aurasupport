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

        $record = new \stdClass();
        $record->userid = $userid;
        $record->action = $action;
        $record->tokens_prompt = $prompt_tokens;
        $record->tokens_completion = $completion_tokens;
        $record->timecreated = time();
        $DB->insert_record('local_aurasupport_ai_logs', $record);
    }

    public static function generate_reply($ticket_subject, $ticket_description, $history = '', $submitter_name = 'User', $agent_name = 'Agent', $dept_name = '', $kb_context = '') {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        $apikey = get_config('local_aurasupport', 'gemini_api_key');
        if (empty($apikey)) {
            return "Error: Gemini API key is not configured.";
        }

        $model = get_config('local_aurasupport', 'gemini_model');
        if (empty($model)) {
            $model = 'gemini-2.5-flash';
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';

        $prompt = "You are 'Aura AI', a highly professional, empathetic, and solution-oriented IT Support agent for PRESOLA (President Online Learning Academy). Your task is to draft a reply for a user's (student/teacher/staff) support ticket.\n\n";

        if (!empty($kb_context)) {
            $prompt .= "--- KNOWLEDGE BASE CONTEXT ---\n";
            $prompt .= "You MUST base your technical solutions STRICTLY on the following Knowledge Base articles. Do NOT invent features that don't exist in the context.\n";
            $prompt .= $kb_context . "\n";
            $prompt .= "------------------------------\n\n";
        }

        $prompt .= "Ticket Subject: " . $ticket_subject . "\n";
        $prompt .= "Ticket Description: " . $ticket_description . "\n";
        if (!empty($history)) {
            $prompt .= "\nPrevious Conversation History:\n" . $history . "\n";
        }
        
        $dept_str = !empty($dept_name) ? " - " . $dept_name : "";

        $prompt .= "\nInstructions for AI:\n";
        $prompt .= "1. ALWAYS start with the exact greeting: 'Hi {$submitter_name},'.\n";
        $prompt .= "2. ONLY answer questions that are related to Moodle, PRESOLA, e-Learning, or IT Support.\n";
        $prompt .= "3. If the user asks something completely unrelated (like math 1+1, general trivia, history) or if the issue requires manual human intervention not covered in the Knowledge Base, DO NOT attempt to answer it. Instead, politely reply: 'I could not find an exact solution for your request in my knowledge base. I have forwarded this ticket to the administration team, and a human agent will contact you shortly to assist further.'\n";
        $prompt .= "4. Show empathy and apologize for any inconvenience if the user is reporting an error or issue.\n";
        $prompt .= "5. End the reply EXACTLY with this format:\n";
        $prompt .= "   Best regards,\n";
        $prompt .= "   {$agent_name}{$dept_str}\n";
        $prompt .= "6. DO NOT reply with introductory meta-text like 'Sure, here is the draft'. Output ONLY the body of the reply itself.\n";
        $prompt .= "7. Format the response using clean, pure HTML elements (use <p>, <ul>, <li>, <strong>, <br>).\n";

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
        $curl->setHeader('x-goog-api-key: ' . $apikey);

        $response = $curl->post($url, json_encode($data));

        if ($curl->get_info()['http_code'] !== 200) {
            return "<p><em>Error from AI Provider.</em></p>";
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
            $kbs = $DB->get_records('local_aurasupport_kb');
            $kb_context = "";
            if (!empty($kbs)) {
                foreach ($kbs as $kb) {
                    $kb_context .= "Title: {$kb->title}\nContent: " . strip_tags($kb->content) . "\n\n";
                }
            }

            $reply_html = self::generate_reply($ticket_subject, strip_tags($ticket_description), '', $firstname, 'Aura AI', 'Support Team', $kb_context);
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
            $model = 'gemini-2.5-flash';
        }

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent';

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
        $curl->setHeader('x-goog-api-key: ' . $apikey);

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
