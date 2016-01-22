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
 * The library of functions and constants for the projectgradeup block
 *
 * @author Dalin Williams <dalinwilliams@gamil.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2015 Dalin Williams
 * @version 1.0.0
 * @package Project Grade-Up
 */


/**
 * A bundle function that launches the two artifact function_exists
 * @param  NULL $args Just included for extensibility
 * @return void         Nothing is returned if executed properly
 */
function block_projectgradeup_update_pgu_tables($args = null){

}
/**
 * block_projectgradeup_searalize_duration - gets and overrides the duration of a course
 *
 * @param int $course_id The id of the selected course
 * @param bool $is_overriden A boolean indicator indicating to override any and all dates
 * @param int $end_date A unix timestamp representing the end of the course
 * @param int $default_duration A unix timestamp representing a default duration of an artifact
 */
function block_projectgradeup_searalize_duration($course_id, $is_overriden, $end_date, $default_duration){
    global $DB;
    $result = 7;
    //if this is overriden and the default duration is set
    if($is_overriden == 1 && isset($default_duration)){
        //get the start date for the course
        $current_date = $DB->get_record('course', array('id' => $course_id));
        //get the current day duration in unix, where 86400 is the number of ms per day
        $duration_ux = $default_duration * 86400;
        //if there is not end date, calculate our own
        if(!isset($end_date)){
            $end_date = block_projectgradeup_get_course_end_date($course_id);
        }
        $result = (($current_date->start_date + $default_duration) < $end_date) ? $default_duration : 7;
    }
    return $result;
}

/**
 * block_projectgradeup_extend__get_course_end_date - The extendable funcion to get the course end dates
 *
 * @param int $course_id The id of the selected course
 * @return array(object) An array of objects representing the records provided
 */
function block_projectgradeup_extend__get_course_end_date($course_id){
    global $DB;
    //assignment data generated here
    $assignment_sql = 'SELECT a.duedate
                       FROM {assign} as a
                       WHERE course = :course';
    $assignment_values = $DB->get_records_sql($assignment_sql, array('course' => $course_id));

    //quiz data generated here
    $quiz_sql = "SELECT timeclose as duedate
                 FROM {quiz} as q
                 WHERE course = :course";
    $quiz_values = $DB->get_records_sql($quiz_sql, array('course' => $course_id));
    //lesson data generated here
    $lesson_sql = "SELECT deadline as duedate
                   FROM {lesson} as l
                   WHERE course = :course";
    $lesson_values = $DB->get_records_sql($lesson_sql, array('course' => $course_id));
    //workshop submissions data generated here
    $workshop_submissions_sql = "SELECT submissionend as duedate
                     FROM {workshop}
                     WHERE course = :course";
    $workshop_submissions_values = $DB->get_records_sql($workshop_submissions_sql, array('course' => $course_id));
    //workshop assesment data generated here
    $workshop_assesment_sql = "SELECT assessmentend as duedate
                               FROM {workshop}
                               WHERE course = :course";
    $workshop_assesment_values = $DB->get_records_sql($workshop_assesment_sql, array('course' => $course_id));
    //forum generated here
    $forum_sql = "SELECT assesstimefinish
                  FROM {forum}
                  WHERE course = :course AND type != :news";

    $forum_values = $DB->get_records_sql($forum_sql, array('course' => $coruse_id, 'type' > 'news'));

    return array_merge($assignment_values, $quiz_values, $lesson_values, $workshop_assesment_values, $workshop_submissions_values, $forum_values);
}

/**
 * block_projectgradeup_get_course_end_date - Gets te course end date from the requested course
 *
 * @param int $course_id The course id representing the selected course
 * @return int The course end date as a unix timestamp
 */
function block_projectgradeup_get_course_end_date($course_id){
    global $DB;
    //get all the artifacts for the course
    //$course_artifacts = $DB->get_records('assign', array('course' => $course_id));

    //$course_quiz_artifacts = $DB->get_records('quiz', array('course' => $course_id));

    //$course_artifacts = array_merge($course_artifacts)

    $course_artifacts =  block_projectgradeup_extend__get_course_end_date($course_id);
    $result = 0;
    //find the largest
    foreach ($course_artifacts as $item) {
        if($item->duedate > $result){
            $result = $item->duedate;
        }
    }
    return $result;
}

/**
 * A helper function which figgures out which aggregation column to use
 * @param  int $category_id The category id in which we are looking
 * @param  int $course_id   The course id in which we are looking
 * @param  int $parent      The category's parent in which we are looking
 * @param  int   The value for the aggregationcoef column
 * @param  int $aggrecoef2  The value for the aggregationcoef2 column
 * @param  in $artifact_id The artifact id at which we are looking, default to zero
 * @return int              the needed weight
 */
function block_projectgradeup_get_aggregation_col($category_id, $course_id, $parent, $aggrecoef, $aggrecoef2, $artifact_id = null){
    global $DB;
    $results = false;

    if($get_parent_value = $DB->get_record('grade_categories', array('id' => $parent, 'courseid' => $course_id))){
        //if this is an artifact_id
        $artifact_override = new stdClass();
        $artifact_override->weightoverride = 0;
        if($artifact_id !== null){
            $artifact_override = $DB->get_record('grade_items', array('categoryid' => $category_id, 'id' => $artifact_id));
        }
        $type = block_projectgradeup_sub_aggregation_used($get_parent_value->aggregation);
        //if the incoming is a decemal
        /*if($aggrecoef < 1){
            $aggrecoef = $aggrecoef * 100;
        }*/


        $override = $DB->get_record('grade_items', array('iteminstance' => $category_id, 'itemtype' => 'category'));

        //correct for the '1' case
        /*if($aggrecoef == 1){
            $aggrecoef = 100;
        }*/
        switch ($type->type) {
            case 'aggregationcoef2':
                if($override->weightoverride ==1){
                    //if($aggrecoef2 == 0){
                        $results = $aggrecoef * .01;
                    //}
                    /*else{
                        $results = $aggrecoef2;
                    }*/
                }
                else if($artifact_override->weightoverride == 1){
                    $results = $aggrecoef *.01;
                }
                else{
                    $results = $aggrecoef2;
                }

                break;
            case 'aggregationcoef':
                if($type->override){
                    if($aggrecoef == $aggrecoef2){
                        $results = $aggrecoef * .01;
                    }
                    else if($aggrecoef == 0 && $aggrecoef2 == 0){
                        $results = $aggrecoef;
                    }
                    else if($aggrecoef != 0){
                        $results = $aggrecoef * .01;
                    }
                    else{
                        $results = $aggrecoef2;
                    }
                }
                /*if($override->weightoverride == 1){
                    $results = $aggrecoef2;
                }
                //The old code, included for some reason
                else if($artifact_override->weightoverride == 1){
                    if($aggrecoef == $aggrecoef2){
                        $results = $aggrecoef * .01;
                    }
                    else if($aggrecoef == 0 && $aggrecoef2 == 0){
                        $results = $aggrecoef;
                    }
                    else if($aggrecoef != 0){
                        $results = $aggrecoef * .01;
                    }
                    else{
                        $results = $aggrecoef2;
                    }
                }
                else{*/
                else{
                    $results = $aggrecoef*.01;
                }
                //}
                break;
            default:
                break;
        }
    }
    return $results;
}

/**
 * A simple helper function fo rholding the aggregation methods, can be updated to handle other aggregation methods
 * @param  int $aggregation_method The aggregation method of the queried category
 * @return string                     The aggregation column to use, false if the mehtod does not exist
 */
function block_projectgradeup_sub_aggregation_used($aggregation_method){
    require_once('../../lib/grade/constants.php');
    $results = new stdClass();
    switch ($aggregation_method) {
        case GRADE_AGGREGATE_MEAN:
            //0
            $results->type = 'aggregationcoef2';
            $results->override = false;
            break;
        case GRADE_AGGREGATE_WEIGHTED_MEAN:
            //10
            $results->type = 'aggregationcoef';
            $results->override = true;
            break;
        case GRADE_AGGREGATE_WEIGHTED_MEAN2:
            //11
            $results->type = 'aggregationcoef2';
            $results->override = false;
            break;
        case GRADE_AGGREGATE_SUM:
            //13
            $results->type = 'aggregationcoef2';
            $results->override = false;
            break;
        default:
            $results->type = 'aggregationcoef2';
            $results->override = false;
            break;
    }
    return $results;

}

/**
 * Independant function which calculates the stats of a artifact
 * @return boolean true if exists, false otherwise
 */
function block_update_artifact_date_time_stats(){
    global $DB;
    $result = false;

    $current_blocks = $DB->get_records('block_projectgradeup');
    $artifact_types = $DB->get_records('pgu_artifact_types');
    $artifact_difficulty = $DB->get_records('pgu_artifact_difficulty');
    foreach($current_blocks AS $item){
         block_projectgradeup_calculate_difficulty_durations($item->course_id, $artifact_types, $artifact_difficulty, true);
    }
    if($current_blocks!=false){
        $result = true;
    }
    return $result;
}

/**
 * Checks and sees if the block is activated, returns false if not
 * @param  int  $course_id The course id we are checking on
 * @return boolean            True if activated, false if not
 */
function block_projectgradeup_is_activated($course_id){
    global $DB;
    $result = false;
    if($coruse =  $DB->get_record('block_projectgradeup', array('course_id'=>$course_id))){
        $result = true;
    }
    return $result;
}
/**
 * block_projectgradeup_is_suffix_set_types - Gets the value associated with the provided suffix
 *
 * @param string $suffix The suffix to query
 * @param int $course_id  The course in which to search for the suffix
 * @return int The value associated with the provided suffix
 */
function block_projectgradeup_is_suffix_set_types($suffix, $course_id){
    $results = true;
    global $DB;
    $SQL = 'SELECT *
            FROM {pgu_artifact_types} as t
            WHERE t.suffix = :suffix
                AND t.course_id = :courseid';
    $data = $DB->get_record_sql($SQL, array('suffix' => $suffix, 'courseid' => $course_id));
    if(empty($data)){
        $results = false;
    }
    return $results;
}
/**
 * block_projectgradeup_is_suffix_set_difficulties
 *
 * @param string $suffix The suffix to query
 * @param int $course_id  The course in which to search for the suffix
 * @return int The value associated with the provided suffix
 */
function block_projectgradeup_is_suffix_set_difficulties($suffix, $course_id){
    $results = true;
    global $DB;
    $SQL = 'SELECT *
            FROM {pgu_artifact_difficulty} as t
            WHERE t.suffix = :suffix
                AND t.course_id = :courseid';
    $data = $DB->get_record_sql($SQL, array('suffix' => $suffix, 'courseid' => $course_id));
    if(empty($data)){
        $results = false;
    }
    return $results;
}

/**
 * Function which checks the difficulty durations of a specific course
 * @param  int  $course_id        The course id
 * @param  array(object)  $table_types      The contents of the table artifact_types
 * @param  array(object)  $table_difficulty The contents of the table artifact_difficulty
 * @param  boolean $is_update        indicates if this is an update or not
 * @return boolean                    True if successful, false if not
 */
