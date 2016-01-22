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
 * The main class for the block projectgradeup
 *
 *
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @version 1.0.0
 * @package Project Grade Up
 * @copyright 2015 Dalin Williams
 */
class block_projectgradeup extends block_base {
    public function init() {
        $this->title = get_string('projectgradeup', 'block_projectgradeup');
    }

    public function get_content() {
        global $COURSE;
        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        $course_cur = $this->page->course;
        $context = context_course::instance($COURSE->id);
        $url = new moodle_url('/blocks/projectgradeup/view.php', array('id' => $course_cur->id, 'blockid' => $this->instance->id, 'courseid' => $COURSE->id));
        //$url2 = new moodle_url('/blocks/projectgradeup/settings_view.php', array('id' => $course_cur->id, 'blockid' => $this->instance->id, 'courseid' => $COURSE->id), '', array('target'=>'_blank'));
        $url2 = new moodle_url('/blocks/projectgradeup/set_view.php', array('id' => $course_cur->id, 'courseid' => $COURSE->id, 'type' => 'types'));
        $url3 = new moodle_url('/blocks/projectgradeup/set_view.php', array('id' => $course_cur->id, 'courseid' => $COURSE->id, 'type' => 'difficulties'));
        $url4 = new moodle_url('/blocks/projectgradeup/activate_deactivate_view.php', array('id' => $course_cur->id, 'blockid' => $this->instance->id, 'courseid' => $COURSE->id));
        $this->content->text   = get_string('welcometext', 'block_projectgradeup');

        if(has_capability('block/projectgradeup:teachersettings', $context)){
            /*$this->content->text .= html_writer::start_tag('li');
            $this->content->text .= html_writer::link($url2, get_string('viewsettings', 'block_projectgradeup'));
            $this->content->text .= html_writer::end_tag('li');*/
            $this->content->text .= html_writer::start_tag('li');
            $this->content->text .= html_writer::link($url4, get_string('activatedeactivate', 'block_projectgradeup'));
            $this->content->text .= html_writer::end_tag('li');
            //check the status of suffix usage
            $use_suffixes = get_config('projectgradeup', 'Use_Course_Lvl_Suffix');
            $suffixes_enabled = get_config('projectgradeup', 'Use_Suffix');

            //if the teacher is allowed to use suffixes
            if($use_suffixes == 1 && $suffixes_enabled == 1){
                $this->content->text .= html_writer::start_tag('li');
                $this->content->text .= html_writer::link($url2, get_string('linktotypes', 'block_projectgradeup'));
                $this->content->text .= html_writer::end_tag('li');
                $this->content->text .= html_writer::start_tag('li');
                $this->content->text .= html_writer::link($url3, get_string('linktodifficulties', 'block_projectgradeup'));
                $this->content->text .= html_writer::end_tag('li');
            }
        }
        $this->content->footer = html_writer::link($url, get_string('viewstudentcharts', 'block_projectgradeup'));




        return $this->content;
    }
    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('defaulttitle', 'block_projectgradeup');
            }
            else {
                $this->title = $this->config->title;
            }

            if (empty($this->config->text)) {
                $this->config->text = get_string('defaulttext', 'block_projectgradeup');
            }
        }
    }

    public function instance_allow_multiple() {
        return true;
    }

    /*public function cron() {
        mtrace( "Hey, my cron script is running" );

        // do something

        return true;
    }*/


    /*public function cron() {

        global $DB; // Global database object

        // Get the instances of the block
        $instances = $DB->get_records( 'block_instances', array('blockname'=>'simplehtml') );

        // Iterate over the instances
        foreach ($instances as $instance) {

            // Recreate block object
            $block = block_instance('simplehtml', $instance);

            // $block is now the equivalent of $this in 'normal' block
            // usage, e.g.
            // this is where i will change my context/update the data
            $someconfigitem = $block->config->item2;
        }
    }*/



    public function applicable_formats() {
        return array('course-view' => true,
                     'course-view-social' => false);
    }

    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $attributes['class'] .= ' block_'. $this->name(); // Append our class to class attribute
        return $attributes;
    }

    /*public function hide_header() {
        return true;
    }*/

    public function instance_config_save($data, $nolongerused = false) {
        if(get_config('block_projectgradeup', 'Allow_HTML') == '1') {
            $data->text = strip_tags($data->text);
        }

        // And now forward to the default implementation defined in the parent class
        return parent::instance_config_save($data);
    }
    function has_config() {return true;}
}
