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
 * The main namespace for our internal classes
 *
 * @copyright 2015 Dalin Williams
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade Up
 */
namespace classes;
use \DateTime;
/**
 * Description of heat_map
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade Up
 * @version 1.0.0
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
//for testing purposes
require_once('./lib.php');
class heat_map{
    /**
     * The resolution of the heatmap
     *
     * @var object(int)
     */
    private $resolution;
    /**
     * A collection of date-time objects
     *
     * @var object(class_date_time)
     */
    private $class_date_time_list;
    /**
     * A collection of date-time objects
     *
     * @var object(artifact_date_time)
     */
    private $artifact_date_time_list;

    /**
     * The current time for this instance, returned from an ajax call
     *
     * @var string The return of an ajax call to the user to get the time
     */
    private $current_user_time;
    /**
     * The range of time (in days) the artifacts transverse of the current class
     *
     * @var int the date range of the heat-map
     */
    private $range;
    /**
     * The holder of the compuited heat_values per artifact
     *  #. The values at index 0 is the length of the item, the time value
     *  #. The values at index 1 is the height of the item, the difficulty
     *
     * @var Array[mixed] The holder of the individual weights with their respective date lengths
     */
    private $heat_values;
    /**
     *
     * @var type
     */
    private $finial_values;
    /**
     *
     * @var type
     */
    private $colors;
    /**
     * The color blind indicator
     *
     * @var $colorblind: boolean
     */
    private $color_blind;

    /**
     * This variable holds the date range to difficulty value of the heatmap of item groups
     *
     * @var $flux_list: associative array
     */
    private $flux_list;

    /**
     * This variable holds the date range to difficulty value of the heatmap of individual items
     *
     * @var $stable_list: associative array
     */
    private $stable_list;


    /**
     * The constrtuctor for the heat_map class
     *
     * @param int $curr_res  The incoming resolution
     * @param string $user_time The incoming date time
     * @param int $user_id   The users id
     * @param int $course_id The id of the course
     * @param object $artifact_date_times An optional array of artifact_date_time objects to set the class artifact_date_time_list property
     * @param object $class_date_tiems An optional class_date_time object to set the class class_date_time_list property
     * @param boolean $color_blind A flag for indicating color_blind color usage
     * @param boolean $force_date_calc Force the date calculation if set to true, default to false
     */
    function __construct($curr_res, $user_time, $user_id, $course_id, $artifact_date_times = null, $class_date_times = null, $color_blind = false, $force_date_calc = false) {
        require_once('./model/defined_data_layer.class.php');

        if(empty($curr_res) || empty($user_time) || empty($course_id) || empty($user_id) || !is_bool($color_blind)){
            throw new \Exception("Invalid parameters passed to _construct function of heat_map");
        }
        //get the artifacts
        $artifacts = new \datalayermodel\defined_data_layer($course_id, $user_id);
        if(isset($artifact_date_times) && ($artifact_date_times[0] instanceof \datalayermodel\artifact_date_time)){
            $this->artifact_date_time_list = $artifact_date_times;
        }
        else{
            $this->artifact_date_time_list =  $artifacts->get_artifact_date_time_data();
            //print_r2($this->artifact_date_time_list);
        }
        if(isset($class_date_times) && ($class_date_times instanceof \datalayermodel\class_date_time)){
            $this->class_date_time_list = $class_date_times;
        }
        else{
            $this->class_date_time_list = $artifacts->get_class_date_time_data();
        }
        //set the users time
        if(is_int($user_time)){
            try{
                $date = new \DateTime();
                $date->setTimestamp($user_time);
                $this->current_user_time = $date;
            }
            catch(moodle_exception $e){
                throw new invalid_parameter_exception('Invalid datetime integer');
            }
        }
        else if(is_string($user_time)){
            try{
                $date = new \DateTime($user_time);
                $this->current_user_time = $date;
            }
            catch(moodle_exception $e){
                throw new invalid_parameter_exception('Invalid datetime object');
            }
        }
        else if($user_time instanceof \DateTime){
            $this->current_user_time = $user_time;
        }
        else{
            throw new invalid_parameter_exception('Invalid type for parameter user_time');
        }
        //set the range if it is not set OR if force_date_calc is true
        if($this->class_date_time_list->class_end_date == null || $force_date_calc){
            $this->range = $this->get_date_range(false);
        }
        else{
            $this->range = $this->get_date_range(true);
        }
        //set the resolution to the incoming resolution
        $this->resolution[0] = $curr_res[1];
        $this->resolution[1] = $curr_res[0];
        //set the color blind checker
        $this->color_blind = $color_blind;
    }


    /**
     * get_date_range - Gets the range for a class if the class_date_time object does not provide suffice data
     *
     * @param boolean $is_defiend   If the class_date_time object has an end_date defined
     * @return int The duration of the class
     */
    private function get_date_range($is_defiend){
        $data = $this->artifact_date_time_list;
        $current_max = new DateTime();
        //if the class_date_time object does not have a defined end date
        if(!$is_defiend){
            $current = 0;
            //loop through artifacts and find largest date
            foreach($data as $item){
                if($item->artifact_end_date->getTimestamp() > $current){
                    $current = $item->artifact_end_date->getTimestamp();
                }
            }
            //get the end_date
            $current_max->setTimestamp($current);
        }
        else{
            //otherwise the end date is the defined end date
            $current_max = clone $this->class_date_time_list->class_end_date;
        }

        //get the start_date
        $current_start = clone $this->class_date_time_list->class_start_date;
        //set the new objects
        $start_date = clone $current_start;
        $end_date = clone $current_max;
        //calculate the interval
        $interval = $start_date->diff($end_date);
        $result = abs((int)$interval->format("%r%a"));
        //return the interval

        return $result;
    }

    /**
     * define_date_values() - Finds the number of date-time values
     * @return array(mixed) Returns an array of date ammount averages
     */
    private function define_date_values(){
        $date = $this->class_date_time_list;
        $start_date = $date->class_start_date;
        $end_date = $date->class_end_date;
        //itterate over the range and set the 'buckets' appropriately
        $range_of_dates = $this->range;
        $date = clone $start_date;
        $result = [];
        for($i = 0; $i < (int)$range_of_dates; $i++){
            array_push($result,['name' => ['NA'], 'date' => $date->format('Y-m-d'), 'value' => 0, 'individual' => ['NA' => 0]]);
            $date = $date->modify('+1 day');//add(new \DateInterval('P1D'));
        }
        $this->heat_values = $result;
    }
    /**
     * get_individua_dates($artifact_date_time) - Gets adn returns the artifact_date_time range
     * per artifact.
     *
     * @param object($artifact_date_time) $artifact_date_time The artifact_date_time for which we are finding the dates for
     * @return array(mixed) An array of values represending every day of the assignment and the total range length
     */
    private function get_individual_dates($artifact_date_time){
        $start = clone $artifact_date_time->artifact_start_date;
        $end = clone $artifact_date_time->artifact_end_date;
        $range = $start->diff($end);
        $int_range = (int)$range->format("%r%a");
        $date = $start;
        $result = [];
        for($i = 0; $i < $int_range; $i++){
            $result[] = $date->format('Y-m-d');
            $date->modify('+1 day');
        }
        return ['artifact' => $artifact_date_time->artifact_name, 'dates' => $result, 'range' => $int_range];
    }

    /**
     * get_max_difficulty() - Gets the maximum difficulty of the heat_values type
     *
     * @return int The maximum difficulty
     */
    private function get_max_difficulty(){
        $data = $this->heat_values;
        //itterate over the heat_values and get the largest
        $result = 0;
        foreach($data as $item){
            if($item['value'] > $result){
                $result = $item['value'];
            }
        }
        return $result;
    }
    /**
     * fill_date_range($artifact_date_time) - Fills and computes the $heat_values object property
     *
     * @param object $artifact_date_time The artifact sent in to compute
     * @return void     Nothing at all
     */
    private function fill_date_range($artifact_date_time){
        $complete_range = $this->heat_values;
        //get the range per item. Returns an array spanning the width of time of the aritfact
        $dates_range = $this->get_individual_dates($artifact_date_time);
        //pull out and the range
        $dates = $dates_range['dates'];
        //substract one day (the date of issue) so as not to have one day overlapps with adjacent artifacts
        $range = $dates_range['range']-1;
        //get the name
        $artifact_name = $dates_range['artifact'];
        //get the first date
        $first = null;
        for ($i=0; $i < count($dates); $i++) {
            //find items that are not due on the first day of class
            if(count($dates[$i]) != 0){
                $first = $dates[$i];
                break;
            }
            else {
                unset($dates[$i]);
                unset($range[$i]);
                $dates = array_values($dates);
                $range = array_values($range);
            }
        }

        $index = null;
        for($i = 0; $i < count($complete_range); $i++){
            //if there is a correspondance in our list
            if($first === $complete_range[$i]['date']){
                $index = $i;
                break;
            }
        }
        //a unique id for the name value, so we do not have any mixups
        $rand_id = rand();
        $new_name = $artifact_name . ' ' . $rand_id;
        //calculate the overall weight of the artifact
        $weight = $artifact_date_time->weight * $artifact_date_time->category * $artifact_date_time->defined_difficulty;
        //if we found the start(first date) within our range
        if(isset($index)){
            //loop the duration of the artifact and add it's difficulty
            for($i = 0; $i < $range+1; $i++){
                //add the value to the map
                $complete_range[$index]['name'][] = $new_name;
                $complete_range[$index]['value'] +=  $weight;//$artifact_date_time->defined_difficulty;
                $complete_range[$index]['individual'][$new_name] = $weight;//$artifact_date_time->defined_difficulty;
                $index++;
            }

        }
        //we simply set the heat_range values to what is needed
        $this->heat_values = $complete_range;
    }

    /**
     * itterate_across_all_class_date_time() - A simple wrapper function for fill_date_range, itterates over all
     * artifact_date_time_objects
     *
     * @return void Nothing
     */
    private function itterate_across_all_class_date_time(){
        //$this->define_date_values();
        $data = $this->artifact_date_time_list;
        foreach($data as $item){
            $this->fill_date_range($item);
        }
    }
    /**
     * get_color_max() - A helper funciton which returns the max RGB value of red based on class difficulty
     * @return int The amount of 'red' to use
     */
    private function get_color_max(){
        $data = $this->class_date_time_list;
        switch ($data->class_difficulty) {
            case 1:
            case 2:
                $result = 1/3*255;
                break;
            case 3:
            case 4:
                $result = 2/3*255;
                break;
            case 5:
                $result = 255;
                break;
            default:
                $result = 255;
                break;
        }
        return $result;
    }
    /**
     * calculate_flux_list - Calculates the flux (intermittant data) list
     *
     * @param array(object) $data The heat_values array
     * @return array(object) The array of flux items
     */
    private function calculate_flux_list($data){
        $repeat_count = 1;
        $flux_diagram = [];
        for ($i=0; $i < count($data); $i++){
            //if this is not the first itteration
            if($i !== 0){
                //check and see if we are still on the trend
                if((count($data) - 1) === $i){
                   $flux_diagram[] = ['value' => $prev, 'duration' => $repeat_count+1, 'name' => $prev_name];
                }
                //if we are at the last item, go ahead and update
                else if($prev == $data[$i]['value'] && $prev_name == $data[$i]['name']){
                    $repeat_count++;
                }
                else{
                    //store the data
                    $flux_diagram[] = ['value' => $prev, 'duration' => $repeat_count, 'name' => $prev_name];
                    //restart the count
                    $repeat_count = 1;
                }
            }
            $prev = $data[$i]['value'];
            $prev_name = $data[$i]['name'];
        }
        $this->flux_list = $flux_diagram;
    }
    /**
     * calculate_stable_list - Calculates the stable (non-intermittant data) list
     *
     * @param array(object) $data The heat_values array
     * @return array(object) The array of stable items
     */
    private function calculate_stable_list($data){
        //$data = $this->heat_values;
        $info_array = [];
        $count_array = [];
        $starting_position = [];
        for($i = 0; $i < count($data); $i++) {
            foreach ($data[$i]['name'] as $piece) {
                if(isset($count_array[$piece])){
                    $count_array[$piece]++;
                }
                else{
                    $starting_position[$piece] = $i/*+1/*/;
                    $count_array[$piece] = 1;
                }
                $info_array[$piece] = ['name' => $piece, 'range' => $count_array[$piece], 'value' => ($piece == 'NA') ? 0 : $data[$i]['individual'][$piece], 'starting_position' => $starting_position[$piece]];
            }
        }
        $info_array = array_values($info_array);
        $this->stable_list = $info_array;
    }

    private function initilize_flux_stable_values(){
        $data = $this->heat_values;
        $this->calculate_flux_list($data);
        $this->calculate_stable_list($data);
    }

    private function initilize_heat_values(){
        $this->define_date_values();
        $this->itterate_across_all_class_date_time();
        $this->clean_heat_values();
    }

    private function clean_heat_values(){
        $data = $this->heat_values;

        for($i = 0; $i < count($data); $i++){
            //if there is something active over this peroid
            if(count($data[$i]['name']) > 1){
                //remove the first element
                array_shift($data[$i]['name']);
                //reset the pointer
                reset($data[$i]['name']);
            }
            if(count($data[$i]['individual'] !== 1)){
                //remove the first element
                unset($data[$i]['individual']['NA']);
            }
        }

        $this->heat_values = $data;
    }

    /**
     * populate_finial_values() - Calculates all the values for the heat map
     * @return Array Returns an array representation of the heatmap
     */
    public function populate_finial_values(){
        $this->initilize_heat_values();
        $data = $this->heat_values;
        $res_x = $this->resolution[0];
        $res_y = $this->resolution[1];
        $total = $this->range;
        $current_res = 0;
        //$max_any_color = 255;
        $max_green = 255;
        //adjust the red color according to the difficulty of the course
        $max_red = $this->get_color_max();
        $max_dificulty = $this->get_max_difficulty();
        $red_color_unit = $max_dificulty/$max_red;
        $red = dechex(00);
        //hard set green to 255
        $green = dechex($max_green);
        //calculate the graidents
        $this->initilize_flux_stable_values();

        $flux_diagram = $this->flux_list;
        $info_array = $this->stable_list;

        //the old method
        foreach($data as $item){
            //compares the color approximating to the max difficulty
            $rgb_adjust = $item['value']/$red_color_unit;
            $red = dechex($rgb_adjust);
            $green = dechex($max_green - $rgb_adjust);
            $color[] = ['red' => $red, 'green' =>$green];
            $rgb_adjust = 0;
        }
        //set the object representation of the colors of the heatmap
        $this->colors = $color;
        //bring in the color blind variable
        $color_blind = $this->color_blind;
        $repeat = 1;
        $prev = null;
        //create the finial object
        foreach ($color as $item){
            if($item == $prev){
                $repeat++;
            }
            else{
                $finial_object[]=['range' => $repeat, 'color' => $prev,
                    'gradient' => "this is a string", 'length' =>  10, 'rect' => [], 'json' => 'json'];
                $prev = $item;
                $repeat = 1;
            }
        }
        //catch the last set
        $finial_object[]=['range' => $repeat, 'color' => $prev,
            'gradient' => "this is a string", 'length' =>  10, 'rect' => [], 'json' => 'json'];
        unset($finial_object[0]);
        $finial_object = array_values($finial_object);
        //popilate all the objects in the finial object
        $prev_red = null;
        $prev_green = null;
        for($i = 0; $i < count($finial_object); $i++){
            $red = (strlen((string)$finial_object[$i]['color']['red']) == 1) ? "0".strtoupper((string)$finial_object[$i]['color']['red']) : strtoupper((string)$finial_object[$i]['color']['red']);
            $green = (strlen((string)$finial_object[$i]['color']['green']) == 1) ? "0".strtoupper((string)$finial_object[$i]['color']['green']) : strtoupper((string)$finial_object[$i]['color']['green']);
            if($prev_green !== null && $prev_red !== null){
                $finial_object[$i]['gradient'] = ($color_blind) ? "0-#" . $prev_red . "00" . $prev_green . ":0-#" . $red . "00" . $green . ":100" : "0-#" . $prev_red . $prev_green . "00:0-#" . $red . $green . "00:100";
            }
            else{
                $finial_object[$i]['gradient'] = ($color_blind) ?  "0-#" . $red .  "00" . $green . ":0-#" . $red . "00" . $green . ":100" : "0-#" . $red . $green . "00:0-#" . $red . $green . "00:100";
            }
            $prev_red = $red;
            $prev_green = $green;
            $finial_object[$i]['rect'] = ['type' => 'rect',
                                          'x' => $current_res,
                                          'y' => 0,
                                          'width' => 0,
                                          'height' => $res_y,
                                          'stroke-width' => 0,
                                          'fill' => $finial_object[$i]['gradient']
            ];

            $calculated_range = ($finial_object[$i]['range']/$total)*$res_x;
            $current_res += $calculated_range;
            $finial_object[$i]['length'] = ($i < count($finial_object)-1) ? $calculated_range : ($res_x-$current_res) + $calculated_range;
            $finial_object[$i]['rect']['width'] = $finial_object[$i]['length'];
            $finial_object[$i]['json'] = json_encode($finial_object[$i]['rect']);
        }
        $this->finial_values = $finial_object;
    }

    /**
     * get_heatmap_json() - A helper funciton which gets and formats the data into JSON
     * @return string Json representation of the solution
     */
    public function get_heatmap_json(){
        $this->populate_finial_values();
        $data = $this->finial_values;
        foreach ($data as $item) {
            $result[] = $item['json'];
        }
        return json_encode(implode("|", $result));
    }
    /**
     * get_current_date_bar() - Gets the purple current date bar
     * @return string JSON object of the purple bar
     */
    public function get_current_date_bar(){
        $res_x = $this->resolution[0];
        $res_y = $this->resolution[1];
        $current_time = $this->current_user_time;
        $class_start = $this->class_date_time_list->class_start_date;
        $class_end = $this->class_date_time_list->class_end_date;
        $total_class = $this->range;
        //calculate all relavant diffs
        $cp = $current_time->diff($class_end);
        $pp = $current_time->diff($class_start);
        $current_posistion = (int)$cp->format("%r%a");
        $post_posistion = (int)$pp->format("%r%a");
        ///check for colorblind and use the fill color!
        $fill_color = ($this->color_blind) ? '#ffff00' : '#ff00ff';
        //check to see if the user is in the correct date-range for this module
        if(($current_posistion < 0) || ($post_posistion > 0)){
            //some default bar
            $width = ceil($res_x/100);
            $result =  ['type' => 'rect',
                        'x' => 0,
                        'y' => 0,
                        'width' => $width,
                        'height' => (int)$res_y,
                        'stroke-width' => 0,
                        'fill' => $fill_color,
                        'stroke' => $fill_color,
                        'opacity' => .50,
                        'title' => $current_time->format('H:i:s')
                    ];
        }
        else{
            $actual_posistion = floor($this->resolution[0]*(abs($post_posistion)/$total_class));
            $width = ceil($res_x/100);
            $x = (($actual_posistion/$res_x) > .5) ? $actual_posistion - $width : $actual_posistion+$width;

            $result =  ['type' => 'rect',
                        'x' => $x,
                        'y' => 0,
                        'width' => $width,
                        'height' => (int)$res_y,
                        'stroke-width' => 0,
                        'fill' => $fill_color,
                        'stroke' => $fill_color,
                        'opacity' => .50,
                        'title' => $current_time->format('m/d/Y')
                    ];
        }
        return json_encode($result);
    }
    /**
     * get_heat_map_annotations() - Gets the annotations for the artifact bars
     * @return string The json string for the annotations
     */
    public function get_heat_map_annotations(){
        $res_x = $this->resolution[0];
        $res_y = $this->resolution[1];
        $data = $this->artifact_date_time_list;
        $class = $this->class_date_time_list;
        $class_length = $this->range;
        $width = $res_x/100;
        $prev = ['rect'=>[null],'name'=>[null]];
        $fill_color = ($this->color_blind) ? '#ffa500': '#0000ff';
        $end_dates = [];
        $counter = -1;
        $class_start_date = clone $class->class_start_date;
        $to_result = [];
        foreach($data as $item){
            $d = $item->artifact_start_date->diff($item->artifact_end_date);
            $e = $class->class_start_date->diff($item->artifact_start_date);
            $dist = abs((int)$e->format("%r%a"));
            $diff = (int)$d->format("%r%a");
            $x_plus = ($dist/$class_length) * $res_x;
            $x = ($diff/$class_length) * $res_x;

            $end_dates[] = $item->artifact_end_date;

            if($class_start_date >= $item->artifact_end_date){
                $to_result = ['rect' => ['type' => 'rect',
                            'x' => 0    ,
                            'y' => 0,
                            'width' => $width/3,
                            'height' => (int)$res_y,
                            'stroke-width' => 2,
                            'fill' => $fill_color,
                            'stroke' => $fill_color,
                            'opacity' => .15],
                            'name' => $item->artifact_name . "\r\n" . $item->artifact_start_date->format('m/d/Y H:i:s') . ' -- ' . $item->artifact_end_date->format('m/d/Y   H:i:s'),
                            'title' => $item->artifact_name];
            }
            else{
                $to_result = ['rect' => ['type' => 'rect',
                            'x' => $x  + $x_plus - (($width*3)/11),
                            'y' => 0,
                            'width' => $width,
                            'height' => (int)$res_y,
                            'stroke-width' => 2,
                            'fill' => $fill_color,
                            'stroke' => $fill_color,
                            'opacity' => .15],
                            'name' => $item->artifact_name . "\r\n" . $item->artifact_start_date->format('m/d/Y H:i:s') . ' -- ' . $item->artifact_end_date->format('m/d/Y   H:i:s'),
                            'title' => $item->artifact_name];
            }
            $found = false;
            if(isset($result)){
                for($i = 0; $i < count($result); $i++){
                    if($result[$i]['rect']['x'] == $to_result['rect']['x'] || $end_dates[$i]==$item->artifact_end_date){
                        //echo ' DUPE FOUND ';
                        $found = true;
                        array_pop($end_dates);
                        $result[$i]['name'] .=  "\r\n" . $to_result['name'];//$result[$counter-1]['name']." & " .$prev['name'];
                    }
                }
            }

            if(!$found){
                //echo ' NOT FOUND ';
                $result[] = $to_result;
                $counter++;
            }
        }
        return json_encode($result);
    }
    /**
     * get_calendar_marks - Gets the calendar marks
     *
     * @return string The calendar marks JSON
     */
    public function get_calendar_marks(){
        $resolution = $this->resolution;
        $res_x = $resolution[0];
        $res_y = $resolution[1];
        $class = $this->class_date_time_list;
        $artifact_date_times = $this->artifact_date_time_list;
        $range = $this->range;


        $fill_color = ($this->color_blind) ? '#FFFFFF' : '#000000';

        $start_date =  $class->class_start_date;
        //get the day to res ratio
        $day_to_res_ratio = $resolution[0]/$range;
        $current_placement = 0;
        $result = array();
        $width = $res_x/300;
        $current_plus_one = true;
        for ($i = 0; $i <= $range; $i++) {
            //add height definition to times
            $dw = $start_date->format('N');
            if($dw == 6){
                $height = (5/10) * (int)$res_y;
            }
            else{
                $height = (1/10) * (int)$res_y;
            }
            $x_val = ($i === $range - 1) ? $day_to_res_ratio - $width : $day_to_res_ratio;
            $result[] = ['type' => 'rect',
                         'x' => $current_placement,
                         'y' => $res_y - $height,
                         'width' => $width,
                         'height' => $height,
                         'stroke-width' => 0,
                         'fill' => $fill_color,
                         'stroke' => $fill_color,
                         'opacity' => 0.15,
                         'title' => $start_date->format('m/d/Y')];
            $start_date->modify('+1 day');
            $current_placement += $x_val;
            $current_plus_one = !$current_plus_one;
        }
        return json_encode($result);
    }
    /**
     * get_triangles - Gets the triangles json
     * @return string The json triangles
     */
    private function get_triangles(){
        $this->initilize_heat_values();
        $this->initilize_flux_stable_values();
        $flux = $this->flux_list;
        $stable = $this->stable_list;
        $res_x = $this->resolution[0];
        $res_y = $this->resolution[1];
        $total = $this->range;
        $max_dificulty = $this->get_max_difficulty();
        $border_offset = 10;
        $previous_length = 0;
        $triangles = [];
        $color_blind = $this->color_blind;

        $max_green = 255;
        //adjust the red color according to the difficulty of the course
        $max_red = $this->get_color_max();
        $max_dificulty = $this->get_max_difficulty();
        $red_color_unit = $max_dificulty/$max_red;

        $red = dechex(00);
        $green = dechex($max_green);
        //loop through all flux values for the first set of values
        foreach ($flux as $item) {
            $length = ($item['duration']/$total) * $res_x;
            $height = ($item['value']/$max_dificulty) * $res_y;
            $length += $previous_length;
            $t_height = $res_y - $height;

            //color calculation
            $rgb_adjust = $item['value']/$red_color_unit;
            $red = dechex($rgb_adjust);
            $green = dechex($max_green - $rgb_adjust);

            $red = str_pad($red,2,'0',STR_PAD_LEFT);
            $green = str_pad($green,2,'0',STR_PAD_LEFT);

            $gradient = ($color_blind) ? "90-#0000FF:0-#" . $red ."00" . $green  .":100" : "90-#00FF00:0-#" .  $red . $green . "00:100";

            $triangles[] = ['type' => 'path',
                            'path' => 'M'.$previous_length.','.$res_y.'L'.$length.','.$res_y.'L'.$length.','.$t_height.'L'.$previous_length.','.$res_y,
                            'fill' => $gradient,

                            'stroke-width' => 0,
                            'opacity' => 1];

            $previous_length = $length;
            //$l += $item['duration'];
        }
        //reset the values
        $red = dechex(00);
        $green = dechex($max_green);

        $previous_length = 0;

        foreach ($stable as $item) {
            $height = ($item['value']/$max_dificulty) * $res_y;
            $t_height = $res_y - $height;

            $length_1 = ($item['starting_position']/$total) * $res_x;
            $length_2 = ($item['range'] + $item['starting_position'])/$total * $res_x;

            $length += $previous_length;
            //color calculation
            $rgb_adjust = $item['value']/$red_color_unit;

            $red = dechex($rgb_adjust);
            $green = dechex($max_green - $rgb_adjust);
            $red = str_pad($red,2,'0',STR_PAD_LEFT);
            $green = str_pad($green,2,'0',STR_PAD_LEFT);

            $gradient = ($color_blind) ? "90-#0000FF:0-#" . $red ."00" . $green  .":100" : "90-#00FF00:0-#" .  $red . $green . "00:100";

            $triangles[] = ['type' => 'path',
                            'path' => 'M'.$length_1.','.$res_y.'L'.$length_2.','.$res_y.'L'.$length_2.','.$t_height.'L'.$length_1 .','.$res_y,
                            'fill' => $gradient,
                            'stroke-width' => 0,
                            'opacity' => 1];
        }

        return json_encode($triangles);
    }

    /**
     * get_all() - A function that gets and formats all data into json
     * @return array(string) An array of JSON strings
     */
    public function get_all(){
        $triangles = $this->get_triangles();
        $result[] = ["heatmap" => $this->get_heatmap_json()];
        $result[] = ["annotations" => $this->get_heat_map_annotations()];
        $result[] = ["current" => $this->get_current_date_bar()];
        $result[] = ["ticks" => $this->get_calendar_marks()];
        $result[] = ['triangles' => $triangles];
        return json_encode($result);
    }

}
