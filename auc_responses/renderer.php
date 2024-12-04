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
 * Defines the renderer for the auc_responses behaviour.
 *
 * @package    qbehaviour_auc_responses
 * @copyright  2024 CSchenk
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 
defined('MOODLE_INTERNAL') || die();
class qbehaviour_auc_responses_renderer extends qbehaviour_renderer {
    
    public function controls(question_attempt $qa, question_display_options $options) {

        # creates HTML for submitButton from general rendererbase-file:
        $submitButton = $this->submit_button($qa, $options);

        // Determine whether button should be enabled or not, based on the state of the question:
        $state = $qa->get_state();

        // Initialize variable "script" (needed later on)
        $script = '';

        // If the question state is "todo" (the question has not been answered correctly):
        // Ensure that previously given answers are disabled
        // Ensure that "Next" Button is disabled
        if ($state == question_state::$todo) {
            
            // First of all, have a look at all previously given answers using step iterator:
            $steps = iterator_to_array($qa->get_step_iterator());
            for($i = 0; $i < count($steps); $i++){
                $step = $steps[$i];
                $qt_data_for_step = $step-> get_qt_data();
                $previous_response = reset($qt_data_for_step);
      
                // Compare the previously given responses with the values on the radio buttons
                // For a radio button with a value that matches a previously given response:
                // Ensure that the radio button is unchecked and ensure that it is disabled
                $script .= html_writer::script("
                    document.addEventListener('DOMContentLoaded', function() {
                        var previousResponse = '" . addslashes($previous_response) . "';
                        var radioButton = document.querySelector('input[type=\"radio\"][value=\"' + previousResponse + '\"]');
                        if (radioButton) {
                            radioButton.checked = false; 
                            radioButton.disabled = true; 
                        }
                    });                
                ");
            }

            // Ensure that "Next"-Button is disabled:
            $script .= html_writer::script("
                document.addEventListener('DOMContentLoaded', function () {
                    var nextButton = document.getElementById('mod_quiz-next-nav');
                    if (nextButton) {
                        nextButton.setAttribute('disabled', 'disabled');
                    }
                });
            ");
            return $submitButton . $script;
        }

        // Otherwise: Simply return submit_button
        return $this->submit_button($qa, $options);
    }

    public function feedback(question_attempt $qa, question_display_options $options) {
        // Show the Try again button if we are in try-again state.
        if (!$qa->get_state()->is_active() ||
                ($options->readonly !== qbehaviour_auc_responses::TRY_AGAIN_VISIBLE &&
                        $options->readonly !== qbehaviour_auc_responses::TRY_AGAIN_VISIBLE_READONLY)) {
            return '';
        }

        $attributes = [
            'type' => 'submit',
            'id' => $qa->get_behaviour_field_name('tryagain'),
            'name' => $qa->get_behaviour_field_name('tryagain'),
            'value' => get_string('tryagain', 'qbehaviour_auc_responses'),
            'class' => 'submit btn btn-secondary',
            'data-savescrollposition' => 'true',
        ];
        if ($options->readonly === qbehaviour_auc_responses::TRY_AGAIN_VISIBLE_READONLY) {
            // This means the question really was rendered with read-only option.
            $attributes['disabled'] = 'disabled';
        }
        $output = html_writer::empty_tag('input', $attributes);
        if (empty($attributes['disabled'])) {
            $this->page->requires->js_call_amd('core_question/question_engine', 'initSubmitButton', [$attributes['id']]);
        }
        return $output;
    }
}