function block_projectgradeup_calculate_difficulty_durations($course_id, $table_types, $table_difficulty, $is_update){
    global $DB;
    $result = false;
    //get the grade_items row associated with this course
    $course_difinitions = $DB->get_record('grade_items', array('courseid'=> $course_id, 'itemtype' => 'course'));
    $course_date_time = $DB->get_record('pgu_class_date_time',array('class_id' => $course_id));
    $aritfact_date_tiems = $DB->get_records('pgu_artifact_date_time', array('class_id' => $course_id));
    $course_details = $DB->get_record('block_projectgradeup', array('course_id' => $course_id));
    $start_date_ux = $course_date_time->class_start_date;
    $end_date_ux = $course_date_time->class_end_date;
    $class_duration =  false;
    if(isset($start_date_ux) && isset($end_date_ux)){
        $start_date = new DateTime();
        $end_date = new DateTime();
        $start_date->setTimestamp($start_date_ux);
        $end_date->setTimestamp($end_date_ux);
        $difference = $start_date->diff($end_date);
        $class_duration = abs((int)$difference->format("%r%a"));
    }
    else{
        $end_date_ux = block_projectgradeup_get_class_end_date($aritfact_date_tiems);
        $start_date = new DateTime();
        $end_date = new DateTime();
        $start_date->setTimestamp($start_date_ux);
        $end_date->setTimestamp($end_date_ux);
        $difference = $start_date->diff($end_date);
        $class_duration = abs((int)$difference->format("%r%a"));
    }
    $array_of_updates = [];
    //tre return value is fixed to true
    $result = true;
    foreach ($aritfact_date_tiems as $item) {
        //echo "working with artifact $item->artifact_name with the class id of $item->class_id and the artifact id of $item->artifact_id '\n'";
        $sudo_artifact_date_time = new stdClass();
        $sudo_artifact_date_time->id = $item->id;
        $sudo_artifact_date_time->artifact_name = $item->artifact_name;
        $heuristics = block_projectgradeup_get_artifact_date_time_difficulties($item, $table_difficulty, $table_types, $class_duration, $course_difinitions, $course_date_time, $course_details);
        //if the date is already set and this is not an update and the entry in the database is also zero
        /*$get_dates_sql = 'SELECT a.allowsubmissionsfromdate AS start_date, a.duedate AS due_date
                            FROM {grade_items} AS gi
                            JOIN {assign} AS a ON gi.iteminstance = a.id
                            AND gi.courseid = a.course
                            WHERE a.course = :courseid
                            AND gi.id = :artifactid';
        $artifact_dates = $DB->get_record_sql($get_dates_sql, array('courseid' => $item->class_id, 'artifactid' => $item->artifact_id));*/

        $artifact_dates = blocks_projectgradeup_extend_calculate_difficulty_durations($item->class_id, $item->artifact_id);
        if((($item->artifact_start_date == 0) && ($artifact_dates->start_date == 0)) || $is_update){
            $sudo_artifact_date_time->artifact_start_date = block_projectgradeup_update_artifact_start_time($heuristics->duration, $item);
        }
        else{
            $sudo_artifact_date_time->artifact_start_date = $item->artifact_start_date;
        }
        $sudo_artifact_date_time->artifact_end_date = $item->artifact_end_date;
        $sudo_artifact_date_time->artifact_id = $item->artifact_id;
        $sudo_artifact_date_time->class_id = $item->class_id;
        $sudo_artifact_date_time->artifact_title_long = $item->artifact_title_long;
        $sudo_artifact_date_time->artifact_weight = $item->artifact_weight;
        //ensure the difficulty is not under zero
        if($heuristics->difficulty < 1){
            $heuristics->difficulty = 1;
        }
        $sudo_artifact_date_time->artifact_difficulty = $heuristics->difficulty;
        //set bulk to true to indicate multiple records
        $DB->update_record('pgu_artifact_date_time', $sudo_artifact_date_time, $bulk = true);
    }
    return $result;
}
/**
 * blocks_projectgradeup_extend_calculate_difficulty_durations - The user extendable function for getting the difficulty durations
 *
 * @param int $course_id The course in which to search
 * @param int $artifact_id The artifact associated with in the course to search
 * @return array(object) An array of artifact difficulty durations
 */
function blocks_projectgradeup_extend_calculate_difficulty_durations($course_id, $artifact_id){
    global $DB;
    $merge = [];
    //assignment data generated here
    $get_dates_assignments_sql = 'SELECT a.allowsubmissionsfromdate AS start_date, a.duedate AS due_date
                        FROM {grade_items} AS gi
                        JOIN {assign} AS a ON gi.iteminstance = a.id
                        AND gi.courseid = a.course
                        WHERE a.course = :courseid
                        AND gi.itemmodule = :thistype
                        AND gi.id = :artifactid';

    $artifact_dates_assignments = $DB->get_record_sql($get_dates_assignments_sql, array('courseid' => $course_id, 'artifactid' => $artifact_id, 'thistype' => 'assign'));
    if(!empty($artifact_dates_assignments)){
        return $artifact_dates_assignments;
    }
    //quiz data generated here
    $get_dates_quiz_sql = 'SELECT a.timeopen AS start_date, a.timeclose AS due_date
                        FROM {grade_items} AS gi
                        JOIN {quiz} AS a ON gi.iteminstance = a.id
                        AND gi.courseid = a.course
                        WHERE a.course = :courseid
                        AND gi.itemmodule = :thistype
                        AND gi.id = :artifactid';

    $artifact_dates_quiz = $DB->get_record_sql($get_dates_quiz_sql, array('courseid' => $course_id, 'artifactid' => $artifact_id, 'thistype' => 'quiz'));
    if(!empty($artifact_dates_quiz)){
        return $artifact_dates_quiz;
    }
    //workshop assessment data generated here

    $get_dates_workshop_a_sql = 'SELECT a.assessmentstart AS start_date, a.assessmentend AS due_date
                        FROM {grade_items} AS gi
                        JOIN {workshop} AS a ON gi.iteminstance = a.id
                        AND gi.courseid = a.course
                        WHERE a.course = :courseid
                        AND gi.itemmodule = :thistype
                        AND gi.itemname LIKE "%(assessment)%"
                        AND gi.id = :artifactid';

    $artifact_dates_workshop_a = $DB->get_record_sql($get_dates_workshop_a_sql, array('courseid' => $course_id, 'artifactid' => $artifact_id, 'thistype' => 'workshop'));
    if(!empty($artifact_dates_workshop_a)){
        return $artifact_dates_workshop_a;
    }
    //workshop submission data generated here
    $get_dates_workshop_s_sql = 'SELECT a.submissionstart AS start_date, a.submissionend AS due_date
                        FROM {grade_items} AS gi
                        JOIN {workshop} AS a ON gi.iteminstance = a.id
                        AND gi.courseid = a.course
                        WHERE a.course = :courseid
                        AND gi.itemmodule = :thistype
                        AND gi.itemname LIKE "%(submission)%"
                        AND gi.id = :artifactid';

    $artifact_dates_workshop_s = $DB->get_record_sql($get_dates_workshop_s_sql, array('courseid' => $course_id, 'artifactid' => $artifact_id, 'thistype' => 'workshop'));
    if(!empty($artifact_dates_workshop_s)){
        return $artifact_dates_workshop_s;
    }
    //lesson data generated here
    $get_dates_lesson_sql = 'SELECT a.available AS start_date, a.deadline AS due_date
                        FROM {grade_items} AS gi
                        JOIN {lesson} AS a ON gi.iteminstance = a.id
                        AND gi.courseid = a.course
                        WHERE a.course = :courseid
                        AND gi.itemmodule = :thistype
                        AND gi.id = :artifactid';

    $artifact_dates_lesson = $DB->get_record_sql($get_dates_lesson_sql, array('courseid' => $course_id, 'artifactid' => $artifact_id, 'thistype' => 'lesson'));
    if(!empty($artifact_dates_lesson)){
        return $artifact_dates_lesson;
    }
    //forum data generated here
    $get_dates_forum_sql = 'SELECT a.assesstimestart AS start_date, a.assesstimefinish AS due_date
                        FROM {grade_items} AS gi
                        JOIN {forum} AS a ON gi.iteminstance = a.id
                        AND gi.courseid = a.course
                        WHERE a.course = :courseid
                        AND gi.id = :artifactid
                        AND gi.itemmodule = :thistype
                        AND a.type != :type';

    $artifact_dates_forum = $DB->get_record_sql($get_dates_forum_sql, array('courseid' => $course_id, 'artifactid' => $artifact_id, 'type' => 'news', 'thistype' => 'forum'));
    if(!empty($artifact_dates_forum)){
        return $artifact_dates_lesson;
    }
    return false;
}

/**
 * Calculates the start date in unix time since epoch
 * @param  int $duration The duration between the start and end dates of the artifact
 * @param  object($artifact_date_time) $artifact The aritfact with which to calculate the start date
 * @return [type]           [description]
 */
function block_projectgradeup_update_artifact_start_time($duration, $artifact){
    $start_date = new DateTime();
    $end_date = new DateTime();
    $end_date->setTimestamp($artifact->artifact_end_date);
    $start_date = clone $end_date;
    //calculate the start date
    $start_date = $start_date->modify("-{$duration} days");
    return $start_date->format("U");
}

/**
 * A function which finds the end of a course based on the artifact_start_date
 * @param  object($artifact_Date_time) $aritfact_date_tiems The collection of artifact date time objects to itterate over
 * @return unix date                      The largest date
 */
function block_projectgradeup_get_class_end_date($aritfact_date_tiems){
    $running_last = 0;
    foreach($aritfact_date_tiems as $item){
        $end = $item->artifact_end_date;
        if($running_last < $end){
            $running_last = $end;
        }
    }
    return $running_last;
}

/**
 * An alternative function for getting the artifact date time difficulties
 * @param  object $artifact_date_time         The artifact date time object
 * @param  int $duration                   The duration in days
 * @param  object $type                       A record from the table artifact_types
 * @param  int $course_length_days         The length of a course in dba_key_split
 * @param  object $current_course_difinitions A list of data pertaining to the course
 * @param  object $current_course_date_time   A record form the artifact_class_date_times
 * @return boolean                             True if the function is successful, false if not
 */
function block_projectgradeup_get_artifact_date_time_difficulties($artifact_date_time, $duration, $type, $course_length_days, $current_course_difinitions, $current_course_date_time, $course_details){
    global $DB;
    $result = false;
    $heuristics = new stdClass();
    $course_id = $artifact_date_time->class_id;
    //if the suffix permission is on
    if(get_config('projectgradeup', 'Use_Suffix') == '1'){
        //if the course level suffix usage is enabled
        $course_to_use = 0;
        if(get_config('projectgradeup', 'Use_Course_Lvl_Suffix') == '1'){
            $course_to_use = $course_id;
        }
        //we select the elements based on the level of suffix used, global or course
        $found_difficulty = block_projectgradeup_search_associative_array($duration, $course_to_use, 'course_id',true);
        $found_type = block_projectgradeup_search_associative_array($type, $course_to_use, 'course_id',true);

        $title = $artifact_date_time->artifact_name;
        $matching_string = "/\[([^\]]*)\]/";
        if(get_config('projectgradeup', 'Use_Curly_Braces')==1){
            $matching_string = "/\{([^\]]*)\}/";
        }



        preg_match_all($matching_string, $title, $matches);

        if(count($matches[1]) == 1){
            //the type 'duration' is always the first param
            if($t = block_projectgradeup_search_associative_array($found_type, $matches[1][0], 'suffix', false)){
                $heuristics->duration = $t->duration;
            }
            else{
                //set the default duration range
                $heuristics->duration = round((($artifact_date_time->artifact_category * $artifact_date_time->artifact_weight))*$course_length_days);
            }
            //the default difficulty, the weight and category weight of an artifact devided by the course total
            $heuristics->difficulty = round((($artifact_date_time->artifact_category * $artifact_date_time->artifact_weight))*$current_course_date_time->class_difficulty);
            $results = $heuristics;
        }
        else if(count($matches[1]) >= 2){
            if($t = block_projectgradeup_search_associative_array($found_type, $matches[1][0], 'suffix', false)){
                $heuristics->duration = $t->duration;
            }
            else{
                $heuristics->duration = round((($artifact_date_time->artifact_category * $artifact_date_time->artifact_weight))*$course_length_days);
            }
            if($d = block_projectgradeup_search_associative_array($found_difficulty, $matches[1][1], 'suffix', false)){
                $heuristics->difficulty = $d->difficulty;
            }
            else{
                $heuristics->difficulty = round((($artifact_date_time->artifact_category * $artifact_date_time->artifact_weight))*$current_course_date_time->class_difficulty);
            }
            $results = $heuristics;
        }
        else {
            $heuristics->duration = round((($artifact_date_time->artifact_category * ($artifact_date_time->artifact_weight)))*$course_length_days);
            $heuristics->difficulty = round((($artifact_date_time->artifact_category * ($artifact_date_time->artifact_weight)))*$current_course_date_time->class_difficulty);
            $results = $heuristics;
        }
    }
    else{

        $results = block_projectgradeup_get_artifact_date_time_durrations($artifact_date_time, $course_length_days, $course_details, $current_course_date_time);
        /*$heuristics->duration = round((($artifact_date_time->category * $artifact_date_time->weight))*$course_length_days);
        $heuristics->difficulty = round((($artifact_date_time->category * $artifact_date_time->weight))*$current_course_date_time->class_difficulty);*/
        $results = $results;
    }
    return $results;
}

