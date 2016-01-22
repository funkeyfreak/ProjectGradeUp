<?php
require_once("{$CFG->libdir}/formslib.php");

class settings_form extends moodleform {
    function definition() {

        $mform =& $this->_form;
        // add group for text areas
        $mform->addElement('header','displayinfo', get_string('activatecourse', 'block_projectgradeup'));

        // add page title element.
        $mform->addElement('text', 'pagetitle', get_string('pagetitle', 'block_projectgradeup'));
        $mform->addRule('pagetitle', null, 'required', null, 'client');

        // add display text field
        $mform->addElement('htmleditor', 'displaytext', get_string('displayedhtml', 'block_projectgradeup'));
        $mform->setType('displaytexttext', PARAM_RAW);
        $mform->addRule('displaytext', null, 'required', null, 'client');
        // add filename selection.
        $mform->addElement('filepicker', 'filename', get_string('file'), null, array('accepted_types' => '*'));

        $mform->addGroup($radioarray, 'radioar', get_string('pictureselect', 'block_projectgradeup'), array(' '), FALSE);
        // // add display picture yes / no option
        $mform->addElement('selectyesno', 'displaypicture', get_string('displaypicture', 'block_projectgradeup'));
        $mform->setDefault('displaypicture', 1);
        // add description field
        /*$attributes = array('size' => '50', 'maxlength' => '100');
        $mform->addElement('text', 'description', get_string('picturedesc', 'block_projectgradeup'), $attributes);
        $mform->setType('description', PARAM_TEXT);*/
        // add optional grouping
        $mform->addElement('header', 'optional', get_string('optional', 'form'), null, false);
        // add date_time selector in optional area
        $mform->addElement('date_time_selector', 'displaydate', get_string('displaydate', 'block_projectgradeup'), array('optional' => true));
        $mform->setAdvanced('optional');


        $this->add_action_buttons();
    }
}
