<?php
class block_projectgradeup_edit_form extends block_edit_form{
     protected function specific_definition($mform){
         //delare the section header
         $mform->addElement('header', 'configheader', get_string('blocksettings', 'block_projectgradeup'));
         // A sample string variable with a default value.
         $mform->addElement('text', 'config_title', get_string('blocktitle', 'block_projectgradeup'));
         //$mform->setDefault('config_title', get_string('defaultvalue', 'block_projectgradeup'));
         $mform->setType('config_title', PARAM_TEXT);
         $mform->setDefault('config_title', get_string('blockstring', 'block_projectgradeup'));
     }
}
