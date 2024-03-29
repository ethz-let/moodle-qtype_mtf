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
 * Migration script for migration to multichoice
 *
 * @package     qtype_mtf
 * @author      Amr Hourani (amr.hourani@id.ethz.ch)
 * @author      Martin Hanusch (martin.hanusch@let.ethz.ch)
 * @copyright   2016 ETHZ {@link http://ethz.ch/}
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
// phpcs:disable
require_once(dirname(__FILE__) . '/../../../../config.php');
require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->libdir . '/questionlib.php');

// Getting parameters.
$courseid = optional_param('courseid', 0, PARAM_INT);
$categoryid = optional_param('categoryid', 0, PARAM_INT);
$all = optional_param('all', 0, PARAM_INT);
$dryrun = optional_param('dryrun', 1, PARAM_INT);
$autoweights = optional_param('autoweights', 0, PARAM_INT);
$includesubcategories = optional_param('includesubcategories', 0, PARAM_INT);

@set_time_limit(0);
@ini_set('memory_limit', '3072M');

// General Page Setup.
echo '<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" />' .
    '<style>body{font-family: "Courier New", Courier, monospace; font-size: 12px; background: #ebebeb; color: #5a5a5a;}</style>' .
    '</head>';
echo "=========================================================================================<br/>\n";
echo "M I G R A T I O N :: MTF to Multichoice<br/>\n";
echo "=========================================================================================<br/>\n";

// Checking for permissions.
require_login();
if (!is_siteadmin()) {
    echo "<br/>[<font color='red'>ERR</font>] You are not a Website Administrator";
    die();
}

$starttime = microtime(1);
$fs = get_file_storage();

$sql = "SELECT q.*
        FROM {question} q
        WHERE q.qtype = 'mtf'
          AND q.parent = 0
          AND q.id in (select questionid from {qtype_mtf_options})
        ";
$params = array();

// Showing information when either no or too many parameters are selected.
$numparameters = ($all == 0 ? 0 : 1) + ($courseid == 0 ? 0 : 1) + ($categoryid == 0 ? 0 : 1);
if (($all != 1 && $courseid <= 0 && $categoryid <= 0) || $numparameters > 1 ) {
    echo "
    <br/>\nParameters:<br/><br/>\n\n
    =========================================================================================<br/>\n
    You must specify certain parameters for this script to work: <br/><br/>\n\n
    Step 1: <b>NECESSARY </b> - Use ONE of the following three parameters-value pairs:
    <ul>
        <li><b>courseid</b> (values: <i>a valid course ID</i>)</li>
        <li><b>categoryid</b> (values: <i>a valid category ID</i>)</b></li>
        <li><b>all</b> (values: 1)</li>
    </ul>
    This parameter-value pairs define which MTF questions will be migrated.<br/><br/>\n\n
    Step 2: <b>IMPORTANT AND STRONGLY RECOMMENDED:</b><br/>\n
    <ul>
        <li><b>dryrun</b> (values: <i>0,1</i>)</li>
        <li><b>autoweights</b> (values: <i>0,1</i>)</li>
        <li><b>includesubcategories</b> (values: <i>0,1</i>)</li>
    </ul>
    The Dryrun Option is enabled (1) by default.<br/>\n
    With Dryrun enabled no changes will be made to the database.<br/>\n
    Use Dryrun to receive information about possible issues before migrating.<br/><br/>\n\n
    The Autoweights Options is disabled (0) by default.<br/>\n
    While migrating from MTF to Multichoice, grades for correct or incorrect answers are <br/>\n
    usually set equal. However in some cases the SUM of all grades does not match 100%.<br/>\n
    With Autoweights enabled different grades will be set to match a SUM of 100%.<br/>\n
    With Autoweights disabled the affected question will be ignored in migration.<br/><br/>\n\n
    The IncludeSubcategories Option also is disabled (0) by default.<br/>\n
    With includesubcategories enabled all subcategories will be included in the migration<br/>\n
    process, if the user chooses to migrate questions by selecting a certain category.<br/><br/>\n\n
    =========================================================================================<br/><br/>\n\n
    Examples:<br/><br/>\n\n
    =========================================================================================<br/>\n
	<ul>
        <li><strong>Migrate MTF Questions in a specific course</strong>:<br/>\n
        MOODLE_URL/question/type/mtf/bin/mig_mtf_to_multichoice.php?<b>courseid=55</b>
        <li><strong>Migrate MTF Questions in a specific category</strong>:<br/>\n
        MOODLE_URL/question/type/mtf/bin/mig_mtf_to_multichoice.php?<b>categoryid=1</b>
        <li><strong>Migrate all MTF Questions</strong>:<br/>\n
        MOODLE_URL/question/type/mtf/bin/mig_mtf_to_multichoice.php?<b>all=1</b>
        <li><strong>Disable Dryrun</strong>:<br/>\n
        MOODLE_URL/question/type/mtf/bin/mig_mtf_to_multichoice.php?all=1<b>&dryrun=0</b>
        <li><strong>Enable Autoweights</strong>:<br/>\n
        MOODLE_URL/question/type/mtf/bin/mig_mtf_to_multichoice.php?all=1<b>&autoweights=1</b>
        <li><strong>Enable IncludeSubcategories</strong>:<br/>\n
        MOODLE_URL/question/type/mtf/bin/mig_mtf_to_multichoice.php?all=1&dryrun=0<b>&includesubcategories=1</b>
	</ul>
    <br/>\n";
    die();
}

