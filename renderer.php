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
        $intryagainstate =
        $options->readonly === qbehaviour_auc_responses::TRY_AGAIN_VISIBLE ||
        $options->readonly === qbehaviour_auc_responses::TRY_AGAIN_VISIBLE_READONLY;

        // In this state Moodle should show the submitted wrong answer,
        // its feedback, and the Try again button. Do not uncheck/disable
        // the submitted answer yet.
        if ($intryagainstate) {
            return '';
        }

        // Create HTML for submit button from base renderer.
        $submitbutton = $this->submit_button($qa, $options);

        $state = $qa->get_state();

        // Only after the user has clicked Try again should we disable
        // previously attempted responses.
        if ($state == question_state::$todo) {
            $previousresponses = [];

            $steps = iterator_to_array($qa->get_step_iterator());
            foreach ($steps as $step) {
                $qtdataforstep = $step->get_qt_data();
                $previousresponse = reset($qtdataforstep);

                if ($previousresponse !== false && $previousresponse !== null && $previousresponse !== '') {
                    $previousresponses[] = $previousresponse;
                }
            }

            $disablenext = true;
            $this->page->requires->js_call_amd(
                'qbehaviour_auc_responses/attempt_controls',
                'init',
                [$previousresponses, $disablenext]
            );
        }

        return $submitbutton;
    }

    public function feedback(question_attempt $qa, question_display_options $options) {
        // Show the Try again button if we are in try-again state.
        if (
            !$qa->get_state()->is_active() ||
                ($options->readonly !== qbehaviour_auc_responses::TRY_AGAIN_VISIBLE &&
                        $options->readonly !== qbehaviour_auc_responses::TRY_AGAIN_VISIBLE_READONLY)
        ) {
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
