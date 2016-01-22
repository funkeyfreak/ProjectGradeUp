<?php
require_once('../../config.php');
require_once('lib.php');

global $DB, $OUTPUT, $PAGE, $CORUSE;

// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);
$operation = required_param('type', PARAM_NOTAGS);
// Next look for optional variables.
$id = optional_param('id', 0, PARAM_INT);

$context = context_course::instance($courseid);

//set the courseid if it does not exist
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_projectgradeup', $courseid);
}

//teachers only
require_login($course);
require_capability('block/projectgradeup:teachersettings', $context);

//set up the page
$PAGE->set_url('/blocks/projectgradeup/view.php', array('id' => $courseid));
$PAGE->set_title(get_string('teacherssettingsheading', 'block_projectgradeup'));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('teacherssettingsheading', 'block_projectgradeup'));
$settingsnode = $PAGE->settingsnav->add(get_string('porjectgradeupsettings', 'block_projectgradeup'));
$editurl = new moodle_url('/blocks/projectgradeup/set_view.php', array('id' => $id, 'courseid' => $courseid, 'type' => $operation));
$editnode = $settingsnode->add(get_string('setcreate', 'block_projectgradeup'), $editurl);
$editnode->make_active();

//if the course is not activated
if($operation === 'difficulties'){
    require_once('set_difficulties_form.php');
    $set_operation = new set_difficulties_form();
}
//otherwise de-activate
else if ($operation === 'types'){
    require_once('set_types_form.php');
    $set_operation = new set_types_form();
}
//if someone tries to go anywhere else but to the two paramed locations, STOP THEM
else{
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($courseurl);
}

//set the hidden fields
$toform['courseid'] = $courseid;
$toform['type'] = $operation;
$set_operation->set_data($toform);


if($set_operation->is_cancelled()) {
    // Cancelled forms redirect to the originating course
    $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
    redirect($courseurl);
} else if ($fromform = $set_operation->get_data()) {
    //if it setting the types
    if($operation === 'types'){
        //if this type is not set, we can submit
        if(!is_suffix_set_types($fromform->typessuffix, $courseid)){
            $to_insert = new stdClass();
            $to_insert->type = $fromform->typesname;
            $to_insert->suffix = $fromform->typessuffix;
            $to_insert->duration = $fromform->typesduration;
            $to_insert->course_id = $courseid;
            $DB->insert_record('pgu_artifact_types', $to_insert);
            $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
            redirect($courseurl);
        }
        //otherwise cry and re-display
        else{
            print_error('suffixexiststypes', 'blocks_projectgradeup', $operation);
        }
    }
    //otherwise it is the activate forum
    else if($operation === 'difficulties'){
        //if this difficulty is not set, we can submit
        if(!is_suffix_set_difficulties($fromform->typessuffix, $courseid)){
            $to_insert = new stdClass();
            $to_insert->type = $fromform->difficultyname;
            $to_insert->suffix = $fromform->difficultysuffix;
            $to_insert->course_id = $courseid;
            $to_insert->difficulty = $fromform->difficultydifficulty;
            $DB->insert_record('pgu_artifact_difficulty', $to_insert);
            $courseurl = new moodle_url('/course/view.php', array('id' => $courseid));
            redirect($courseurl);
        }
        //otherwise cry and re-display
        else{
            print_error('suffixexistsdifficulties', 'blocks_projectgradeup', $operation);
        }
    }
    else{
        print_error('invalidoperation', 'blocks_projectgradeup', $operation);
    }
} else {
    // form didn't validate or this is the first display
    $site = get_site();
    echo $OUTPUT->header();
    $set_operation->display();
    echo $OUTPUT->footer();
}

?>
