<?php
require_once("{$CFG->libdir}/formslib.php");

class set_difficulties_form extends moodleform {
    function definition() {
        $mform =& $this->_form;
        //add the hidden form
        $mform->addElement('hidden','courseid');
        //The difficulity name
        $mform->addElement('text', 'difficultyname', get_string('difficultyname', 'block_projectgradeup'));
        $mform->setType('difficultyname', PARAM_NOTAGS);
        $mform->addRule('difficultyname', null, 'required', null, 'client');
        //The difficulty suffix
        $mform->addElement('text', 'difficultysuffix', get_string('difficultysuffix', 'block_projectgradeup'));
        $mform->setType('difficultysuffix', PARAM_NOTAGS);
        $mform->addRule('difficultysuffix', get_string('maximumchars', 'block_projectgradeup'), 'maxlength', 5, 'client');
        $mform->addRule('difficultysuffix', null, 'required', null, 'client');
        //The difficulty amnt, can only be 1-5
        $mform->addElement('text', 'difficultydifficulty', get_string('difficultydifficulty', 'block_projectgradeup'));
        $mform->setType('difficultydifficulty', PARAM_INT);
        $mform->addRule('difficultydifficulty', get_string('maximumchars', 'block_projectgradeup'), 'maxlength', 5, 'client');
        $mform->addRule('difficultydifficulty', null, 'required', null, 'client');
        $mform->addRule('difficultydifficulty', null, 'numeric', null, 'client');
        //add the hidden form
        $mform->addElement('hidden','courseid');
        $mform->addElement('hidden','type');
        $this->add_action_buttons();
    }
}