// Parameter Information.
echo "-----------------------------------------------------------------------------------------<br/><br/>\n\n";
echo ($dryrun == 1 ? "[<font style='color:#228d00;'>ON </font>] " : "[<font color='red'>OFF</font>] ") .
    "Dryrun: " . ($dryrun == 1 ? "NO changes to the database will be made!" : "Migration is being processed") . "<br/>\n";
echo ($autoweights == 1 ? "[<font style='color:#228d00;'>ON </font>] " : "[<font color='red'>OFF</font>] ") .
    "Autoweights<br/><br/>\n\n";
echo ($includesubcategories == 1 ? "[<font style='color:#228d00;'>ON </font>] " : "[<font color='red'>OFF</font>] ") .
    "IncludeSubcategories<br/><br/>\n\n";
echo "-----------------------------------------------------------------------------------------<br/>\n";
echo "=========================================================================================<br/>\n";

// Get the categories : Case 1.
if ($all == 1) {
    if ($categories = $DB->get_records('question_categories', array())) {
        echo "Migration of all MTF Questions<br/>\n";
    } else {
        echo "<br/>[<font color='red'>ERR</font>] Could not get categories<br/>\n";
        die();
    }
}
// Get the categories : Case 2.
if ($courseid > 0) {
    if (!$course = $DB->get_record('course', array('id' => $courseid))) {
        echo "<br/>[<font color='red'>ERR</font>] Course with ID " . $courseid . " not found<br/>\n";
        die();
    }

    $coursecontext = context_course::instance($courseid);

    $categories = $DB->get_records('question_categories',
            array('contextid' => $coursecontext->id
            ));
    $catids = array_keys($categories);
    if (!empty($catids)) {
        echo "Migration of MTF Questions within courseid " . $courseid . " <br/>\n";
        list($csql, $params) = $DB->get_in_or_equal($catids);
        $version = 'AND (qv.version = (SELECT MAX(v.version)
                                         FROM {question_versions} v
                                         JOIN {question_bank_entries} be
                                           ON be.id = v.questionbankentryid
                                        WHERE be.id = qbe.id) OR qv.version is null)';
        $sql = "SELECT q.*, qv.status, qc.id AS category
                                         FROM {question} q
                                         JOIN {question_versions} qv ON qv.questionid = q.id
                                         JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                                         JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                                        WHERE qc.id $csql $version
                                        and q.qtype = 'mtf' and q.parent = 0
                                     ORDER BY qc.id, q.qtype, q.name";
    } else {
        echo "<br/>[<font color='red'>ERR</font>] No question categories for course found.<br/>\n";
        die();
    }
}

