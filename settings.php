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

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_aurasupport', get_string('pluginname', 'local_aurasupport'));
    
    $settings->add(new admin_setting_configcheckbox('local_aurasupport/enable_email',
        'Enable Email Notifications',
        'Send emails to users and agents when tickets are updated.',
        1));
        
    $settings->add(new admin_setting_heading('local_aurasupport/ai_heading', 'AI Settings', 'Configure artificial intelligence for ticket responses.'));
    
    $settings->add(new admin_setting_configcheckbox('local_aurasupport/enable_ai',
        'Enable AI Auto-Response',
        'Allow agents to generate ticket responses using Google Gemini AI.',
        0));
        
    $settings->add(new admin_setting_configpasswordunmask('local_aurasupport/gemini_api_key',
        'Gemini API Key',
        'API Key for Google Gemini. Required if AI is enabled.',
        ''));
        
    $settings->add(new admin_setting_configselect('local_aurasupport/gemini_model',
        'Gemini Model',
        'Select the model to use for AI responses.',
        'gemini-3.5-flash',
        [
            'gemini-3.5-flash' => 'Gemini 3.5 Flash (Most Intelligent Agentic)',
            'gemini-3.1-pro' => 'Gemini 3.1 Pro (Advanced Intelligence)',
            'gemini-3' => 'Gemini 3',
            'gemini-3-flash' => 'Gemini 3 Flash (Frontier Performance)',
            'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash-Lite (Fast & Cost Efficient)',
            'gemini-2.5-pro' => 'Gemini 2.5 Pro (Deep Reasoning)',
            'gemini-2.5-flash' => 'Gemini 2.5 Flash (Best Price-Performance)',
            'gemini-2.5-flash-lite' => 'Gemini 2.5 Flash-Lite (Budget-friendly)',
            'antigravity-agent-preview' => 'Antigravity Agent Preview (Managed Agent)'
        ]));
        
    $settings->add(new admin_setting_heading('local_aurasupport/ui_heading', 'UI Settings', 'Configure the appearance of the widget.'));
    
    $settings->add(new admin_setting_configtext('local_aurasupport/widget_footer_text',
        'Widget Footer Text',
        'Text displayed at the bottom of the support widget. Use {{version}} to automatically insert the plugin version.',
        'AuraSupport {{version}}', PARAM_TEXT));
        
    $ADMIN->add('localplugins', $settings);
}
