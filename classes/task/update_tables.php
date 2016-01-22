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
 * @package block_projectgradeup
 * @copyright 2015 Dalin Williams
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace block_projectgradeup\task;

class update_tables extends \core\task\scheduled_task  {

    public function get_name()
    {
        //shows the name in admin screens
        return get_string('update_tables', 'block_projectgradeup');
    }


    public function execute(){
        global $CFG;
        require_once($CFG->dirroot . '/block/projectgradeup/lib.php');

        //run updates on tables
        block_projectgradeup_update_on_all_users();
        block_projectgradeup_update_and_delete_all_on_course();
        block_projectgradeup_update_class_date_time();
        block_projectgradeup_update_pgu_artifacts();
    }
}