// Get the categories : Case 3.
if ($categoryid > 0) {
    if ($categories[$categoryid] = $DB->get_record('question_categories', array('id' => $categoryid))) {

        $catids = [];

        if ($includesubcategories == 1) {
            $subcategories = get_subcategories($categoryid);
            $catids = array_column($subcategories, 'id');
            $catnames = array_column($subcategories, 'name');
        }

        array_push($catids, $categoryid);

        echo 'Migration of MTF questions within category "' . $categories[$categoryid]->name . "\"<br/>\n";

        if ($includesubcategories == 1) {
            echo "Also migrating subcategories:<br>\n";
            echo implode(",<br>", $catnames) . "<br>\n";
            echo "=========================================================================================<br/>\n";
        }

        list($csql, $params) = $DB->get_in_or_equal($catids);
        $version = 'AND (qv.version = (SELECT MAX(v.version)
                                         FROM {question_versions} v
                                         JOIN {question_bank_entries} be
                                           ON be.id = v.questionbankentryid
                                        WHERE be.id = qbe.id) OR qv.version is null)';
        $sql = "SELECT q.*, qv.status, qc.id AS category
                                         FROM {question} q
                                         JOIN {question_versions} qv ON qv.questionid = q.id
                                         JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                                         JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
                                        WHERE qc.id $csql $version
                                        and q.qtype = 'mtf' and q.parent = 0
                                     ORDER BY qc.id, q.qtype, q.name";
    } else {
        echo "<br/>[<font color='red'>ERR</font>] Question category with ID " . $categoryid . " not found<br/>\n";
        die();
    }
}

// Get the questions based on the previous set parameters.
$questions = $DB->get_records_sql($sql, $params);
echo 'Questions found: ' . count($questions) . "<br/>\n";
echo "=========================================================================================<br/><br/>\n\n";
if (count($questions) == 0) {
    echo "<br/>[<font color='red'>ERR</font>] No questions found<br/>\n";
    die();
}

// Processing the single questions.
echo "Migrating questions...<br/>\n";
$nummigrated = 0;
$questionsnotmigrated = [];

