<?php
require_once("{$CFG->libdir}/formslib.php");

class set_types_form extends moodleform {
    function definition() {
        $mform =& $this->_form;
        //add the hidden form
        $mform->addElement('hidden','courseid');
        //set the types name
        $mform->addElement('text', 'typesname', get_string('typesname', 'block_projectgradeup'));
        $mform->setType('typesname', PARAM_NOTAGS);
        $mform->addRule('typesname', null, 'required', null, 'client');
        //The suffix field
        $mform->addElement('text', 'typessuffix', get_string('typessuffix', 'block_projectgradeup'));
        $mform->setType('typessuffix', PARAM_NOTAGS);
        $mform->addRule('typessuffix', get_string('maximumchars', 'block_projectgradeup'), 'maxlength', 5, 'client');
        $mform->addRule('typessuffix', null, 'required', null, 'client');
        //The duration field
        $mform->addElement('text', 'typesduration', get_string('typesduration', 'block_projectgradeup'));
        $mform->setType('typesduration', PARAM_INT);
        $mform->addRule('typesduration', get_string('maximumchars', 'block_projectgradeup'), 'maxlength', 5, 'client');
        $mform->addRule('typesduration', null, 'required', null, 'client');
        $mform->addRule('typesduration', null, 'numeric', null, 'client');
        //add the hidden form
        $mform->addElement('hidden','courseid');
        $mform->addElement('hidden','type');
        $this->add_action_buttons();
    }
}