/**
 * The default artifact heurstic function
 * @param  object $artifact_date_time A record form the artifact_date_time table
 * @param  int $course_length_days The length of the course in days
 * @return boolean                     True if the function is successful, false otherwise
 */
function block_projectgradeup_get_artifact_date_time_durrations($artifact_date_time, $course_length_days, $course_details, $current_course_date_time){
    global $DB;
    $result = false;
    $heuristics = new stdClass();
    $course_id = $artifact_date_time->class_id;
    $start_date_ux = $artifact_date_time->artifact_start_date;
    $end_date_ux = $artifact_date_time->artifact_end_date;
    $usable=false;
    if($course_details->override_duration == 1 && $usable = true){
        $heuristics->duration = $course_details->default_duration;
    }
    else if($end_date_ux != 0 && $start_date_ux != 0){

        $start_date = new DateTime();
        $end_date = new DateTime();
        $start_date->setTimestamp($start_date_ux);
        $end_date->setTimestamp($end_date_ux);
        $difference = $start_date->diff($end_date);
        $heuristics->duration = abs((int)$difference->format("%r%a"));
    }
    else{
        $heuristics->duration = round((($artifact_date_time->artifact_category * ($artifact_date_time->artifact_weight)))*$course_length_days);
    }
    $heuristics->difficulty = round((($artifact_date_time->artifact_category * $artifact_date_time->artifact_weight))*$current_course_date_time->class_difficulty);
    return $heuristics;
}

/**
 * An alternative version to the aformentioned funciton
 * @param  int $weight    The current weight
 * @param  object $artifact  A record from the aritfacts table
 * @param  int $course_id The id of the course
 * @param  int $duration  The duration of a course in days
 * @param  string $type      The type if artifact, or a record from the artifact_types table
 * @return mixed            The heuristic information pertaining to that course
 */
function block_projectgradeup_get_difficulty_huristics($weight, $artifact, $course_id, $duration, $type){
    global $DB, $CFG;

    $use_suffix = get_config('projectgradeup', 'Use_Suffix');
    $heuristics = new stdClass();
    //if suffixes are used
    if($use_suffix == 1 && (isset($duration) && isset($type))){
        $found_duration = block_projectgradeup_search_associative_array($duration, $course_id, 'course_id',true);
        $found_type = block_projectgradeup_search_associative_array($type, $course_id, 'course_id',true);

        $matching_string = "/\[([^\]]*)\]/";
        if(get_config('projectgradeup', 'Use_Curly_Braces')==1){
            $matching_string = "/\{([^\]]*)\}/";
        }

        $artifact_title = $artifact->title;
        preg_match_all($matching_string, $artifact->title, $matches);
        if(count($matches[1]) > 0){
            if(count($matches[1]) == 1){
                $class_diff_sql = 'SELECT bpgu.course_difficulty
                                   FROM {block_projectgradeup} AS bpgu
                                   WHERE bpgu.course_id = :courseid';
                $difficulty = $DB->get_record_sql($class_diff_sql, array('courseid' => $course_id));
                $t = block_projectgradeup_search_associative_array($found_type, $matches[1][0], 'suffix', false);
                $heuristics->range = $t->duration;
                $heuristics->difficulty = round($artifact->weight/1*$difficulty->course_difficulty);
                return $heuristics;
            }
            if(count($matches[1]) == 2){
                $t = block_projectgradeup_search_associative_array($found_type, $matches[1][0], 'suffix', false);
                $d = block_projectgradeup_search_associative_array($found_duration, $matches[1][1], 'suffix', false);
                $heuristics->range = $t->duration;
                $heuristics->difficulty = $d->difficulty;
                return $heuristics;
            }
            if(count($matches[1]) > 2){
                $t = block_projectgradeup_search_associative_array($found_type, $matches[1][0], 'suffix', false);
                $d = block_projectgradeup_search_associative_array($found_duration, $matches[1][1], 'suffix', false);
                $heuristics->range = $t->duration;
                $heuristics->difficulty = $d->difficulty;
                return $heuristics;
            }
        }
        else{
            $class_diff_sql = 'SELECT bpgu.course_difficulty
                               FROM {block_projectgradeup} AS bpgu
                               WHERE bpgu.course_id = :courseid';
            $difficulty = $DB->get_record_sql($class_diff_sql, array('courseid' => $course_id));
            $heuristics->difficulty = round($difficulty->course_difficulty/2);
            $heuristics->range = 7;
        }
    }
    else{
        $class_diff_sql = 'SELECT bpgu.course_difficulty
                           FROM {block_projectgradeup} AS bpgu
                           WHERE bpgu.course_id = :courseid';
        $difficulty = $DB->get_record_sql($class_diff_sql, array('courseid' => $course_id));
        //check if the date exists
        if($end_date != null){
            date_default_timezone_set(usertimezone(time()));
            $start_date = new DateTime();
            $end_date = new DateTime();
            $start_date->setTimestamp($artifact_date->start_date);
            $end_date->setTimestamp($artifact_date->end_date);
            $difference = $start_date->diff($end_date);
            $heuristics->range = abs((int)$e->format("%r%a"));
        }
        else{
            $heuristics->range = 7;
        }
        //find the difficulty by multiplying it by a factor of 5 (range 1-5),
        //read from the course tables
        $heuristics->difficulty = round($artifact->weight/1*$difficulty->course_difficulty);
        //extract the id
        $heuristics->id = $artifact->artifact_id;
    }
}

/**
 * Helper funciton to search through an associative array
 * @param  array() $array  The incoming associative array
 * @param  mixed $value  The searched value
 * @param  mixed $labels The label within the array on which to search
 * @param  bool $is_multiple A boolean value to either return a array or a single item
 * @return ass.array()         The instance of the associative array at which the value equals the array at the label
 */
function block_projectgradeup_search_associative_array($array, $value, $label, $is_multiple){
    $results = false;
    if($is_multiple){
        $results = [];
    }
    foreach($array as $item){
        if($item->$label==$value){
            if($is_multiple){
                $results[] = $item;
            }
            else{
                $results = $item;
                break;
            }
        }
    }
    return $results;
}
/**
 * Check the courses stored and update based on users in the course
 * @return [type] [description]
 */
function block_projectgradeup_update_on_all_users(){
    global $DB;
    $blocks_in_courses = $DB->get_records('block_projectgradeup');
    foreach($blocks_in_courses as $item){
        block_projectgradeup_update_pgu_artifact_on_user($item->course_id);
    }
}

/**
 * Gets the action buttons for the respective chart_type
 * @param  string $chart_type The chart the buttons are created for
 * @param  object $text       The strings for the button titles
 * @return object             The buttons (in html form)
 */
function block_projectgradeup_get_graph_buttons($chart_type, $text){
    $buttons = new \stdClass();
    $buttons->show = '<button type="button" class="m_pgu_button_show" id="m_pgu_button_show_'.$chart_type.' data_button=\'{"func": get_'.$chart_type.'_chart}\'>'.$text->show.'</button>';
    //$buttons->hide = '<button type="button" class="m_pgu_button_hide" id="m_pgu_button_hide_'.$chart_type.' data_button=\'{"func": hide_'.$chart_type.'_chart}\'>'.$text->hide.'</button>';
    $buttons->refresh = '<button type="button" class="m_pgu_button_refresh" id="m_pgu_button_refresh_'.$chart_type.' data_button=\'{"func": refresh_'.$chart_type.'_chart}\'>'.$text->refresh.'</button>';
    return $buttons;
}
/**
 * block_projectgradeup_get_artifacts - Gets the artifacts associated with the provided course
 *
 * @param int $course_id The course in which to return the artifacts
 * @param int $user_id The user with which to query inside the course for the artifacts
 * @param string $format The format in which to return the data
 * @return string A json string of artifacts
 */
function block_projectgradeup_get_artifacts($course_id, $user_id, $format='string'){
    require_once('./model/defined_data_layer.class.php');
    global $DB;
    $results = false;
    //get the data from the artifact class
    $artifacts = new \datalayermodel\defined_data_layer($course_id, $user_id);
    //format the data accordingly
    switch ($format) {
        case 'string':
            $results = json_encode($artifacts->get_artifact_data());
            break;
        case 'json':
            //not quite supported just yet
            $results = $artifacts->get_artifact_data();
            break;
        default:
            $results = json_encode($artifacts->get_artifact_data());
            break;
    }
    //return the data
    return $results;
}
/**
 * block_projectgradeup_get_artifacts_all - Get the artifacts for a user
 *
 * @param int $user_id The user whose artifacts we will return
 * @param string $format The format in which to return the data
 * @return string A string of artifacts
 */
function block_projectgradeup_get_artifacts_all($user_id, $format='string'){
    require_once('./model/defined_data_layer.class.php');
    global $DB;
    $results = false;
    //if we have no courses for this user
    if($courses = block_projectgradeup_get_all_user_courses($user_id)){
        foreach($courses as $id){
            //call the get_artifact_data function
            $artifacts = new \datalayermodel\defined_data_layer($id, $user_id);
            //add the data
            $results[] = json_encode([$id => $artifacts->get_artifact_data()]);
        }
        //encode
        $results = json_encode($results);
    }
    return $results;
}
/**
 * block_projectgradeup_get_artifacts_course - Gets the artifacts for every user within a course
 *
 * @param int $course_id The course in which to gather the artifacts
 * @param string $format The format in which to return the data
 * @return string A string of artifacts
 */
function block_projectgradeup_get_artifacts_course($course_id, $format='string'){
    require_once('./model/defined_data_layer.class.php');
    global $DB;
    $results = false;
    //if we have no courses for this user
    if($users = block_projectgradeup_get_all_course_users($course_id)){
        foreach($users as $id){
            //call the get_artifact_data function
            $artifacts = new \datalayermodel\defined_data_layer($course_id, $id);
            //add the data
            $results[] = json_encode([$id => $artifacts->get_artifact_data()]);
        }
        //encode
        $results = json_encode($results);
    }
    return $results;
}
/**
 * block_projectgradeup_get_burnup - Get the burnup chart calculated for a user in a course
 *
 * @param int $course_id The course in which to gather the data
 * @param int $user_id The user with which to gather the data
 * @param array(int) $resolution The resolution in which to draw the graphic
 * @param bool $colorblind The visual assistance option
 * @return string A string of the componets of the graphic
 */
