<?php
require_once("{$CFG->libdir}/formslib.php");

class activate_form extends moodleform {
    function definition() {
        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('activatecourse', 'block_projectgradeup'));
        //add activation checkbox
        $mform->addElement('advcheckbox', 'isnot_active', get_string('activatecourse', 'block_projectgradeup'));
        $mform->setDefault('isnot_active', 0);
        //add the field for the course difficulty
        $mform->addElement('text', 'coursedifficulty', get_string('coursedifficulty', 'block_projectgradeup'));
        $mform->setDefault('coursedifficulty', 3);
        $mform->setType('coursedifficulty', PARAM_INT);
        //add the field for the end date display
        $mform->addElement('date_selector', 'courseenddate', get_string('to'));
        //testing and prototype functionality, not intended for use
        $future = false;
        if($future){
            //add overrides for the artifact duration
            $mform->addElement('advcheckbox', 'override_duration', get_string('overrideduration', 'block_projectgradeup'));
            $mform->setDefault('override_duration', 0);
            //add default duration value
            $mform->addElement('text', 'durationoverride', get_string('duration_override', 'block_projectgradeup'));
            $mform->setType('durationoverride', PARAM_INT);
        }
        //add the field for the allowance or not of multiple graphs IF it is available
        if(get_config('projectgradeup', 'Allow_Both_HeatMap_Charts') == 1){
            $mform->addElement('advcheckbox', 'teachermultigraphsallow', get_string('teachermultigraphsallow', 'block_projectgradeup'));
        }
        //add the hidden form
        $mform->addElement('hidden','courseid');
        $mform->addElement('hidden','blockid');
        //set the action buttons
        $this->add_action_buttons();
    }
}
