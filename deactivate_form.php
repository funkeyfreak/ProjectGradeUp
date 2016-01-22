<?php
require_once("{$CFG->libdir}/formslib.php");

class deactivate_form extends moodleform {
    function definition() {

        $mform =& $this->_form;
        $mform->addElement('header','displayinfo', get_string('deactivatecourse', 'block_projectgradeup'));
        //add activation checkbox
        $mform->addElement('advcheckbox', 'is_active', get_string('deactivatecourse', 'block_projectgradeup'));
        $mform->setDefault('is_active', 1);
        //add the hidden form
        $mform->addElement('hidden','courseid');
        $mform->addElement('hidden','blockid');
        $this->add_action_buttons();
    }
}