function block_projectgradeup_get_burnup($course_id, $user_id, $resolution, $colorblind){
    require_once('./burnup.class.php');
    global $DB;
    $results = false;
    //now get the data
    $res = array($resolution->width, $resolution->height);
    //call the burnup function
    $burnup = new \classes\burnup($res, $course_id, $user_id, null, $colorblind);
    //return the data
    $results = $burnup->produce_complete_array();
    return $results;
}
/**
 * block_projectgradeup_get_burnup_all - Get the burnup chart calculated for a users courses
 *
 * @param int $user_id The user with which to gather the data
 * @param array(int) $resolution The resolution in which to draw the graphic
 * @param bool $colorblind The visual assistance option
 * @return string A string of the componets of the graphic
 */
function block_projectgradeup_get_burnup_all($user_id, $resolution, $colorblind){
    require_once('./burnup.class.php');
    global $DB;
    $results = false;
    //now get the data
    $res = array($resolution->width, $resolution->height);
    //if we have no courses for this user
    if($courses = block_projectgradeup_get_all_user_courses($user_id)){
        foreach($courses as $id){
            //call the burnup function
            $burnup = new \classes\burnup($res, $id, $user_id, null, $colorblind);
            //return the data
            $results[] = json_encode([$id => $burnup->produce_complete_array()]);
        }
        $results = json_encode($results);
    }
    return $results;
}
/**
 * block_projectgradeup_get_burnup_course - Get the burnup chart calculated for the users in a course
 *
 * @param int $course_id The course in which to gather the data
 * @param array(int) $resolution The resolution in which to draw the graphic
 * @param bool $colorblind The visual assistance option
 * @return string A string of the componets of the graphic
 */
function block_projectgradeup_get_burnup_course($course_id, $resolution, $colorblind){
    require_once('./burnup.class.php');
    global $DB;
    $results = false;
    //now get the data
    $res = array($resolution->width, $resolution->height);
    //if we have no users for this course
    if($users = block_projectgradeup_get_all_course_users($course_id)){
        foreach($users as $id){
            //call the burnup function
            $burnup = new \classes\burnup($res, $course_id, $id, null, $colorblind);
            //return the data
            $results[] = json_encode([$id => $burnup->produce_complete_arrays()]);
        }
        $results = json_encode($results);
    }
    return $results;

}
/**
 * block_projectgradeup_get_all_heatmap_graph -Get the heatmap of all the users courses combined into one
 *
 * @param int $user_id The user with which to gather the data
 * @param datetime usertime The user's time
 * @param array(int) $resolution The resolution in which to draw the graphic
 * @param bool $colorblind The visual assistance option
 * @return string A string of the componets of the graphic
 */
function block_projectgradeup_get_all_heatmap_graph($user_id, $resolution, $usertime, $colorblind){
    require_once('./model/defined_data_layer.class.php');
    require_once('./heat_map.class.php');
    global $DB;
    //if the user has courses
    $total_artifacts = [];
    $earilest = new DateTime('1st January 2999');
    $course_id_to_use;
    if($users_courses = $DB->get_records('pgu_artifacts', array('user_id' => $user_id),null, 'class_id')){
        //merge all arrays
        foreach($users_courses as $item){
            $artifacts = new \datalayermodel\defined_data_layer($item->class_id, $user_id);
            $data = $artifacts->get_artifact_date_time_data();
            $total_artifacts = array_merge($total_artifacts, $data);
            //find longest_course
            $course = $artifacts->get_class_date_time_data();
            if($course->class_start_date < $earilest){
                $course_id_to_use = $item->class_id;
                $earilest = $course->class_start_date;
            }
        }
        $res = array($resolution->width, $resolution->height);
        //$res = $resolution;

        $heatmap = new \classes\heat_map($res, $usertime, $user_id, $course_id_to_use, $total_artifacts, null, $colorblind, true);
        $results = $heatmap->get_all();
        return $results;
    }
}
/**
 * block_projectgradeup_get_heatmap - Get the heatmap chart calculated for a user in a course
 *
 * @param int $course_id The course in which to gather the data
 * @param int $user_id The user with which to gather the data
 * @param datetime usertime The user's time
 * @param array(int) $resolution The resolution in which to draw the graphic
 * @param bool $colorblind The visual assistance option
 * @return string A string of the componets of the graphic
 */
function block_projectgradeup_get_heatmap($course_id, $user_id, $resolution, $usertime, $colorblind){
    require_once('./heat_map.class.php');
    global $DB;
    $results = false;
    //now get the data
    $res = array($resolution->width, $resolution->height);
    //call the burnup function
    $heat_map = new \classes\heat_map($res, $usertime, $user_id, $course_id, null, null, $colorblind);
    //return the data
    $results[] = $heat_map->get_all();
    $results[] = block_projectgradeup_get_all_heatmap_graph($user_id, $resolution, $usertime, $colorblind);
    $results = json_encode($results);

    return $results;
}
/**
 * block_projectgradeup_get_heatmap_all - Get the heatmap chart calculated for all of a users courses
 *
 * @param int $user_id The user with which to gather the data
 * @param datetime usertime The user's time
 * @param array(int) $resolution The resolution in which to draw the graphic
 * @param bool $colorblind The visual assistance option
 * @return string A string of the componets of the graphic
 */
function block_projectgradeup_get_heatmap_all($user_id, $resolution, $usertime, $colorblind){
    require_once('./heat_map.class.php');
    global $DB;
    $results = false;
    //now get the data
    $res = array($resolution->width, $resolution->height);
    //if we have no courses for this user
    if($courses = block_projectgradeup_get_all_course_users($user_id)){
        foreach($courses as $id){
            //call the burnup function
            $heat_map = new \classes\heat_map($res, $usertime, $user_id, $id, null, null, $colorblind);
            //return the data
            $results[] = json_encode([$id => $heat_map->get_all()]);
        }
        //push the semester results
        $results[] = json_encode(['semester' => block_projectgradeup_get_all_heatmap_graph($user_id, $resolution, $usertime, $colorblind)]);
        $results = json_encode($results);
    }
    return $results;
}
/**
 * block_projectgradeup_get_heatmap_course - Get the heatmap chart calculated for all users in a course
 *
 * @param int $course_id The course in which to gather the data
 * @param datetime usertime The user's time
 * @param array(int) $resolution The resolution in which to draw the graphic
 * @param bool $colorblind The visual assistance option
 * @return string A string of the componets of the graphic
 */
function block_projectgradeup_get_heatmap_course($course_id, $resolution, $usertime, $colorblind){
    require_once('./heat_map.class.php');
    global $DB;
    $results = false;
    //now get the data
    $res = array($resolution->width, $resolution->height);
    //if we have no courses for this user
    if($users = block_projectgradeup_get_all_user_courses($course_id)){
        foreach($courses as $id){
            //call the burnup function
            $heat_map = new \classes\heat_map($res, $usertime, $id, $course_id, null, null, $colorblind);
            //return the data
            $results[] = json_encode([$id => $heat_map->get_all()]);
        }
        //push the semester results
        $results[] = json_encode(['semester' => block_projectgradeup_get_all_heatmap_graph($user_id, $resolution, $usertime, $colorblind)]);
        $results = json_encode($results);
    }
    return $results;
}
/**
 * block_projectgradeup_get_all_user_courses - Get all of a users courses
 *
 * @param int $user_id The users id
 * @return array An array of the user's courses
 */
function block_projectgradeup_get_all_user_courses($user_id){
    global $DB;
    $results = false;
    //select the unique entries
    if($course_ids = $DB->get_records('pgu_artifacts', array('user_id' => $user_id),'class_id')){
        $result = $course_ids;
    }
    return $results;
}
/**
 * block_projectgradeup_get_all_course_users - Get all the users within a course
 *
 * @param int $course_id The course id in which to pull the users
 * @return array The array of users within a course
 */
function block_projectgradeup_get_all_course_users($course_id){
    global $DB;
    $results = false;
    //select the unique entries
    if($user_ids = $DB->get_records('pgu_artifacts', array('class_id' => $course_id),'user_id')){
        $result = $user_ids;
    }
    return $results;
}
/**
 * Update the talbes on a user's removal
 * @param  int $course_id The course within which to remove the user
 * @return boolean            True when complete
 */
function block_projectgradeup_update_pgu_artifact_on_user($course_id){
    global $DB;
    //sql to check the course and return any student who needs to be removed
    $users_in_course_sql = 'SELECT a.user_id
                            FROM {pgu_artifacts} AS a
                            LEFT JOIN(SELECT
                                        u.id AS user_id
                                    FROM
                                        {role_assignments} ra
                                        JOIN {user} u ON u.id = ra.userid
                                        JOIN {role} r ON r.id = ra.roleid
                                        JOIN {context} cxt ON cxt.id = ra.contextid
                                        JOIN {course} c ON c.id = cxt.instanceid
                                    WHERE ra.userid = u.id
                                        AND ra.contextid = cxt.id
                                        AND cxt.contextlevel =50
                                        AND cxt.instanceid = c.id
                                        AND r.shortname = :student
                                        AND c.id = :courseid) AS users ON a.user_id = users.user_id
                            WHERE users.user_id IS NULL
                                AND a.class_id = :coursei';
    $users_in_course = $DB->get_records_sql($users_in_course_sql,array('student' => 'student','courseid' => $course_id));
    //remote all of the missing or removed students
    foreach ($users_in_course as $item) {
        $SQL = 'user_id = :userid AND class_id = :courseid';
        $DB->delete_records_select('pgu_artifacts', $SQL,array('userid' => $item->user_id, 'courseid' => $course_id, 'coursei' => $course_id));
    }
    return true;
}

/**
 * Update all tables on a course removal
 * @param  NULL $args NULL
 * @return void
 */
function block_projectgradeup_update_and_delete_all_on_course($is_all = false, $is_forced = false){
    global $DB;

    //first we update the stored blocks page
    $block_sql = 'SELECT psb.course_id AS course_id
                  FROM {block_projectgradeup} AS psb
                       LEFT JOIN {course} AS c ON psb.course_id = c.id
                  WHERE c.id IS NULL';
    $course_not_in_sys = $DB->get_record_sql($block_sql);
    //delete the row in block_projectgradeup
    foreach ($course_not_in_sys as $item) {
        $SQL = 'course_id = :courseid';
        $DB->delete_records_select('block_projectgradeup', $SQL, array('courseid'=>$item->course_id));
    }
    if($is_all){
        block_projectgradeup_update_tables_on_block_table_absence($is_forced);
    }
}

/**
 * block_projectgradeup_update_pgu_artifacts -The fucntion to update the and artifact data time tables
 * @param  other $args An expandable list of params for the function
 * @return void
 */
