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
 * PHPUnit integration tests
 *
 * @package    Project Grade-Up
 * @category   blocks
 * @copyright  2015 Dalin Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once('./burnup6666666666.class.php');

$resolution = [1080,720];
$user_time = new \DateTime('NOW');
$user_id = 4;
$class_id = 3;
$color_blind = false;
$force_date_calc = false;


class block_projectgradeup_heatmap_testcase extends advanced_testcase {

    public function test_heat_map_bad_value(){
        try{
            $heat_map = new \classes\heat_map();
        }
        catch(Exception $e){
            $this->assertInstanceOf('PHPUnit_Framework_Error_Warning'. $e);
        }
    }

    public function test_heat_map_number_value(){
        try{
            $heat_map = new \classes\heat_map('test','test','test','test','test','test','test','test','test');
        }
        catch(Exception $e){
            $this->assertInstanceOf('PHPUnit_Framework_Error_Warning'. $e);
        }
    }

    public function test_heat_map_noe_params(){
        try{
            $heat_map = new \classes\heat_map('test','test','test','test');
        }
        catch(Exception $e){
            $this->assertInstanceOf('PHPUnit_Framework_Error_Warning'. $e);
        }
    }

    public function test_heat_map_get_heatmap_json(){
        $heat_map = new \classes\heat_map($resolution, $user_time, $class_id);
        $tester = $heat_map->get_heatmap_json();
        $this->assertInternalType('string',$tester);
    }

    public function test_heat_map_get_current_date_bar(){
        $heat_map = new \classes\heat_map($resolution, $user_time, $class_id);
        $tester = $heat_map->get_current_date_bar();
        $this->assertInternalType('string',$tester);
    }

    public function test_heat_map_get_heat_map_annotations(){
        $heat_map = new \classes\heat_map($resolution, $user_time, $class_id);
        $tester = $heat_map->get_heat_map_annotations();
        $this->assertInternalType('string',$tester);
    }

    public function test_heat_map_get_calendar_marks(){
        $heat_map = new \classes\heat_map($resolution, $user_time, $class_id);
        $tester = $heat_map->get_calendar_marks();
        $this->assertInternalType('string',$tester);
    }

    public function test_heat_map_get_triangles(){
        $heat_map = new \classes\heat_map($resolution, $user_time, $class_id);
        $tester = $heat_map->get_triangles();
        $this->assertInternalType('string',$tester);
    }

    public function test_heat_map_get_all(){
        $heat_map = new \classes\heat_map($resolution, $user_time, $class_id);
        $tester = $heat_map->get_all();
        $this->assertInternalType('string',$tester);
    }
}
