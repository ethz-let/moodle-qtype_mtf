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
 * qtype_mtf question definition class.
 *
 * @package     qtype_mtf
 * @author      Amr Hourani (amr.hourani@id.ethz.ch)
 * @author      Martin Hanusch (martin.hanusch@let.ethz.ch)
 * @copyright   2016 ETHZ {@link http://ethz.ch/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Represents a qtype_mtf question.
 *
 * @copyright   2016 ETHZ {@link http://ethz.ch/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_mtf_question extends question_graded_automatically_with_countback {

    /** @var array rows */
    public $rows;
    /** @var array columns */
    public $columns;
    /** @var array weights */
    public $weights;
    /** @var string scoringmethod */
    public $scoringmethod;
    /** @var float deduction */
    public $deduction;
    /** @var bool shuffleanswers */
    public $shuffleanswers;
    /** @var int numberofrows */
    public $numberofrows;
    /** @var int numberofcolumns */
    public $numberofcolumns;
    /** @var array order */
    public $order = null;
    /** @var bool editedquestion */
    public $editedquestion;
    /** @var string answernumbering */
    public $answernumbering;

    /**
     * (non-PHPdoc).
     * @see question_definition::start_attempt()
     * @param question_attempt_step $step
     * @param int $variant
     */
    public function start_attempt(question_attempt_step $step, $variant) {
        $this->order = array_keys($this->rows);
        if ($this->shuffleanswers) {
            shuffle($this->order);
        }
        $step->set_qt_var('_order', implode(',', $this->order));
    }
    public function validate_can_regrade_with_other_version(question_definition $otherversion): ?string {
        $basemessage = parent::validate_can_regrade_with_other_version($otherversion);
        if ($basemessage) {
            return $basemessage;
        }
        if (count($this->rows) != count($otherversion->rows)) {
            return get_string('numberchoicehaschanged', 'qtype_mtf');
        }
        return null;
    }

    public function update_attempt_state_data_for_new_version(
                    question_attempt_step $oldstep, question_definition $otherversion) {

        $startdata = parent::update_attempt_state_data_for_new_version($oldstep, $otherversion);

        $mapping = array_combine(array_keys($otherversion->rows), array_keys($this->rows));

        $oldorder = explode(',', $oldstep->get_qt_var('_order'));
        $neworder = [];
        foreach ($oldorder as $oldid) {
            $neworder[] = $mapping[$oldid] ?? $oldid;
        }
        $startdata['_order'] = implode(',', $neworder);
        return $startdata;
    }
    /**
     * (non-PHPdoc).
     * @see question_definition::apply_attempt_state()
     * @param question_attempt_step $step
     */
    public function apply_attempt_state(question_attempt_step $step) {
        $this->order = explode(',', $step->get_qt_var('_order'));
        parent::apply_attempt_state($step);
    }

    /**
     * get the question order
     * @param question_attempt $qa
     * @return array
     */
    public function get_order(question_attempt $qa) {
        $this->init_order($qa);

        return $this->order;
    }

    /**
     * Initialises the order (if it is not set yet) by decoding the question attempt variable '_order'.
     * @param question_attempt $qa
     */
    protected function init_order(question_attempt $qa) {
        if (is_null($this->order)) {
            $this->order = explode(',', $qa->get_step(0)->get_qt_var('_order'));
        }
    }

    /**
     * Returns the name field name for input cells in the questiondisplay.
     * The column parameter is ignored for now since we don't use multiple answers.
     * @param int $key
     * @return string
     */
    public function field($key) {
        return 'option' . $key;
    }

    /**
     * Checks whether an row is answered by a given response.
     * @param array $response
     * @param int $rownumber
     * @return bool
     */
    public function is_answered($response, $rownumber) {
        $field = $this->field($rownumber);
        return isset($response[$field]) && !empty($response[$field]);
    }

    /**
     * Checks whether a given column (response) is the correct answer for a given row (option).
     * @param int $row The row number.
     * @param int $col The column number
     * @return bool
     */
    public function is_correct($row, $col) {
        $weight = $this->weight($row, $col);

        if ($weight > 0.0) {
            return 1;
        } else {
            return 0;
        }
    }

    /**
     * Returns the weight for the given row and column.
     * @param mixed $row A row object or a row number.
     * @param mixed $col A column object or a column number.
     * @return float
     */
    public function weight($row = null, $col = null) {
        $rownumber = is_object($row) ? $row->number : $row;
        $colnumber = is_object($col) ? $col->number : $col;
        if (isset($this->weights[$rownumber][$colnumber])) {
            $weight = (float) $this->weights[$rownumber][$colnumber]->weight;
        } else {
            $weight = 0;
        }

        return $weight;
    }

    /**
     * Checks wether a specific row is selected within the responses
     * @param array $response
     * @param int $rownumber
     * @return bool
     */
    public function is_row_selected($response, $rownumber) {
        return isset($response[$this->field($rownumber)]);
    }

    /**
     * Returns the last response in a question attempt.
     * @param question_attempt $qa
     * @return array|mixed
     */
    public function get_response(question_attempt $qa) {
        return $qa->get_last_qt_data();
    }

    /**
     * Used by many of the behaviours, to work out whether the student's
     * response to the question is complete.
     * That is, whether the question attempt
     * should move to the COMPLETE or INCOMPLETE state.
     * @param array $response responses, as returned by
     *        {@see question_attempt_step::get_qt_data()}.
     * @return bool whether this response is a complete answer to this question.
     */
    public function is_complete_response(array $response) {
        if ($this->get_num_selected_choices($response) >= count($this->rows)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Use by many of the behaviours to determine whether the student
     * has provided enough of an answer for the question to be graded automatically,
     * or whether it must be considered aborted.
     * @param array $response responses, as returned by
     *      {@see question_attempt_step::get_qt_data()}.
     * @return bool whether this response can be graded.
     */
    public function is_gradable_response(array $response) {
        if ($this->scoringmethod == 'subpoints' || $this->scoringmethod == 'subpointdeduction') {
            if ($this->get_num_selected_choices($response) > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            return $this->is_complete_response($response);
        }
    }

    /**
     * In situations where is_gradable_response() returns false, this method
     * should generate a description of what the problem is.
     * @param array $response
     * @return string the message.
     */
    public function get_validation_error(array $response) {
        if ($this->is_complete_response($response)) {
            return '';
        }
        return get_string('oneanswerperrow', 'qtype_mtf');
    }

    /**
     * Get the number of selected options
     * @param array $response responses, as returned by
     *        {@see question_attempt_step::get_qt_data()}.
     * @return int the number of choices that were selected. in this response.
     */
    public function get_num_selected_choices(array $response) {
        $numselected = 0;
        foreach ($response as $key => $value) {
            if (!empty($value) && $key[0] != '_') {
                $numselected += 1;
            }
        }
        return $numselected;
    }

    /**
     * Produce a plain text summary of a response.
     * @param array $response
     * @return string
     */
    public function summarise_response(array $response) {
        $result = array();

        foreach ($this->order as $key => $rowid) {
            $field = $this->field($key);
            $row = $this->rows[$rowid];

            if (isset($response[$field])) {
                foreach ($this->columns as $column) {
                    if ($column->number == $response[$field]) {
                        $result[] = $this->html_to_text($row->optiontext, $row->optiontextformat) .
                                 ': ' . $this->html_to_text($column->responsetext,
                                        $column->responsetextformat);
                    }
                }
            }
        }
        return implode('; ', $result);
    }

    /**
     * Categorise the student's response according to the categories defined by get_possible_responses.
     * @param array $response a response, as might be passed to  grade_response().
     * @return array subpartid => question_classified_response objects.
     *      returns an empty array if no analysis is possible.
     */
    public function classify_response(array $response) {
        // See which column numbers have been selected.
        $selectedcolumns = array();
        $weights = $this->weights;
        foreach ($this->order as $key => $rowid) {
            $field = $this->field($key);
            $row = $this->rows[$rowid];

            if (property_exists((object) $response, $field) && $response[$field]) {
                $selectedcolumns[$rowid] = $response[$field];
            } else {
                $selectedcolumns[$rowid] = 0;
            }
        }

        $parts = array();
        // Now calculate the classification for MTF.
        foreach ($this->rows as $rowid => $row) {
            $field = $this->field($key);
            if (empty($selectedcolumns[$rowid])) {
                $parts[$rowid] = question_classified_response::no_response();
                continue;
            }
            // Find the chosen column by columnnumber.
            $column = null;
            foreach ($this->columns as $colid => $col) {
                if ($col->number == $selectedcolumns[$rowid]) {
                    $column = $col;
                    break;
                }
            }
            if (empty($column)) {
                $parts[$rowid] = question_classified_response::no_response();
                continue;
            }
            // Calculate the partial credit.
            if ($this->scoringmethod == 'subpoints') {
                $partialcredit = 0.0;
            } else {
                $partialcredit = -0.999; // Due to non-linear math.
            }
            if ($this->scoringmethod == 'subpoints' &&
                     $this->weights[$row->number][$column->number]->weight > 0) {
                $partialcredit = 1 / count($this->rows);
            }
            $parts[$rowid] = new question_classified_response($column->id, $column->responsetext,
                    $partialcredit);
        }

        return $parts;
    }

    /**
     * Use by many of the behaviours to determine whether the student's
     * response has changed.
     * This is normally used to determine that a new set
     * of responses can safely be discarded.
     * @param array $prevresponse the responses previously recorded for this question,
     *        as returned by {@see question_attempt_step::get_qt_data()}
     * @param array $newresponse the new responses, in the same format.
     * @return bool whether the two sets of responses are the same - that is
     *         whether the new set of responses can safely be discarded.
     */
    public function is_same_response(array $prevresponse, array $newresponse) {
        if (count($prevresponse) != count($newresponse)) {
            return false;
        }
        foreach ($prevresponse as $field => $previousvalue) {
            if (!isset($newresponse[$field])) {
                return false;
            }
            $newvalue = $newresponse[$field];
            if ($newvalue != $previousvalue) {
                return false;
            }
        }

        return true;
    }

    /**
     * What data would need to be submitted to get this question correct.
     * If there is more than one correct answer, this method should just
     * return one possibility
     * @param bool $rowidindex
     * @return array
     */
    public function get_correct_response($rowidindex = false) {
        $result = array();
        foreach ($this->order as $key => $rowid) {
            $row = $this->rows[$rowid];
            $field = $this->field($key);

            foreach ($this->columns as $column) {
                $weight = $this->weight($row, $column);
                if ($weight > 0) {
                    if ($rowidindex) {
                        $result[$rowid] = $column->id;
                    } else {
                        $result[$field] = $column->number;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Returns an instance of the grading class according to the scoringmethod of the question.
     * @return string The grading object.
     */
    public function grading() {
        global $CFG;
        $type = $this->scoringmethod;
        $gradingclass = 'qtype_mtf_grading_' . $type;
        require_once($CFG->dirroot . '/question/type/mtf/grading/' . $gradingclass . '.class.php');

        return new $gradingclass();
    }

    /**
     * Grade a response to the question, returning a fraction between
     * get_min_fraction() and 1.0, and the corresponding {@see question_state}
     * right, partial or wrong.
     * @param array $response responses, as returned by
     *        {@see question_attempt_step::get_qt_data()}.
     * @return array (number, integer) the fraction, and the state.
     */
    public function grade_response(array $response) {
        $grade = $this->grading()->grade_question($this, $response);
        $state = question_state::graded_state_for_fraction($grade);

        return array($grade, $state
        );
    }

    /**
     * What data may be included in the form submission when a student submits
     * this question in its current state?
     * This information is used in calls to optional_param. The parameter name
     * has {@see question_attempt::get_field_prefix()} automatically prepended.
     * @return array|string variable name => PARAM_... constant, or, as a special case
     *         that should only be used in unavoidable, the constant question_attempt::USE_RAW_DATA
     *         meaning take all the raw submitted data belonging to this question.
     */
    public function get_expected_data() {
        $result = array();
        foreach ($this->order as $key => $notused) {
            $field = $this->field($key);
            $result[$field] = PARAM_INT;
        }

        return $result;
    }

    /**
     * Returns an array where keys are the cell names and the values
     * are the weights.
     * @return array
     */
    public function cells() {
        $result = array();
        foreach ($this->order as $key => $rowid) {
            $row = $this->rows[$rowid];
            $field = $this->field($key);
            foreach ($this->columns as $column) {
                $result[$field] = $this->weight($row->number, $column->number);
            }
        }

        return $result;
    }

    /**
     * Makes HTML text (e.g.
     * option or feedback texts) suitable for inline presentation in renderer.php.
     * @param string $html
     * @return string
     */
    public function make_html_inline($html) {
        $html = preg_replace('~\s*<p>\s*~u', '', $html);
        $html = preg_replace('~\s*</p>\s*~u', '<br />', $html);
        $html = preg_replace('~(<br\s*/?>)+$~u', '', $html);

        return trim($html);
    }

    /**
     * Convert some part of the question text to plain text.
     * This might be used, for example, by get_response_summary().
     * @param string $text The HTML to reduce to plain text.
     * @param int $format the FORMAT_... constant.
     * @return string the equivalent plain text.
     */
    public function html_to_text($text, $format) {
        return question_utils::to_plain_text($text, $format);
    }

    /**
     * Computes the final grade when "Multiple Attempts" or "Hints" are enabled
     * @param array $responses Contains the user responses. 1st dimension = attempt, 2nd dimension = answers
     * @param int $totaltries Not needed
     */
    public function compute_final_grade($responses, $totaltries) {
        $lastresponse = count($responses) - 1;
        $numpoints = isset($responses[$lastresponse]) ? $this->grading()->grade_question($this, $responses[$lastresponse]) : 0;
        return max(0, $numpoints - max(0, $lastresponse) * $this->penalty);
    }

    /**
     * Disable those hint settings that we don't want when the student has selected
     * more choices than the number of right choices.
     * This avoids giving the game away.
     * @param question_hint_with_parts $hint a hint.
     */
    protected function disable_hint_settings_when_too_many_selected(question_hint_with_parts $hint) {
        $hint->clearwrong = false;
    }

    /**
     * Get one of the question hints. The question_attempt is passed in case
     * the question type wants to do something complex. For example, the
     * multiple choice with multiple responses question type will turn off most
     * of the hint options if the student has selected too many opitions.
     * @param int $hintnumber Which hint to display. Indexed starting from 0
     * @param question_attempt $qa The question_attempt.
     */
    public function get_hint($hintnumber, question_attempt $qa) {

        $hint = parent::get_hint($hintnumber, $qa);
        if (is_null($hint)) {
            return $hint;
        }

        return $hint;
    }

    /**
     * Checks whether the users is allow to be served a particular file.
     * @param object $qa
     * @param object $options the options that control display of the question.
     * @param string $component the name of the component we are serving files for.
     * @param string $filearea the name of the file area.
     * @param array $args the remaining bits of the file path.
     * @param bool $forcedownload whether the user must be forced to download the file.
     * @return bool true if the user can access this file.
     */
    public function check_file_access($qa, $options, $component, $filearea, $args, $forcedownload) {

        if ($component == 'qtype_mtf' && $filearea == 'optiontext') {
            return true;
        } else if ($component == 'qtype_mtf' && $filearea == 'feedbacktext') {
            return true;
        } else if ($component == 'question'
            && in_array($filearea, array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback'))) {
            if ($this->editedquestion == 1) {
                return true;
            } else {
                return $this->check_combined_feedback_file_access($qa, $options, $filearea);
            }
        } else if ($component == 'question' && $filearea == 'hint') {
            return $this->check_hint_file_access($qa, $options, $args);
        } else {
            return parent::check_file_access($qa, $options, $component, $filearea, $args, $forcedownload);
        }
    }
}