function block_projectgradeup_update_pgu_artifacts($args = null){
    global $DB;
    $is_update_artifact_date_time = false;
    if(isset($args) && is_bool($args[0])){
        $is_update_artifact_date_time = $args[0];
    }
    //check in mdl_grade_categories if aggregation is set to 10, 11, or 0, read only from aggregationcoef2
    $in_database = $DB->get_records('block_projectgradeup');
    //if no one has a block, dont run it!
    if(empty($in_database)){
        return false;
    }
    //itterate through the class, and update the classes
    foreach($in_database AS $item){
        $get_users_sql = 'SELECT
                            u.id AS user_id,
                            c.id AS course_id
                        FROM {role_assignments} ra
                            JOIN {user} u ON u.id = ra.userid
                            JOIN {role} r ON r.id = ra.roleid
                            JOIN {context} cxt ON cxt.id = ra.contextid
                            JOIN {course} c ON c.id = cxt.instanceid
                        WHERE ra.userid = u.id
                            AND ra.contextid = cxt.id
                            AND cxt.contextlevel =50
                            AND cxt.instanceid = c.id
                            AND r.shortname = :student
                            AND c.id = :courseid
                        ORDER BY c.fullname';
        //get the users
        $users_in_course = $DB->get_records_sql($get_users_sql, array('student' => 'student', 'courseid' => $item->course_id));
        //create course information
        $grade_categories = block_projectgradeup_get_grade_categories($item->course_id);
        //set and update artifact date times as well
        $sudo_artifact_date_time = block_projectgradeup_artifact_date_time_helper($item->course_id, $grade_categories);
        $delete_adt_sql = 'class_id = :courseid';
        $DB->delete_records_select('pgu_artifact_date_time', $delete_adt_sql, array('courseid'=>$item->course_id));
        $DB->insert_records('pgu_artifact_date_time', $sudo_artifact_date_time);
        //update the difficulity values
        $types = $DB->get_records('pgu_artifact_types');
        $difficulty = $DB->get_records('pgu_artifact_difficulty');
        block_projectgradeup_calculate_difficulty_durations($item->course_id, $types, $difficulty , $is_update_artifact_date_time);

        //itterate over all the students
        foreach($users_in_course AS $usr){
            //get the artifacts for this user
            //$pre_artifacts = $DB->get_records_sql($artifact_sql, array('student' => 'student', 'courseid' => $item->course_id, 'userid' => $usr->user_id));*/
            $pre_artifacts = block_projectgradeup_extend_update_pgu_artifacts($item->course_id, $usr->user_id);
            $sudo_artifacts = block_projectgradeup_artifacts_per_user_helper($pre_artifacts, $grade_categories);
            $SQL = 'user_id = :userid AND class_id = :courseid';
            //delete the old records
            $DB->delete_records_select('pgu_artifacts', $SQL, array('userid'=>$usr->user_id,'courseid'=>$item->course_id));
            $DB->insert_records('pgu_artifacts', $sudo_artifacts);
        }
    }
    //indicator showing the fucntion exited properly
    return true;
}
/**
 * block_projectgradeup_extend_update_pgu_artifacts - The helper fucntion to update the and artifact data time tables
 * @param int $course_id The course in which to pull the artifact data
 * @param int $user_id The user in which to pull the artifact data
 * @return array(object) An array of artifact objects
 */
function block_projectgradeup_extend_update_pgu_artifacts($course_id, $user_id){
    global $DB;
    //get the artifacts for this user
    $artifact_sql_assignmments = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.allowsubmissionsfromdate AS start_date,
                          a.duedate AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {assign} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND u.id = :userid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_assignmments = $DB->get_records_sql($artifact_sql_assignmments, array('student' => 'student', 'courseid' => $course_id, 'userid' => $user_id, 'thistype' => 'assign'));
    //get the quizes for this user
    $artifact_sql_quiz = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.timeopen AS start_date,
                          a.timeclose AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {quiz} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND u.id = :userid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_quiz = $DB->get_records_sql($artifact_sql_quiz, array('student' => 'student', 'courseid' => $course_id, 'userid' => $user_id, 'thistype' => 'quiz'));
    //get the workshop assessments for this user
    $artifact_sql_workshop_a = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.assessmentstart AS start_date,
                          a.assessmentend AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {workshop} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND u.id = :userid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gi.itemname LIKE "%(assessment)%"
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_workshop_a = $DB->get_records_sql($artifact_sql_workshop_a, array('student' => 'student', 'courseid' => $course_id, 'userid' => $user_id, 'thistype' => 'workshop'));
    //get the workshop submissions for this user
    $artifact_sql_workshop_s = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.submissionstart AS start_date,
                          a.submissionend AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {workshop} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND u.id = :userid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gi.itemname LIKE "%(submission)%"
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_workshop_s = $DB->get_records_sql($artifact_sql_workshop_s, array('student' => 'student', 'courseid' => $course_id, 'userid' => $user_id, 'thistype' => 'workshop'));
    //get the lessons for this user
    $artifact_sql_lesson = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.available AS start_date,
                          a.deadline AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {lesson} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND u.id = :userid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_lesson = $DB->get_records_sql($artifact_sql_lesson, array('student' => 'student', 'courseid' => $course_id, 'userid' => $user_id, 'thistype' => 'lesson'));
    //get the forums for this user
    $artifact_sql_forum = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.assesstimestart AS start_date,
                          a.assesstimefinish AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {forum} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND u.id = :userid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gc.fullname IS NOT NULL
                        AND a.type != :type
                    ORDER BY counter ASC';
    $pre_artifacts_forum = $DB->get_records_sql($artifact_sql_forum, array('student' => 'student', 'courseid' => $course_id, 'userid' => $user_id, 'type' => 'news', 'thistype' => 'forum'));

    return array_merge($pre_artifacts_assignmments, $pre_artifacts_quiz, $pre_artifacts_workshop_a, $pre_artifacts_workshop_s, $pre_artifacts_lesson, $pre_artifacts_forum);
}
/**
 * Update the class_date_tiem table using moodles update function
 * @return boolean True once done
 */
function block_projectgradeup_update_pgu_class_date_time_update(){
    global $DB;
    //get the courses
    $courses = $DB->get_records('block_projectgradeup');
    $class_date_time_sql = 'SELECT c.id as class_id,
                                   c.fullname as class_long_name,
                                   c.shortname as class_short_name,
                                   c.idnumber as class_number,
                                   c.startdate as class_start_date
                            FROM {course} as c
                            WHERE c.id = :courseid';

    //update the class date times
    foreach($courses as $item){
        $asso_artifact = $DB->get_record('pgu_class_date_time', array('class_id' => $item->course_id));
        $course_date_time_found = $DB->get_record_sql($class_date_time_sql, array('courseid'=>$item->course_id));
        $sudo_class_date_time = new stdClass();
        $sudo_class_date_time->id = $asso_artifact->id;
        $sudo_class_date_time->class_long_name = $course_date_time_found->class_long_name;
        $sudo_class_date_time->class_start_date = $course_date_time_found->class_start_date;
        $sudo_class_date_time->class_number = $course_date_time_found->class_number;
        $sudo_class_date_time->class_short_name = $course_date_time_found->class_short_name;
        $sudo_class_date_time->class_end_date = $item->course_end_date;
        $sudo_class_date_time->class_difficulty = $item->course_difficulty;//a teacher can modify this value
        $sudo_class_date_time->class_id = $course_date_time_found->class_id;
        $DB->update_record('pgu_class_date_time', $sudo_class_date_time, $bulk = true);
    }
}
/**
 * Update the artifact_date_time table using Moodle's update function
 * @return [type] [description]
 */
function block_projectgradeup_update_pgu_artifacts_date_time_update(){
    global $DB;

    $types = $DB->get_records('pgu_artifact_types');
    $difficulty = $DB->get_records('pgu_artifact_difficulty');

    $in_database = $DB->get_records('block_projectgradeup');
    foreach ($in_database as $record) {
        $pre_artifacts = block_projectgradeup_extend_update_pgu_artifacts_date_time_update($record->course_id);
        $course_info = block_projectgradeup_get_grade_categories($record->course_id);

        foreach ($pre_artifacts as $item) {
            $asso_artifact = $DB->get_record('pgu_artifact_date_time', array('class_id' => $record->course_id, 'artifact_id' => $item->artifact_id));
            $courseid = $record->course_id;
            $artifactid = $item->artifact_id;
            $sudo_artifact_date_time = new stdClass();
            $sudo_artifact_date_time->id = $asso_artifact->id;
            $sudo_artifact_date_time->artifact_name = $item->artifact_name;
            $sudo_artifact_date_time->artifact_start_date = $item->start_date;
            $sudo_artifact_date_time->artifact_end_date = $item->end_date;
            $sudo_artifact_date_time->artifact_id = $artifactid;
            $sudo_artifact_date_time->class_id = $courseid;
            $sudo_artifact_date_time->artifact_title_long = $item->artifact_name . $courseid . $artifactid;
            $sudo_artifact_date_time->artifact_weight = $course_info->$courseid->$artifactid->weight;
            $sudo_artifact_date_time->artifact_category = $course_info->$courseid->$artifactid->category_weight;
            $sudo_artifact_date_time->artifact_difficulty = $asso_artifact->artifact_difficulty;
            $DB->update_record('pgu_artifact_date_time', $sudo_artifact_date_time, $bulk = true);
        }
        block_projectgradeup_calculate_difficulty_durations($record->course_id, $types, $difficulty , false);
    }
}
/**
 * block_projectgradeup_extend_update_pgu_artifacts_date_time_update - The helper fucntion to update the and artifact data time tables
 * @param int $course_id The course in which to pull the artifact data
 * @return array(object) An array of artifact objects
 */
function block_projectgradeup_extend_update_pgu_artifacts_date_time_update($course_id){
    global $DB;
    //get the grades and stuff for aggregation (artifacts)
    $artifact_assignment_sql = 'SELECT DISTINCT (@counter := @counter +1) as counter,
              gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.allowsubmissionsfromdate AS start_date,
              a.duedate AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
        FROM {role_assignments} AS ra
            JOIN {role} AS r ON r.id = ra.roleid
            JOIN {context} AS ctx ON ctx.id = ra.contextid
            JOIN {course} AS c ON (c.id = ctx.instanceid)
            JOIN {grade_items} AS gi ON c.id = gi.courseid
            JOIN {assign} AS a ON a.id = gi.iteminstance
            LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
            cross join (select @counter := 0) r
        WHERE ra.contextid = ctx.id
            AND c.id = :courseid
            AND ctx.instanceid = c.id
            AND gi.itemmodule = :thistype
            AND gc.fullname IS NOT NULL';
    $pre_artifacts_assignment = $DB->get_records_sql($artifact_assignment_sql, array('courseid' => $course_id, 'thistype' => 'assign'));
    //get the grades and stuff for aggregation (quizs)
    $artifact_quiz_sql = 'SELECT DISTINCT (@counter := @counter +1) as counter,
              gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.timeopen AS start_date,
              a.timeclose AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
        FROM {role_assignments} AS ra
            JOIN {role} AS r ON r.id = ra.roleid
            JOIN {context} AS ctx ON ctx.id = ra.contextid
            JOIN {course} AS c ON (c.id = ctx.instanceid)
            JOIN {grade_items} AS gi ON c.id = gi.courseid
            JOIN {quiz} AS a ON a.id = gi.iteminstance
            LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
            cross join (select @counter := 0) r
        WHERE ra.contextid = ctx.id
            AND c.id = :courseid
            AND ctx.instanceid = c.id
            AND gi.itemmodule = :thistype
            AND gc.fullname IS NOT NULL';
    $pre_artifacts_quiz = $DB->get_records_sql($artifact_quiz_sql, array('courseid' => $course_id, 'thistype' => 'quiz'));
    //get the grades and stuff for aggregation (workshop assessments)
    $artifact_workshop_a_sql = 'SELECT DISTINCT (@counter := @counter +1) as counter,
              gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.assessmentstart AS start_date,
              a.assessmentend AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
        FROM {role_assignments} AS ra
            JOIN {role} AS r ON r.id = ra.roleid
            JOIN {context} AS ctx ON ctx.id = ra.contextid
            JOIN {course} AS c ON (c.id = ctx.instanceid)
            JOIN {grade_items} AS gi ON c.id = gi.courseid
            JOIN {workshop} AS a ON a.id = gi.iteminstance
            LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
            cross join (select @counter := 0) r
        WHERE ra.contextid = ctx.id
            AND c.id = :courseid
            AND ctx.instanceid = c.id
            AND gi.itemmodule = :thistype
            AND gi.itemname LIKE "%(assessment)%"
            AND gc.fullname IS NOT NULL';
    $pre_artifacts_workshop_a = $DB->get_records_sql($artifact_workshop_a_sql, array('courseid' => $course_id, 'thistype' => 'workshop'));
    //get the grades and stuff for aggregation (workshop submissions)
    $artifact_workshop_s_sql = 'SELECT DISTINCT (@counter := @counter +1) as counter,
              gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.submissionstart AS start_date,
              a.submissionend AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
        FROM {role_assignments} AS ra
            JOIN {role} AS r ON r.id = ra.roleid
            JOIN {context} AS ctx ON ctx.id = ra.contextid
            JOIN {course} AS c ON (c.id = ctx.instanceid)
            JOIN {grade_items} AS gi ON c.id = gi.courseid
            JOIN {workshop} AS a ON a.id = gi.iteminstance
            LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
            cross join (select @counter := 0) r
        WHERE ra.contextid = ctx.id
            AND c.id = :courseid
            AND ctx.instanceid = c.id
            AND gi.itemmodule = :thistype
            AND gi.itemname LIKE "%(submission)%"
            AND gc.fullname IS NOT NULL';
    $pre_artifacts_workshop_s = $DB->get_records_sql($artifact_workshop_s_sql, array('courseid' => $course_id, 'thistype' => 'workshop'));
    //get the grades and stuff for aggregation (lessons)
    $artifact_lesson_sql = 'SELECT DISTINCT (@counter := @counter +1) as counter,
              gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.available AS start_date,
              a.deadline AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
        FROM {role_assignments} AS ra
            JOIN {role} AS r ON r.id = ra.roleid
            JOIN {context} AS ctx ON ctx.id = ra.contextid
            JOIN {course} AS c ON (c.id = ctx.instanceid)
            JOIN {grade_items} AS gi ON c.id = gi.courseid
            JOIN {lesson} AS a ON a.id = gi.iteminstance
            LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
            cross join (select @counter := 0) r
        WHERE ra.contextid = ctx.id
            AND c.id = :courseid
            AND ctx.instanceid = c.id
            AND gi.itemmodule = :thistype
            AND gc.fullname IS NOT NULL';
    $pre_artifacts_lesson = $DB->get_records_sql($artifact_lesson_sql, array('courseid' => $course_id, 'thistype' => 'lesson'));
    //get the grades and stuff for aggregation (forums)
    $artifact_forum_sql = 'SELECT DISTINCT (@counter := @counter +1) as counter,
              gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.assesstimestart AS start_date,
              a.assesstimefinish AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
        FROM {role_assignments} AS ra
            JOIN {role} AS r ON r.id = ra.roleid
            JOIN {context} AS ctx ON ctx.id = ra.contextid
            JOIN {course} AS c ON (c.id = ctx.instanceid)
            JOIN {grade_items} AS gi ON c.id = gi.courseid
            JOIN {forum} AS a ON a.id = gi.iteminstance
            LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
            cross join (select @counter := 0) r
        WHERE ra.contextid = ctx.id
            AND c.id = :courseid
            AND ctx.instanceid = c.id
            AND gi.itemmodule = :thistype
            AND gc.fullname IS NOT NULL';
    $pre_artifacts_forum = $DB->get_records_sql($artifact_forum_sql, array('courseid' => $course_id, 'thistype' => 'forum'));

    return array_merge($pre_artifacts_assignment, $pre_artifacts_quiz, $pre_artifacts_workshop_a, $pre_artifacts_workshop_s, $pre_artifacts_lesson, $pre_artifacts_forum);
}