foreach ($questions as $question) {
    set_time_limit(600);
    $question->oldid = $question->id;
    $question->oldname = $question->name;

    // Getting related question data.
    $mtfcolumns = $DB->get_records('qtype_mtf_columns', array('questionid' => $question->id), ' id ASC ');
    $mtfoptions = $DB->get_record('qtype_mtf_options', array('questionid' => $question->id));
    $mtfrows = $DB->get_records('qtype_mtf_rows', array('questionid' => $question->id), ' id ASC ');
    $mtfweights = $DB->get_records('qtype_mtf_weights', array('questionid' => $question->id), ' id ASC ');
    $questionhints = $DB->get_records('question_hints', array('questionid' => $question->id), ' id ASC ');

    // Checking for possible errors before doing anyting.
    // Getting question fractions in case of a complete record.

    if ((!isset($mtfcolumns) || count($mtfcolumns) == 0)
    || (!isset($mtfoptions) || $mtfoptions == false)
    || (!isset($mtfrows) || count($mtfrows) == 0)
    || (!isset($mtfweights) || count($mtfweights) == 0)
    || !isset($questionhints)) {
        $questionweights = array("error" => true, "message" => "Database records incomplete.", "notices" => []);
    } else {
        $questionweights = get_weights($mtfweights, $autoweights, $mtfcolumns);
    }

    // If weights are not mapable, skip the whole question.
    // and continue; with the next iteration.
    if ($questionweights["error"]) {
        echo '[<font style="color:#ff0909;">ERR</font>] - question <i>"' . $question->oldname .
            '"</i> (ID: <a href="' . $CFG->wwwroot . '/question/bank/previewquestion/preview.php?id=' . $question->oldid .
            '" target="_blank">' . $question->oldid . '</a>) is not migratable: ' . $questionweights["message"];
        echo count($questionweights["notices"]) > 0 ? " ::: <b>Notices:</b> " . implode(" | ", $questionweights["notices"]) : null;
        echo "<br/>\n";
        array_push($questionsnotmigrated, array("id" => $question->oldid, "name" => $question->oldname));
        continue;
    }

    // Get contextid from question category.
    $bankentry = $DB->get_record_sql("
    SELECT DISTINCT qbe.id, qbe.questioncategoryid
                              FROM {question_versions} qv
                              JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
                             WHERE qv.questionid = ?", ['id' => $question->id]);
    if($bankentry){
      $contextid = $DB->get_field('question_categories', 'contextid', array('id' => $bankentry->questioncategoryid));
    }
    if (!isset($contextid) || $contextid == false) {
        echo "<br/>[<font color='red'>ERR</font>] No context id found for this question.";
        continue;
    }

    // Pretesting files.

    $success = 1;
    $status = "";

    foreach ($mtfrows as $key => $row) {

        // Test images in the optiontext to the new answer.
        $testresult = test_files(
            $fs,
            $contextid,
            $mtfrows[$key]->id,
            trim($mtfrows[$key]->optiontext),
            "optiontext",
            "qtype_mtf");

        $success = $success && $testresult[0];
        $status .= $testresult[1];

        // Test images in the answer feedback.
        $testresult = test_files(
            $fs,
            $contextid,
            $mtfrows[$key]->id,
            $mtfrows[$key]->optionfeedback,
            "feedbacktext",
            "qtype_mtf");

        $success = $success && $testresult[0];
        $status .= $testresult[1];
    }

    // Copy images in the questiontext to new itemid.
    $testresult = test_files(
        $fs,
        $contextid,
        $question->oldid,
        $question->questiontext,
        "questiontext",
        "question");

    $success = $success && $testresult[0];
    $status .= $testresult[1];

    // Copy images in the general feedback to new itemid.
    $testresult = test_files(
        $fs,
        $contextid,
        $question->oldid,
        $question->generalfeedback,
        "generalfeedback",
        "question");

    $success = $success && $testresult[0];
    $status .= $testresult[1];


    if (!$questionweights["error"] && $success) {
        $nummigrated++;
    }

    // If Dryrun is disabled, changes to the database are made from this point on.
    if ($dryrun == 0 && $success) {
        try {

            unset($transaction);
            $transaction = $DB->start_delegated_transaction();
            // Only create a new bank entry if the question is not a new version (New question or duplicating a question).
            require_once($CFG->dirroot . '/question/engine/lib.php');
            require_once($CFG->libdir . '/questionlib.php');
            $questionbankentry = null;
            $qbankentry = null;
            $qbankentry = get_question_bank_entry($question->id);

            // Get the question status.
            $getqversionstatus = $DB->get_record('question_versions', ['questionid' => $question->id, 'questionbankentryid' => $qbankentry->id]);
            $oldqstatus = $getqversionstatus->status;
            $originalversionnumber = 'V. '.$getqversionstatus->version;

            // Duplicating  mdl_question -> mdl_question.
            unset($question->id);

            $question->parent = 0;
            $question->name = substr($question->name . " [$originalversionnumber] (MC " . date("Y-m-d H:i:s") . ")", 0, 255);
            $question->qtype = "multichoice";
            $question->stamp = make_unique_id_code();
            $question->version = make_unique_id_code();
            $question->timecreated = time();
            $question->timemodified = time();
            $question->modifiedby = $USER->id;
            $question->createdby = $USER->id;
            $question->idnumber = null;

            // Create the question.
            $question->id = $DB->insert_record('question', $question);

            // Create a record for question_bank_entries, question_versions and question_references.
            $questionbankentry = new \stdClass();
            $questionbankentry->questioncategoryid = $qbankentry->questioncategoryid;
            $questionbankentry->idnumber = null;
            $questionbankentry->ownerid = $USER->id;
            $questionbankentry->id = $DB->insert_record('question_bank_entries', $questionbankentry);

            // Create question_versions records.
            $questionversion = new \stdClass();
            $questionversion->questionbankentryid = $questionbankentry->id;
            $questionversion->questionid = $question->id;
            // Get the version and status from the parent question if parent is set.
            // Get the status field. It comes from the form, but for testing we can.
            if($oldqstatus) {
              $status = $oldqstatus;
            } else {
              $status = \core_question\local\bank\question_version_status::QUESTION_STATUS_READY;
            }
            $questionversion->version = get_next_version($questionbankentry->id);
            $questionversion->status = $status;

            $questionversion->id = $DB->insert_record('question_versions', $questionversion);

            // Tansferring  md_qtype_mtf_rows + mdl_qtype_mtf_weights -> mdl_question_answers.
            foreach ($mtfrows as $key => $row) {
                $entry = new stdClass();
                $entry->question = $question->id;
                $entry->answer = trim($mtfrows[$key]->optiontext);
                $entry->answerformat = $mtfrows[$key]->optiontextformat;
                $entry->fraction = $questionweights["message"][$row->number];
                $entry->feedback = $mtfrows[$key]->optionfeedback;
                $entry->feedbackformat = $mtfrows[$key]->optionfeedbackformat;
                $questionanswerid = $DB->insert_record('question_answers', $entry);
                unset($entry);

                // Copy images in the optiontext to the new answer.
                copy_files(
                    $fs,
                    $contextid,
                    $mtfrows[$key]->id,
                    $questionanswerid,
                    trim($mtfrows[$key]->optiontext),
                    "optiontext",
                    "qtype_mtf",
                    "question",
                    "answer");

                // Copy images in the answer feedback.
                copy_files(
                    $fs,
                    $contextid,
                    $mtfrows[$key]->id,
                    $questionanswerid,
                    $mtfrows[$key]->optionfeedback,
                    "feedbacktext",
                    "qtype_mtf",
                    "question",
                    "answerfeedback");
            }

            // Tansferring  mdl_question_hints -> mdl_question_hints.
            foreach ($questionhints as $key => $row) {
                $entry = new stdClass();
                $entry->questionid = $question->id;
                $entry->hint = $row->hint;
                $entry->hintformat = $row->hintformat;
                $entry->shownumcorrect = 0;
                $entry->clearwrong = 0;
                $entry->options = $row->options;
                $DB->insert_record('question_hints', $entry);
                unset($entry);
            }

            // Transferring md_qtype_mtf_options -> md_qtype_multichoice_options.
            $entry = new stdClass();
            $entry->questionid = $question->id;
            $entry->layout = 0;
            $entry->single = 0;
            $entry->shuffleanswers = $mtfoptions->shuffleanswers;
            $entry->correctfeedback = "Your answer is correct";
            $entry->correctfeedbackformat = 1;
            $entry->partiallycorrectfeedback = "Your answer is partially correct";
            $entry->partiallycorrectfeedbackformat = 1;
            $entry->incorrectfeedback = "Your answer is incorrect";
            $entry->incorrectfeedbackformat = 1;
            $entry->answernumbering = $mtfoptions->answernumbering;
            $entry->shownumcorrect = 0;
            $DB->insert_record('qtype_multichoice_options', $entry);
            unset($entry);

            // Copy images in the questiontext to new itemid.
            copy_files(
                $fs,
                $contextid,
                $question->oldid,
                $question->id,
                $question->questiontext,
                "questiontext",
                "question",
                "question",
                "questiontext");

            // Copy images in the general feedback to new itemid.
            copy_files(
                $fs,
                $contextid,
                $question->oldid,
                $question->id,
                $question->generalfeedback,
                "generalfeedback",
                "question",
                "question",
                "generalfeedback");

            // Copy tags.
            $tags = $DB->get_records_sql(
                "SELECT * FROM {tag_instance} WHERE itemid = :itemid",
                array('itemid' => $question->oldid));

            foreach ($tags as $tag) {
                $entry = new stdClass();
                $entry->tagid = $tag->tagid;
                $entry->component = $tag->component;
                $entry->itemtype = $tag->itemtype;
                $entry->itemid = $question->id;
                $entry->contextid = $tag->contextid;
                $entry->tiuserid = $tag->tiuserid;
                $entry->ordering = $tag->ordering;
                $entry->timecreated = $tag->timecreated;
                $entry->timemodified = $tag->timemodified;
                $DB->insert_record('tag_instance', $entry);
            }

            // Save changes to the database.
            $transaction->allow_commit();
        } catch (Exception $e) {
            $transaction->rollback($e);
        }
    }

    // Output: Question Migration Success.
    echo $success ? '[<font style="color:#228d00;">OK </font>]' : '[<font color="red">ERR</font>]';
    echo ' - question <i>"' . $question->oldname . '"</i> ' .
    '(ID: <a href="' . $CFG->wwwroot . '/question/bank/previewquestion/preview.php?id=' .  $question->oldid .
    '" target="_blank">' .  $question->oldid . '</a>) ';
    if ($dryrun == 0) {
        echo ($success) ? ' > <i>"' . $question->name . '"</i> ' .
        '(ID: <a href="' . $CFG->wwwroot . '/question/bank/previewquestion/preview.php?id=' . $question->id .
        '" target="_blank">' . $question->id . '</a>)' : '';
    }
    if ($dryrun == 1) {
        echo ($success) ? " is migratable" : " is <u>not</u> migratable";
    }

    echo count($questionweights["notices"]) > 0 ? " ::: <b>Notices:</b> " . implode(" | ", $questionweights["notices"]) : null;
    echo "<br/>$status\n";
}

// Showing final summary.
echo "<br/>\n";
echo "=========================================================================================<br/>\n";
echo count($questionsnotmigrated) > 0 ? "Not Migrated: <br/>" : null;
foreach ($questionsnotmigrated as $entry) {
    echo '<a href="' . $CFG->wwwroot . '/question/bank/previewquestion/preview.php?id=' . $entry["id"] . '" target="_blank">' .
    $CFG->wwwroot . '/question/bank/previewquestion/preview.php?id=' . $entry["id"] . "</a> - " . $entry["name"] . "<br/>\n";
}
echo "=========================================================================================<br/>\n";
echo "SCRIPT DONE: Time needed: " . round(microtime(1) - $starttime, 4) . " seconds.<br/>\n";
echo $nummigrated . "/" . count($questions) . " questions " . ($dryrun == 1 ? "would be " : null) . "migrated.<br/>\n";
echo "=========================================================================================<br/>\n";
die();

/**
 * Getting all subcategories of a given category.
 * @param int $categoryid
 * @return array $subcategories
 */
function get_subcategories($categoryid) {
    global $DB;

    $subcategories = $DB->get_records('question_categories', array('parent' => $categoryid), 'id');

    foreach ($subcategories as $subcategory) {
        $subcategories = array_merge($subcategories, get_subcategories($subcategory->id));
    }

    return $subcategories;
}

/**
 * Mapping the mtf weights to multichoice fractions.This function also checks for possible mapping problems.
 * @param array $weights
 * @param bool $autoweights
 * @param array $columns
 * @return array
 */
function get_weights($weights, $autoweights, $columns) {

    // Getting the Moodle fractions.
    $notices = [];
    $fractions = question_bank::fraction_options_full();
    if (empty($fractions)) {
        return array("error" => true, "message" => "Error loading Moodle fractions", "notices" => $notices);
    }

    foreach ($fractions as $key => $record) {
        $fractions[$key] = $key;
    }

    // Creating an answers array, which is still filled with mtf weights.
    $answers = [];
    $numcorrect = 0;
    foreach ($weights as $record) {
        if ($record->columnnumber == 1) {
            $answers[$record->rownumber] = $record->weight;
            $record->weight > 0 ? $numcorrect++ : null;
        }
    }

    $numincorrect = count($answers) - $numcorrect;

    // Notice - Case 1: Labels are not matching either "true" or "false".
    $validresponsetexts = array("true", "false", "wahr", "falsch");
    foreach ($columns as $record) {
        (!in_array(strtolower($record->responsetext), $validresponsetexts)) ?
        array_push($notices, 'Judgement option "' . $record->responsetext . '" not matching standard "True"/"False"') : null;
    }

    // Error - Case 1: All answers are marked as incorrect.
    if ($numcorrect == 0) {
        return array("error" => true, "message" => "All answers are incorrect", "notices" => $notices);
    }

    // Error - Case 2: Too many correct answers.
    $fractionsmin = min(array_filter($fractions, function($value) {
        return $value > 0;
    }));

    if ($numcorrect > 1 / $fractionsmin) {
        return array(
            "error" => true,
            "message" => "Too many correct answers: Number * Min-Fraction exceeds 100%",
            "notices" => $notices);
    }

    // Creating fractions for Multichoice.
    // Equal distribution of grades on 100%.
    $numcorrect != 0 ? $fractioncorrect = number_format(1 / $numcorrect, 7) : $fractioncorrect = 0;
    $numincorrect != 0 ? $fractionincorrect = number_format(-1 / $numincorrect, 7) : $fractionincorrect = 0;
    $fractioncorrectexists = in_array($fractioncorrect, $fractions);
    $fractionincorrectexists = in_array($fractionincorrect, $fractions);
/*    print_r($fractioncorrect);
    echo "<hr>";
    echo $fractioncorrectexists ;
    echo "<hr>";

echo "<hr>";
print_r($fractionincorrect);
echo "<hr>";
echo $fractionincorrectexists ;
echo "<hr>";
print_r($fractions);
*/
    // Error - Case 3: Fraction value does not exist.
    // This part of code assumes that fraction 0.05 & 0.1 is part of the Moodle internal fractions.
    if (!$fractioncorrectexists || !$fractionincorrectexists ) {
        if (!$fractioncorrectexists) {
            if ($autoweights == 0) {
                return array(
                    "error" => true,
                    "message" => "Positive weights not mapable to fraction values (Solution: autoweights=1)",
                    "notices" => $notices);
            } else {
                $num10correct = 1 / $fractionsmin - $numcorrect;
            }
            array_push($notices, "Autoweights applied to positive fractions to match moodle fractions");
        }
        if (!$fractionincorrectexists) {
            $num10incorrect = 1 / $fractionsmin - $numincorrect;
            array_push($notices, "Autoweights applied to negative fractions to match moodle fractions");
        }

        $countercorrect = $counterincorrect = 0;
        foreach ($answers as $key => $record) {
            // Apply correct fractions.
            if ($answers[$key] > 0) {
                if (!$fractioncorrectexists) {
                    $countercorrect < $num10correct ? $answers[$key] = 0.1 : $answers[$key] = 0.05;
                    $countercorrect++;
                } else {
                    $answers[$key] = $fractioncorrect;
                }
            }
            // Apply incorrect fractions.
            if ($answers[$key] == 0) {
                if (!$fractionincorrectexists) {
                    $counterincorrect < $num10incorrect ? $answers[$key] = -0.1 : $answers[$key] = -0.05;
                    $counterincorrect++;
                } else {
                    $answers[$key] = $fractionincorrect;
                }
            }
        }
        return array("error" => false, "message" => $answers, "notices" => $notices);
    }

    // All good: Applying the calculated multichoice fractions to the answers array and returning it.
    foreach ($answers as $key => $record) {
        $answers[$key] > 0 ? $answers[$key] = $fractioncorrect : $answers[$key] = $fractionincorrect;
    }
    return array("error" => false, "message" => $answers, "notices" => $notices);
}

/**
 * Extract the image filenames out of a certain text, e.g questiontext and returning the results
 * @param string $text
 * @return array
 */
function get_image_filenames($text) {
    $result = array();
    $strings = preg_split("/<img|<source/i", $text);
    foreach ($strings as $string) {
        $matches = array();
        if (preg_match('!@@PLUGINFILE@@/(.+)!u', $string, $matches) && count($matches) > 0) {
            $filename = mb_substr($matches[1], 0, mb_strpos($matches[1], '"'));
            $filename = urldecode($filename);
            $result[] = $filename;
        }
    }
    return $result;
}

/**
 * Copy files from one question to another.
 * @param object $fs
 * @param int $contextid
 * @param int $oldid
 * @param int $newid
 * @param string $text
 * @param string $type
 * @param string $olcdomponent
 * @param string $newcomponent
 * @param string $filearea
 */
function copy_files($fs, $contextid, $oldid, $newid, $text, $type, $olcdomponent, $newcomponent, $filearea) {
    $filenames = get_image_filenames($text);
    foreach ($filenames as $filename) {

        $parsedfilenameurl = parse_url($filename)["path"];
        if (isset($parsedfilenameurl)) {
            $filename = $parsedfilenameurl;
        }

        $file = $fs->get_file($contextid, $olcdomponent, $type, $oldid, '/', $filename);
        if ($file) {
            $newfile = new stdClass();
            $newfile->component = $newcomponent;
            $newfile->filearea = $filearea;
            $newfile->itemid = $newid;
            if (!$fs->get_file($contextid, $newfile->component, $newfile->filearea, $newfile->itemid, '/', $filename)) {
                $fs->create_file_from_storedfile($newfile, $file);
            }
        }
    }
}

/**
 * Check if files are actually existent
 * @param object $fs
 * @param int $contextid
 * @param int $oldid
 * @param string $text
 * @param string $type
 * @param string $olcdomponent
 * @return array
 */
function test_files($fs, $contextid, $oldid, $text, $type, $olcdomponent) {

    $success = 1;
    $status = "";

    $filenames = get_image_filenames($text);
    foreach ($filenames as $filename) {

        $parsedfilenameurl = parse_url($filename)["path"];
        if (isset($parsedfilenameurl)) {
            $filename = $parsedfilenameurl;
        }

        $file = $fs->get_file($contextid, $olcdomponent, $type, $oldid, '/', $filename);
        if (!$file) {
            $success = 0;
            $status .= "- File <font color='red'>$filename</font> not found in <u>$type</u>s<br>";
        }
    }

    return ["0" => $success, "1" => $status];
}
