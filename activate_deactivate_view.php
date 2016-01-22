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

require_once('../../config.php');
require_once('lib.php');

global $DB, $OUTPUT, $PAGE, $CORUSE, $CFG;

// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);
$blockid = required_param('blockid', PARAM_INT);

// Next look for optional variables.
$id = optional_param('id', 0, PARAM_INT);

$context = context_course::instance($courseid);

//set the courseid if it does not exist
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_projectgradeup', $courseid);
}

//###################################
//###################################
//###################################
//testing this function
//block_update_artifact_date_time_stats();
/*require_once('heat_map.class.php');
$test = new \classes\heat_map(array(1080,720),new DateTime(2015-09-10),2,2);*/
//block_projectgradeup_update_pgu_artifacts_update();
/*require_once('./model/defined_data_layer.class.php');

$test =new \datalayermodel\defined_data_layer(2, 2);

print_r2($test->get_class_average(array(1080,720)));*/

block_projectgradeup_get_artifacts(2,2);

//###################################
//###################################
//###################################

//teachers only
require_login($course);
require_capability('block/projectgradeup:teachersettings', $context);

//set up the page
$PAGE->set_url('/blocks/projectgradeup/view.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('teacherssettingsheading', 'block_projectgradeup'));
$PAGE->set_heading(get_string('teacherssettingsheading', 'block_projectgradeup'));
$settingsnode = $PAGE->settingsnav->add(get_string('porjectgradeupsettings', 'block_projectgradeup'));
$editurl = new moodle_url('/blocks/projectgradeup/activate_deactivate_view.php', array('id' => $id, 'blockid' => $blockid, 'courseid' => $courseid));
$editnode = $settingsnode->add(get_string('activatedeactivate', 'block_projectgradeup'), $editurl);
$editnode->make_active();

//path indicator
$activate = true;

//if the course is not activated
if(!$is_activated = $DB->get_record('block_projectgradeup', array('course_id' => $courseid))){
    require_once('activate_form.php');
    $activation_status = new activate_form();
}
//otherwise de-activate
else{
    require_once('deactivate_form.php');
    $activation_status = new deactivate_form();
    $activate = false;
}

//set the hidden fields
$toform['courseid'] = $courseid;
$toform['blockid'] = $blockid;
$activation_status->set_data($toform);

if($activation_status->is_cancelled()) {
    // Cancelled forms redirect to the originating course
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($courseurl);
} else if ($fromform = $activation_status->get_data()) {
    //if it is the deactivate from
    if(!$activate){
        if($fromform->is_active == 0){
            //delete this entry in the respective table
            $SQL = "course_id = :course_id";
            $DB->delete_records_select('block_projectgradeup', $SQL, array('course_id' => $courseid));
        }
    }
    //otherwise it is the activate 1forum
    else{
        $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
        if($fromform->isnot_active == 1){
            $to_insert = new stdClass();
            $to_insert->block_id = $blockid;
            $to_insert->course_id = $courseid;
            //clean incomming data
            $to_insert->course_difficulty = ((is_int($fromform->coursedifficulty) && is_float($fromform->coursedifficulty)) || $fromform->coursedifficulty==null || ($fromform->coursedifficulty > 5 || $fromform->coursedifficulty < 1)) ? 3 : round($fromform->coursedifficulty);
            $to_insert->allow_dual_graphs = (get_config('projectgradeup', 'Allow_Both_HeatMap_Charts') == 1)? $fromform->teachermultigraphsallow : 0;
            $check_date = $DB->get_record('course', array('id' => $courseid));
            //make sure the date is set before its respective course
            $to_insert->course_end_date = ($check_date->startdate < $fromform->courseenddate) ? $fromform->courseenddate : null;
            $to_insert->override_duration = 0;//$fromform->override_duration;
            //searalize default duration
            $to_insert->default_duration = block_projectgradeup_searalize_duration($to_insert->override_duration, $to_insert->course_end_date, $fromform->override_duration, $courseid);
            //print_r2($to_insert);
            $DB->insert_record('block_projectgradeup', $to_insert);
            //update the class_date_time table as well
            block_projectgradeup_update_class_date_time();
            //force update of all needed tables, remember update_pgu_artifacts() contains update_pgu_artifact_date_times()
            block_projectgradeup_update_pgu_artifacts(true);
        }
        //now back to the course
        redirect($courseurl);

    }
} else {
    // form didn't validate or this is the first display
    $site = get_site();
    echo $OUTPUT->header();
    $activation_status->display();
    echo $OUTPUT->footer();
}

?>