/**
 * Update the artifacts table using Moodle's update function
 * @return boolean True once done
 */
function block_projectgradeup_update_pgu_artifacts_update(){
    global $DB;

    $in_database = $DB->get_records('block_projectgradeup');
    foreach ($in_database as $record) {

        $pre_artifacts = block_projectgradeup_extends_update_pgu_artifacts_update($record->course_id);

        $grade_categories = block_projectgradeup_get_grade_categories($record->course_id);

        $user_time = usertime(time());
        foreach ($pre_artifacts as $item) {
            //get the artifact
            $asso_artifact = $DB->get_record('pgu_artifacts', array('class_id' => $record->course_id, 'user_id' => $item->user_id, 'artifact_id' => $item->artifact_id));

            if($user_time < $item->end_date && $item->grade == 0){
                $status = 'notdue';
            }
            else{
                if($item->grade == 0){
                    $status = 'incomplete';
                }
                elseif($item->grade === null){
                    $status = 'notgraded';
                }
                else{
                    $status = 'completed';
                }
            }
            $sudo_artifact = new stdClass();
            $courseid = $item->course_id;
            $artifactid = $item->artifact_id;
            $sudo_artifact->id = $asso_artifact->id;
            $sudo_artifact->grade = $item->grade;
            $sudo_artifact->weight = $grade_categories->$courseid->$artifactid->weight;
            $sudo_artifact->category = $grade_categories->$courseid->$artifactid->category_weight;
            $sudo_artifact->status = $status;
            $sudo_artifact->title = $item->artifact_name;
            $sudo_artifact->type = $item->artifact_type;
            $sudo_artifact->title_long = $item->artifact_name . $courseid . $artifactid;
            $sudo_artifact->class_id = $courseid;
            $sudo_artifact->user_id = $item->user_id;
            $sudo_artifact->artifact_id = $item->artifact_id;
            $DB->update_record('pgu_artifacts', $sudo_artifact, $bulk = true);
        }
    }
}

/**
 * block_projectgradeup_extends_update_pgu_artifacts_update - The helper fucntion to update the and artifact data time tables
 * @param int $course_id The course in which to pull the artifact data
 * @return array(object) An array of artifact objects
 */
function block_projectgradeup_extends_update_pgu_artifacts_update($course_id){
    global $DB;
    //assignments
    $artifact_sql_assignmments= 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.allowsubmissionsfromdate AS start_date,
                          a.duedate AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {assign} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_assignments = $DB->get_records_sql($artifact_sql_assignmments, array('student' => 'student', 'courseid' => $course_id, 'thistype' => 'assign'));
    //quiz
    $artifact_sql_quiz = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.timeopen AS start_date,
                          a.timeclose AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {quiz} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_quiz = $DB->get_records_sql($artifact_sql_quiz, array('student' => 'student', 'courseid' => $course_id, 'thistype' => 'quiz'));
    //workshop assessments
    $artifact_sql_workshop_a = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.assessmentstart AS start_date,
                          a.assessmentend AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {workshop} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gi.itemname LIKE "%(assessment)%"
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_workshop_a = $DB->get_records_sql($artifact_sql_workshop_a, array('student' => 'student', 'courseid' => $course_id, 'thistype' => 'workshop'));
    //workshop submissions
    $artifact_sql_workshop_s = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.submissionstart AS start_date,
                          a.submissionend AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {workshop} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gi.itemname LIKE "%(submission)%"
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_workshop_s = $DB->get_records_sql($artifact_sql_workshop_s, array('student' => 'student', 'courseid' => $course_id, 'thistype' => 'workshop'));
    //lesson
    $artifact_sql_lesson = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.available AS start_date,
                          a.deadline AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {lesson} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_lesson = $DB->get_records_sql($artifact_sql_lesson, array('student' => 'student', 'courseid' => $course_id, 'thistype' => 'lesson'));
    //forum
    $artifact_sql_forum = 'SELECT (@counter := @counter +1) as counter,
                          u.id AS user_id,
                          c.id AS course_id,
                          gi.id AS artifact_id,
                          gi.itemname AS artifact_name,
                          gi.categoryid AS artifact_type_category_id,
                          gc.fullname AS artifact_type_category_name,
                          gc.aggregation AS aggregation_type,
                          gi.itemmodule AS artifact_type,
                          gg.rawgrade AS raw_grade,
                          gg.finalgrade AS grade,
                          a.assesstimestart AS start_date,
                          a.assesstimefinish AS end_date,
                          gi.aggregationcoef2 AS artifact_category_weight2,
                          gi.aggregationcoef AS artifact_category_weight,
                          gg.aggregationweight AS true_aggregation_weight
                    FROM mdl_role_assignments AS ra
                        JOIN {role} AS r ON r.id = ra.roleid
                        JOIN {context} AS ctx ON ctx.id = ra.contextid
                        JOIN {course} AS c ON (c.id = ctx.instanceid)
                        JOIN {grade_items} AS gi ON c.id = gi.courseid
                        JOIN {user} AS u ON (u.id =  ra.userid)
                        JOIN {forum} AS a ON a.id = gi.iteminstance
                        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
                        LEFT JOIN {grade_grades} AS gg ON (gi.id = gg.itemid) AND (u.id = gg.userid)
                        cross join (select @counter := 0) r
                    WHERE r.shortname = :student
                        AND c.id = :courseid
                        AND ra.contextid = ctx.id
                        AND ctx.contextlevel = 50
                        AND ctx.instanceid = c.id
                        AND gi.itemmodule = :thistype
                        AND gc.fullname IS NOT NULL
                    ORDER BY counter ASC';
    $pre_artifacts_forum = $DB->get_records_sql($artifact_sql_forum, array('student' => 'student', 'courseid' => $course_id, 'thistype' => 'forum'));

    return array_merge($pre_artifacts_assignments, $pre_artifacts_quiz, $pre_artifacts_workshop_a, $pre_artifacts_workshop_s, $pre_artifacts_lesson, $pre_artifacts_forum);
}

/**
 * A helper funciton for the block_projectgradeup_update_pgu_artifacts function
 * @param  int $course_id   The course we are focused on
 * @param  object $course_info An object containing the weights of the category and individual artifact
 * @return array            An array of artifct_date_time records
 */
function block_projectgradeup_artifact_date_time_helper($course_id, $course_info){
    global $DB;
    $delete_sql = 'class_id = :courseid';
    $DB->delete_records_select('pgu_artifact_date_time', $delete_sql, array('courseid' => $course_id));

    $pre_artifacts = block_projectgradeup_extend_artifact_date_time_helper($course_id);

    $array_of_sudo_artifact_date_times = [];
    foreach ($pre_artifacts as $item) {
        $courseid = $course_id;
        $artifactid = $item->artifact_id;
        $sudo_artifact_date_time = new stdClass();
        $sudo_artifact_date_time->artifact_name = $item->artifact_name;
        $sudo_artifact_date_time->artifact_start_date = $item->start_date;
        $sudo_artifact_date_time->artifact_end_date = $item->end_date;
        $sudo_artifact_date_time->artifact_id = $artifactid;
        $sudo_artifact_date_time->class_id = $courseid;
        $sudo_artifact_date_time->artifact_title_long = $item->artifact_name . $courseid . $artifactid;
        $sudo_artifact_date_time->artifact_weight = $course_info->$courseid->$artifactid->weight;//$item->artifact_category_weight;
        $sudo_artifact_date_time->artifact_category = $course_info->$courseid->$artifactid->category_weight;
        $sudo_artifact_date_time->artifact_difficulty = null;
        $array_of_sudo_artifact_date_times[] = $sudo_artifact_date_time;
    }
    return $array_of_sudo_artifact_date_times;
}

