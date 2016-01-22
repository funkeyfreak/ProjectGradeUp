<?php
require_once('../../config.php');
require_once('projectgradeup_form.php');

global $DB, $OUTPUT, $PAGE;

// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);
$blockid =  required_param('blockid', PARAM_INT);

// Next look for optional variables.
$id = optional_param('id', 0, PARAM_INT);

$this->context = context_course::instance($courseid);

//make sure the course is real
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_projectgradeup', $courseid);
}

//teachers only
require_login($course);
require_capability('block/projectgradeup:teachersettings', $this->context);

//page setup
$PAGE->set_url('/blocks/projectgradeup/view.php', array('id' => $courseid));
$PAGE->set_pagelayout('standard');
$PAGE->set_heading(get_string('teacherssettingsheading', 'block_projectgradeup'));
$settingsnode = $PAGE->settingsnav->add(get_string('porjectgradeupsettings', 'block_projectgradeup'));
$editurl = new moodle_url('/blocks/simplehtml/view.php', array('id' => $id, 'courseid' => $courseid, 'blockid' => $blockid));
$editnode = $settingsnode->add(get_string('editpage', 'block_projectgradeup'), $editurl);
$editnode->make_active();

//manage the settings
$settings = new settings_form();

/*Dont think I need this....
$toform['blockid'] = $blockid;
$toform['courseid'] = $courseid;
$settings->set_data($toform);*/

//handle button events
if($settings->is_cancelled()) {
    // Cancelled forms redirect to the course main page.
    $courseurl = new moodle_url('/course/view.php', array('id' => $id));
    redirect($courseurl);
} else if ($fromform = $settings->get_data()) {
    // We need to add code to appropriately act on and store the submitted data
    print_object($fromform);
} else {
    // form didn't validate or this is the first display
    $site = get_site();
    echo $OUTPUT->header();
    $settings->display();
    echo $OUTPUT->footer();
}
?>