function block_projectgradeup_extend_artifact_date_time_helper($course_id){
    global $DB;
    //assignments
    $artifact_sql_assignmments = 'SELECT DISTINCT  gi.id AS artifact_id,
			  c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
    		  gc.fullname AS artifact_type_category_name,
			  gc.aggregation AS aggregation_type,
    		  gi.itemmodule AS artifact_type,
              a.allowsubmissionsfromdate AS start_date,
              a.duedate AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
    		  gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {assign} AS a ON a.id = gi.iteminstance
    	LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
		AND gc.fullname IS NOT NULL';
    $pre_artifacts_assignments = $DB->get_records_sql($artifact_sql_assignmments, array('courseid' => $course_id, 'thistype' => 'assign'));
    //quizes
    $artifact_sql_quiz = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.timeopen AS start_date,
              a.timeclose AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {quiz} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
        AND gc.fullname IS NOT NULL';
    $pre_artifacts_quiz = $DB->get_records_sql($artifact_sql_quiz, array('courseid' => $course_id, 'thistype' => 'quiz'));
    //workshop assessments
    $artifact_sql_workshop_a = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.assessmentstart AS start_date,
              a.assessmentend AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {workshop} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
        AND gi.itemname LIKE "%(assessment)%"
        AND gc.fullname IS NOT NULL';
    $pre_artifacts_workshop_a = $DB->get_records_sql($artifact_sql_workshop_a, array('courseid' => $course_id, 'thistype' => 'workshop'));
    //workshop submissions
    $artifact_sql_workshop_s = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.submissionstart AS start_date,
              a.submissionend AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {workshop} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
        AND gi.itemname LIKE "%(submission)%"
        AND gc.fullname IS NOT NULL';
    $pre_artifacts_workshop_s = $DB->get_records_sql($artifact_sql_workshop_s, array('courseid' => $course_id, 'thistype' => 'workshop'));
    //lessons
    $artifact_sql_lesson = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.available AS start_date,
              a.deadline AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {lesson} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
        AND gc.fullname IS NOT NULL';
    $pre_artifacts_lesson = $DB->get_records_sql($artifact_sql_lesson, array('courseid' => $course_id, 'thistype' => 'lesson'));
    //forumns
    $artifact_sql_forum = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.assesstimestart AS start_date,
              a.assesstimefinish AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {forum} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
        AND gc.fullname IS NOT NULL';
    $pre_artifacts_forum = $DB->get_records_sql($artifact_sql_forum, array('courseid' => $course_id, 'thistype' => 'forum'));

    return array_merge($pre_artifacts_assignments, $pre_artifacts_quiz, $pre_artifacts_workshop_a, $pre_artifacts_workshop_s, $pre_artifacts_lesson, $pre_artifacts_forum);
}
 /**
  * block_projectgradeup_sanatize_ajax - Sanatizes the inputs from PGU ajax calls
  *
  * @param object $resolution The incoming resolution to sanatize
  * @param string $request_params The title of the incoming request
  * @param bool $colorblind The colorblind boolean to sanatize
  * @param string $format The format string to sanatize
  * @return bool True if sanatization passed, false otherwise
  */
function block_projectgradeup_sanatize_ajax($resolution, $request_params, $colorblind, $format){
    $results = new stdClass();
    //first sanatize the format
    if($resolution !== null){
        //if the resolution is not set, set to default
        if($resolution->width === 0 || $resolution->height === 0){
            $resolution->width = 1080;
            $resolution->height = 720;
        }
        //if it is taler than it is wide, flip the resolution
        if(is_array($resolution)){
            $tmp_res = new stdObject();
            $tmp_res->width = $resolution[0];
            $tmp_res->height = $resolution[1];
            $resolution = new stdObject();
            $resolution = $tmp_res;
        }
        else if($resolution->width > $resolution->height){
            $temp = $resolution->width;
            $resolution->width = $resolution->height;
            $resolution->height = $temp;
        }

        if(!is_object($resolution)){
            return false;
        }

        $results->res = $resolution;
    }

    else{
        return false;
    }
    //now sanatize the request params
    if($request_params !== null){
        //check for valid request name
        switch ($request_params->request_name) {
            case 'burnup':
            case 'heatmap':
            case 'artifact':
                break;
            default:
                return false;
        }
        //check for valid request type
        switch ($request_params->request_type) {
            case 'single':
            case 'class':
            case 'all':
                break;
            default:
                return false;
        }
        $results->request_params = $request_params;
    }
    else{
        return false;
    }
    //now sanatize the colorblind
    if($colorblind !== null){
        if(!is_bool($colorblind)){
            if(is_int($colorblind)){
                switch ($colorblind) {
                    case 1:
                        $colorblind = true;
                        break;
                    default:
                        $colorblind = false;
                        break;
                }
            }
            else{
                $colorblind = false;
            }
        }
        $results->colorblind = $colorblind;
    }
    else{
        return false;
    }
    //now sanatize the format param
    if($format !== null){
        switch ($format) {
            case 'string':
            case 'json':
                break;
            default:
                return false;
        }

        $results->format = $format;
    }
    else{
        return false;
    }
    return $results;
}
/**
 * A helper function for the block_projectgradeup_update_pgu_artifacts funciton
 * @param  array(object) $pre_artifacts An array of data streamed from the pre-artifact query
 * @param  object $course_info   An object containing the weights of the category and individual artifact
 * @return array                An array of artifact table records
 */
function block_projectgradeup_artifacts_per_user_helper($pre_artifacts, $course_info){
    $array_of_sudo_artifacts = [];
    //get the time to compare the user against
    $user_time = usertime(time());

    foreach ($pre_artifacts as $item) {
        if($user_time < $item->end_date && $item->grade == 0){
            $status = 'notdue';
        }
        else{
            if($item->grade == 0){
                $status = 'incomplete';
            }
            elseif($item->grade === null){
                $status = 'notgraded';
            }
            else{
                $status = 'completed';
            }
        }
        $sudo_artifact = new stdClass();
        $courseid = $item->course_id;
        $artifactid = $item->artifact_id;
        $sudo_artifact->grade = $item->grade;
        $sudo_artifact->weight = $course_info->$courseid->$artifactid->weight;
        $sudo_artifact->category = $course_info->$courseid->$artifactid->category_weight;
        $sudo_artifact->status = $status;
        $sudo_artifact->title = $item->artifact_name;
        $sudo_artifact->type = $item->artifact_type;
        $sudo_artifact->title_long = $item->artifact_name . $courseid . $artifactid;
        $sudo_artifact->class_id = $courseid;
        $sudo_artifact->user_id = $item->user_id;
        $sudo_artifact->artifact_id = $item->artifact_id;
        $array_of_sudo_artifacts[] = $sudo_artifact;
    }
    return $array_of_sudo_artifacts;
}

function block_projectgradeup_store_instance($course_id, $block_id){
    global $DB;
    //sql to only retreve items if our table does not contain them
    $block_sql = 'SELECT bi.id AS blockid,
                  FROM {block_instances}  AS bi
                       LEFT JOIN {block_projectgradeup} AS psb ON psb.blockid = bi.id
                  WHERE psb.blockid IS NULL
                        AND bi.blockname = :pgu
                        AND bi.id = :incoming_blockid';
    $blocks_not_in_bi = $DB->get_record_sql($sql, array('incoming_blockid' => $block_id, 'pgu' => 'projectgradeup'));
    //check and see if its empty
    if(!(count($blocks_not_in_bi)===0)){
        $block_instance = new stdClass();
        $block_instance->blockid = $block_id;
        $block_instance->courseid = $course_id;

        $DB->insert_record('block_projectgradeup', $block_instance);
        return true;
    }
    else{
        return false;
    }
}
/**
 * Creates the grede_categories object. This object contains all weight information for a course
 * @param  int $course_id The course id we are in
 * @return object $grade_categories An object containing all weight information for a course
 */
function block_projectgradeup_get_grade_categories($course_id){
    global $DB;
    //SQL to find the categories
    $course_category_sql = 'SELECT gc.id AS category_id,
                  gc.courseid AS course_id,
                  gc.fullname AS category_name,
                  gc.path AS path,
                  gc.depth AS depth,
                  gc.parent AS parent,
                  gc.aggregation AS aggregation_type,
                  gi.aggregationcoef AS weight,
                  gi.aggregationcoef2 AS weight2
            FROM {grade_items} AS gi
                JOIN {grade_categories} AS gc ON gc.id = gi.iteminstance
            WHERE gi.itemtype = :category
                AND gi.categoryid IS NULL
                AND gi.courseid = :courseid';

    $grade_categories = new stdClass();
    $grade_categories->$course_id = new stdClass();
    $grade_categories->$course_id->category = new stdClass();
    //get the data-items for this course
    $artifact_order = block_projectgradeup_extend_get_grade_categories($course_id);//$DB->get_records_sql($course_artifact_sql, array('courseid' => $course_id));
    $course_order = $DB->get_records_sql($course_category_sql, array('category'=>'category', 'courseid' => $course_id));
    //loop through the artifacts
    foreach($artifact_order as $id => $record) {
        $artifact_id = $record->artifact_id;
        //create and seet the stdClass object
        $grade_categories->$course_id->$artifact_id = new stdClass();
        $grade_categories->$course_id->$artifact_id->name = $record->artifact_name;
        $curr_category = $DB->get_record('grade_categories', array('courseid' => $course_id, 'id' => $record->artifact_type_category_id));
        $weight =  block_projectgradeup_get_aggregation_col($curr_category->id, $course_id, $curr_category->parent, $record->artifact_category_weight,
                    $record->artifact_category_weight2, $record->artifact_id);
        $grade_categories->$course_id->$artifact_id->weight = $weight;

        $grade_categories->$course_id->$artifact_id->course_id = $course_id;
        //if the artifact is at the root, go ahead and continue block_projectgradeup_get_aggregation_col
        if($record->artifact_type_category_name == '?'){
            //the category weight is itself
            $grade_categories->$course_id->$artifact_id->category_weight = 1;
            $grade_categories->$course_id->$artifact_id->total_weight = $weight;
        }
        else {
            //otherwise set the weight to the sum of the category weights
            $grade_categories->$course_id->$artifact_id->total_weight = $weight *
                block_projectgradeup_get_artifact_course_weight($current_category=null,$record, $course_order);
            $grade_categories->$course_id->$artifact_id->category_weight =
                block_projectgradeup_get_artifact_course_weight($current_category=null, $record, $course_order);
        }
    }
    return $grade_categories;

}
/**
 * block_projectgradeup_extend_get_grade_categories - The helper fucntion to get grade categories
 * @param int $course_id The course in which to pull the artifact data
 * @return array(object) An array of category objects
 */
function block_projectgradeup_extend_get_grade_categories($course_id){
    global $DB;
    //assignments
    $course_artifact_sql_assignments = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.allowsubmissionsfromdate AS start_date,
              a.duedate AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {assign} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND gi.itemmodule = :thistype
        AND ctx.instanceid = c.id
        AND gc.fullname IS NOT NULL';
    $artifact_order_assignments = $DB->get_records_sql($course_artifact_sql_assignments, array('courseid' => $course_id, 'thistype' => 'assign'));
    //quizs
    $course_artifact_sql_quiz = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.timeopen AS start_date,
              a.timeclose AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {quiz} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
        AND gc.fullname IS NOT NULL';
    $artifact_order_quiz = $DB->get_records_sql($course_artifact_sql_quiz, array('courseid' => $course_id, 'thistype' => 'quiz'));
    //workshop assessments
    $course_artifact_sql_workshop_a = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.assessmentstart AS start_date,
              a.assessmentend AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {workshop} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
        AND gc.fullname IS NOT NULL
        AND gi.itemname LIKE "%(assessment)%"';
    $artifact_order_workshop_a = $DB->get_records_sql($course_artifact_sql_workshop_a, array('courseid' => $course_id, 'thistype' => 'workshop'));
    //workshop submissions
    $course_artifact_sql_workshop_s = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.submissionstart AS start_date,
              a.submissionend AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {workshop} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gc.fullname IS NOT NULL
        AND gi.itemmodule = :thistype
        AND gi.itemname LIKE "%(submission)%"';
    $artifact_order_workshop_s = $DB->get_records_sql($course_artifact_sql_workshop_s, array('courseid' => $course_id, 'thistype' => 'workshop'));
    //lessons
    $course_artifact_sql_lesson = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.available AS start_date,
              a.deadline AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {lesson} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
        AND gc.fullname IS NOT NULL';
    $artifact_order_lesson = $DB->get_records_sql($course_artifact_sql_lesson, array('courseid' => $course_id, 'thistype' => 'lesson'));
    //forum
    $course_artifact_sql_forum = 'SELECT DISTINCT  gi.id AS artifact_id,
              c.id AS course_id,
              gi.itemname AS artifact_name,
              gi.categoryid AS artifact_type_category_id,
              gc.fullname AS artifact_type_category_name,
              gc.aggregation AS aggregation_type,
              gi.itemmodule AS artifact_type,
              a.assesstimestart AS start_date,
              a.assesstimefinish AS end_date,
              gi.aggregationcoef2 AS artifact_category_weight2,
              gi.aggregationcoef AS artifact_category_weight
    FROM {role_assignments} AS ra
        JOIN {role} AS r ON r.id = ra.roleid
        JOIN {context} AS ctx ON ctx.id = ra.contextid
        JOIN {course} AS c ON (c.id = ctx.instanceid)
        JOIN {grade_items} AS gi ON c.id = gi.courseid
        JOIN {forum} AS a ON a.id = gi.iteminstance
        LEFT JOIN {grade_categories} AS gc ON gc.id = gi.categoryid
    WHERE c.id = :courseid
        AND ra.contextid = ctx.id
        AND ctx.instanceid = c.id
        AND gi.itemmodule = :thistype
        AND gc.fullname IS NOT NULL';
    $artifact_order_forum = $DB->get_records_sql($course_artifact_sql_forum, array('courseid' => $course_id, 'thistype' => 'forum'));

    return array_merge($artifact_order_assignments, $artifact_order_quiz, $artifact_order_workshop_a, $artifact_order_workshop_s, $artifact_order_lesson, $artifact_order_forum);
}

/**
 * A simple object/array explode funciton for debug
 * @param  The incoming object $val The object to display
 * @return void
 */
function print_r2($val){
    echo '<pre>';
    print_r($val);
    echo  '</pre>';
}

/**
 * A simple helper function for dumping objects into the error log
 *
 * @param The incomming object that will be printed to the error log
 *
 */
function var_error_log( $object=null ){
    ob_start();                    // start buffer capture
    var_dump( $object );           // dump the values
    $contents = ob_get_contents(); // put the buffer into a variable
    ob_end_clean();                // end capture
    error_log( $contents );        // log contents of the result of var_dump( $object )
}

/**
 * The recursive funciton which finds the weight of an artifact or category
 * @param  object $current_category The current categories information
 * @param  object $current_artifact The current artifact we are attempting to find
 * @param  array(object) $categories       An array of all the categories within a course
 * @return int                   The weight of the artifact
 */
function block_projectgradeup_get_artifact_course_weight($current_category, $current_artifact, $categories){
    if($current_category == null){
        $current_category = new stdClass();
        //if the category cannot be found, return false
        $current_category = block_projectgradeup_get_category_from_id($current_artifact->artifact_type_category_id, $categories);
    }
    //if we are at the bottom
    if($current_category->depth == 2){
        //$category_weight = block_projectgradeup_get_grade_category_weights($current_category);
        //block_projectgradeup_get_aggregation_col
        $weight = block_projectgradeup_get_aggregation_col($current_category->category_id, $current_category->course_id, $current_category->parent, $current_category->weight, $current_category->weight2);
        return $weight;
    }
    else{
        $category_weight = block_projectgradeup_get_grade_category_weights($current_category);
            if($current_artifact->artifact_type_category_id == 5){
        }
        //if we are now at the bottom
        if($category_weight->depth == 2){
            $weight = block_projectgradeup_get_aggregation_col($current_category->category_id, $current_category->course_id, $current_category->parent, $current_category->weight, $current_category->weight2);
            return $weight * $category_weight->weight;
        }
        //next
        $current_category = block_projectgradeup_get_category_from_id($category_weight->parent, $categories);
        //otherwise recurse
        return ($category_weight->weight) *
            block_projectgradeup_get_artifact_course_weight($current_category, $current_artifact, $categories);
    }
}

/**
 * Gets the category from its id
 * @param  int $category_id   The category id
 * @param  array($category ) $category_list A list of categories in which lies
 * the category we seek
 * @return stdClass($category)                The category we seek, otherwise false
 */
function block_projectgradeup_get_category_from_id($category_id, $category_list){
    $results = false;
    foreach ($category_list as $item) {
        if($item->category_id == $category_id){
            $results = $item;
            break;
        }
    }
    return $results;
}

/**
 * Gets the category weights by calculating the number of adjacent categories
 * @param  stdObject::$category $current_category The current category
 * @param  array($category) $grade_categories The array of categorys to calculate
 * against
 * @return array([the calculated weight],[the parent node], [the current depth])
 * An asso. array containing the calculated weight, the category's parent node, and finally its depth
 */
function block_projectgradeup_get_grade_category_weights($current_category){
    global $DB;
    //find all categories who share the same parent
    $SQL = 'SELECT gc.id AS category_id,
                    gc.fullname AS category_name,
                    gc.courseid AS category_course,
                  gc.path,
                  gc.depth,
                  gc.parent AS parent,
                  gi.aggregationcoef2  as weight2,
                  gi.aggregationcoef as weight
            FROM {grade_items} AS gi
                JOIN {grade_categories} AS gc ON gc.id = gi.iteminstance
            WHERE gi.itemtype = :category
                AND gi.categoryid IS NULL
                AND gc.id = :parentid';
    //hand off the new category object
    $new_category = $DB->get_record_sql($SQL, array('category' => 'category', 'parentid' => $current_category->parent));

    //block_projectgradeup_get_aggregation_col
    $weight = block_projectgradeup_get_aggregation_col($new_category->category_id, $new_category->category_course, $new_category->parent, $new_category->weight, $new_category->weight2);
    $DB->get_record_sql($SQL, array('category' => 'category', 'parentid' => $current_category->parent));
    $depth = $new_category->depth;
    //$weight = $new_category->weight;
    $parent = $new_category->parent;
    $result = new stdClass();
    $result->weight = $weight;
    $result->parent = $parent;
    $result->depth = $depth;
    return $result;
}
/**
 * The funciton which updates the class_date_time table
 * @param  null $args Nothing, only here for extensibility
 * @return void       Nothing at all
 */
function block_projectgradeup_update_class_date_time($args = null){
    global $DB;
    //get the courses
    $courses = $DB->get_records('block_projectgradeup');
    $class_date_time_sql = 'SELECT c.id as class_id,
                                   c.fullname as class_long_name,
                                   c.shortname as class_short_name,
                                   c.idnumber as class_number,
                                   c.startdate as class_start_date
                            FROM {course} as c
                            WHERE c.id = :courseid';
    $array_of_class_date_time = [];

    //update the class date times
    foreach($courses as $item){
        $SQL = 'class_id = :courseid';
        $DB->delete_records_select('pgu_class_date_time', $SQL, array('courseid' => $item->course_id));
        $course_date_time_found = $DB->get_record_sql($class_date_time_sql, array('courseid'=>$item->course_id));
        $sudo_class_date_time = new stdClass();
        $sudo_class_date_time->class_long_name = $course_date_time_found->class_long_name;
        $sudo_class_date_time->class_start_date = $course_date_time_found->class_start_date;
        $sudo_class_date_time->class_number = $course_date_time_found->class_number;
        $sudo_class_date_time->class_short_name = $course_date_time_found->class_short_name;
        $sudo_class_date_time->class_end_date = $item->course_end_date;
        $sudo_class_date_time->class_difficulty = $item->course_difficulty;//a teacher can modify this value
        $sudo_class_date_time->class_id = $course_date_time_found->class_id;
        $array_of_class_date_time[] = $sudo_class_date_time;
    }
    $DB->insert_records('pgu_class_date_time', $array_of_class_date_time);
}
/**
 * A simple helper function that gets the key from an array
 * @param  array  $array The array from which to get the key
 * @param  mixed $key   The key we seek
 * @return mixed        The key in the array, if it exists, otherwise false
 */
function block_projectgradeup_get_key_from_array($array, $key){
    $results = false;
    if(is_string($key)){
        foreach($array as $item){
            $results[] = $item->$key;
        }
    }
    return $results;
}

/**
 * Update the tables on block_table absence
 * @param  boolean $is_forced Forces a check on artifact types and difficulties (optional)
 * @return boolean             True once compplete
 */
function block_projectgradeup_update_tables_on_block_table_absence($is_forced = false){
    global $DB;
    //next we update the artifacts table
    $artifact_sql = 'SELECT DISTINCT a.class_id AS course_id
                  FROM {pgu_artifacts}  AS a
                       LEFT JOIN {block_projectgradeup} AS psb ON a.class_id = psb.course_id
                  WHERE psb.course_id IS NULL';
    //update the artifacts table
    $course_to_remove_from_artifacts = $DB->get_records_sql($artifact_sql);
    foreach ($course_to_remove_from_artifacts as $item) {
        $SQL = 'class_id = :courseid';
        $DB->delete_records_select('pgu_artifacts', $SQL, array('courseid'=>$item->course_id));
    }
    //now for the aratifact and course times tables? (see if you can merge into one.... (artifacts and stored_blocks as one))
    $artifact_time_sql = 'SELECT DISTINCT adt.class_id AS course_id
                      FROM {pgu_artifact_date_time} AS adt
                            LEFT JOIN {block_projectgradeup} AS psb ON adt.class_id = psb.course_id
                      WHERE psb.course_id IS NULL';
    $course_to_remove_from_artifact_date_time = $DB->get_records_sql($artifact_time_sql);
    //delete the rows in pgu_artifact_date_time
    foreach ($course_to_remove_from_artifacts as $item) {
        $SQL = 'class_id = :courseid';
        $DB->delete_records_select('pgu_artifact_date_time', $SQL, array('courseid'=>$item->course_id));
    }
    //now the class date time
    $class_date_time_sql = 'SELECT cdt.class_id AS course_id
                            FROM {pgu_class_date_time} AS cdt
                                    LEFT JOIN {block_projectgradeup} AS psb ON cdt.class_id = psb.course_id
                            WHERE psb.course_id IS NULL';
    $course_to_remove_from_class_date_time = $DB->get_records_sql($class_date_time_sql);
    //delete the rows in pgu_class_date_time
    foreach ($course_to_remove_from_artifacts as $item) {
        $SQL = 'class_id = :courseid';
        $DB->delete_records_select('pgu_artifact_date_time', $SQL, array('courseid'=>$item->course_id));
    }
    //execute the following queries only IF the admin as allowed types and course specific types
    $use_suffixes = get_config('projectgradeup', 'Use_Course_Lvl_Suffix');
    $suffixes_enabled = get_config('projectgradeup', 'Use_Suffix');
    if(($use_suffixes == 1 && $suffixes_enabled == 1) || $is_forced){
        //now artifact types
        $artifact_types_sql = 'SELECT DISTINCT at.course_id AS course_id
                                                FROM {pgu_artifact_types} AS at
                                                	LEFT JOIN {block_projectgradeup} AS psb ON at.course_id = psb.course_id
                                                WHERE psb.course_id IS NULL';
        $course_to_remove_from_artifact_types = $DB->get_records_sql($artifact_types_sql);
        foreach($course_to_remove_from_artifact_types as $item){
            $SQL = 'course_id = :courseid';
            $DB->delete_records_select('pgu_artifact_types', $SQL, array('courseid' => $item->course_id));
        }
        //now artifact difficulty
        $artifact_diff_sql = 'SELECT DISTINCT ad.course_id AS course_id
                                                FROM {pgu_artifact_difficulty} AS ad
                                                	LEFT JOIN {block_projectgradeup} AS psb ON ad.course_id = psb.course_id
                                                WHERE psb.course_id IS NULL';
        $course_to_remove_from_artifact_diff = $DB->get_records_sql($artifact_diff_sql);
        foreach($course_to_remove_from_artifact_diff as $item){
            $SQL = 'course_id = :courseid';
            $DB->delete_records_select('pgu_artifact_types', $SQL, array('courseid' => $item->course_id));
        }
    }
    return true;
}
