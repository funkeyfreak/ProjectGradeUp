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
/**
 * This class creates and returns a raphael-ready graph
 *
 * @copyright 2015 Dalin Williams
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade Up
 * @version 1.0.0
 */
class burnup{
    /**
     * The resolution of the window
     *  #[0] = width
     *  #[1] = height
     *
     * @var Int[] $resolution : The resolution of the current window
     */
    private $resolution; //[0] = width, [1] = height
    /**
     * The artifact collection which burnup
     *
     * @var Artifacts[] $artifact_collection : The artifact collection
     */
    private $artifact_collection;
    /**
     * The selected artifacts, a holder to assist get_title()
     *
     * @see get_title()
     * @var type
     */
    private $selected_artifacts;
    /**
     * The color blind indicator
     *
     * @var $colorblind: boolean
     */
    private $color_blind;
    /**
     * The constructor for the burnup object
     *
     * @param array(Int) $resolution  An array containing the resolution information
     * @param int $course_id   The course id
     * @param int $user_id     The user id
     * @param array(object) $artifacts   An optional array of artifacts to set the artifact_collection
     * @param boolean $color_blind A flag indicating color_blind usage
     */
    function __construct($resolution, $course_id, $user_id, $artifacts=null, $color_blind=false) {
        //if $artifacts is empty, we want to pull from the artifacts class
        if($artifacts === null){
            require_once('./model/defined_data_layer.class.php');
            $artifacts = new \datalayermodel\defined_data_layer($course_id, $user_id);
            $this->artifact_collection = $artifacts->get_artifact_data();
        }
        else{
        $this->artifact_collection = $artifacts;
        }
        //set the resolution to the incoming resolution
        if($resolution[1] > $resolution[0]){
            $tmp = $resolution[0];
            $resolution[0] = $resolution[1];
            $resolution[1] = $tmp;
        }
        $this->resolution = $resolution;
        //set the color blind
        $this->color_blind = $color_blind;
    }

    /**
     * A function that allows you to remove some '\' terminated
     *
     * @param type $value
     * @return type
     */
    private function stripslashes_deep($value){
        $value = is_array($value) ?
                    array_map('stripslashes_deep', $value) :
                    stripslashes($value);

        return $value;
    }

    /**
     * get_title() - Gets the titles of all artifacts
     *
     * @return string[] Returns a list of titles
     */
    private function get_title(){
        $data =  $this->selected_artifacts;
        $result = array();
        $i = 0;
        //$remove = array();

        foreach($data as $item){
            if(($item->status != "notdue") && ( $item->status != "notgraded")){
                //echo $item->title;
                $result[] =["title" => $item->title,
                            "grade" => $item->grade,
                            "index" => $i];
                $i++;
            }
            //$i++;
        }
        return $result;
    }

    /**
     * get_proj_grades($projections) - gets the titles and grades of all projections
     * as to where they lie on the far right y-axis
     *
     * @param Projections[] $projections - The list of grade projections
     * @return Object[String,Int] - The combination of the projection title and the projection grade
     */
    private function get_proj_grades($projections){
        $data = $projections;
        //get teh resolution
        $y = $this->resolution[1];
        $result = array();
        //calculate all projection data
        $result[] = ["title" => "Best Possible", "grade" => ((($data[0][1][1]/$y)-1)*-100)];
        $result[] = ["title" => "Worst Possible", "grade" => ((($data[1][1][1]/$y)-1)*-100)];
        $result[] = ["title" => "Current Projection", "grade" => ((($data[2][1][1]/$y)-1)*-100)];

        if(count($projections)==4){
            //TODO
            ////check to see if the class avg. projection has been given
            //$result[] =  ["title" => "Class Average", "grade" => ((($data[3][1]/$y)-1)*-100)]; - "insert magic or function or class call here"
        }
        return $result;
    }

    /**
     * make_artifacts - Makes the visual artifacts collection
     *
     * @return Int[] $set A set of calculated points representing the points
     * for the visual artifacts
     */
    private function make_artifacts(){
        //create local copies of the class variables
        $current_res_x = $this->resolution[0];
        $current_res_y = $this->resolution[1];
        $data = $this->artifact_collection;
        //initilize all the arrays
        $points_b_l = array();
        $points_b_r = array();
        $points_t_l = array();
        $points_t_r = array();
        $status = array();
        $urls   = array();
        $set = array();
        //initilize the temp variables
        $x = 0;
        $y = $current_res_y;
        $t_x = 0;
        $t_y = $current_res_y;
        //echo 'making artifacts';
        require_once('./lib.php');
        //itterate through all the artifacts
        foreach ($data as $item) {
            //if the artifact is complete or incomplete show
            //if(($item->status == "complete") || ( $item->status == "incomplete")){
            if(($item->status != "notdue") && ( $item->status != "notgraded")){
                //print_r2($item);
                //if((strpos($item->status, "not")===false) || (strpos($item->status, "show")!==false)){

                $t_x = (($item->category * $item->weight) * $current_res_x) + $x;
                $t_y = (((($item->category * $item->weight) * $current_res_y)*-1)*$item->grade) + $y;
                /*echo "tx";
                print_r2($t_x);
                echo "ty";
                print_r2($t_y);
                echo "on artifact";
                print_r2($item);*/
                //echo ' x ' . $t_x;
                //echo ' y ' . $t_y;
                $points_b_l[] = [$x,$current_res_y];
                $points_b_r[] = [$t_x,$current_res_y];
                $points_t_l[] = [$x, $y];
                $points_t_r[] = [$t_x, $t_y];
                $status[] = $item->status;
                $urls[] = $item->url;
                $x = $t_x;
                $y = $t_y;
                $this->selected_artifacts[] = $item;
           }
        }
        //echo (strpos($item->status, "show")!==false);
        //fourm all point sets into the finial set
        for($i = 0; $i < count($points_b_l); $i++){
            //echo ' set ';
            $set[] = [$points_b_l[$i], $points_t_l[$i], $points_t_r[$i], $points_b_r[$i], $points_b_l[$i], $status[$i], $urls[$i]];
        }
        //print_r2($set);
        return $set;
    }

    /**
     * makeTrendlne() - makes the thren line
     * @return array - array of all points of the trend line
     */
    private function make_trendLine(){
        //set all local copies of class variables
        $current_res_x = $this->resolution[0];
        $current_res_y = $this->resolution[1];
        $data = $this->artifact_collection;
        //initilize arrays & variables
        $points_t_l = array();
        $points_t_r = array();
        $x = 0;
        $y = $current_res_y;
        $t_x = 0;
        $t_y = $current_res_y;
        $finial_result = array();
        //create set of points
        foreach ($data as $item) {
            //if(($item->status == "complete") || ( $item->status == "incomplete")){
            if(($item->status != "notdue") && ( $item->status != "notgraded")){
            //if((strpos($item->status, "not")===false) && (strpos($item->status, "show")===false)){
            //if((strpos($item->status, "not")===false) || (strpos($item->status, "show")!==false)){
                $t_x = (($item->category * $item->weight) * $current_res_x) + $x;
                $t_y = (((($item->category * $item->weight) * $current_res_y)*-1)*$item->grade) + $y;
                $points_t_l[] = [$x, $y];
                $points_t_r[] = [$t_x, $t_y];
                $x = $t_x;
                $y = $t_y;
            }
        }
        //join all points
        for($i = 0; $i < count($points_t_l); $i++){
            $finial_result[] = [$points_t_l[$i]];
        }
        $finial_result[] = [[$x,$y]];
        return $finial_result;
    }

    private function make_grade_ranges(){
        $current_res_x = $this->resolution[0];
        $current_res_y = $this->resolution[1];

        /*//$grades = $this->artifactGrades;
        //$data = $this->artifact_collection;
        //$x = 0;
        //$y = 0;
        //$t_x = 0;
        //$t_y = 0;
        //$a = array();
        //$b = array();
        //$c = array();
        //$d = array();*/

        $a = [[0,$current_res_y],[$current_res_x, ($current_res_y * 0)],[$current_res_x, ($current_res_y * .1 )],[0,$current_res_y]];
        $b = [[0,$current_res_y],[$current_res_x, ($current_res_y * .1)],[$current_res_x, ($current_res_y * .2 )],[0,$current_res_y]];
        $c = [[0,$current_res_y],[$current_res_x, ($current_res_y * .2)],[$current_res_x, ($current_res_y * .3 )],[0,$current_res_y]];
        $d = [[0,$current_res_y],[$current_res_x, ($current_res_y * .3)],[$current_res_x, ($current_res_y * .4 )],[0,$current_res_y]];

        return [$a, $b, $c, $d];
    }

    /**
     * make_projections($class_average) - makes all grade projections
     *
     * @param Array(optional) $class_average - an optional array if $class average is wanted
     * @return type
     */
    private function make_projections($class_average){
        $current_res_x = $this->resolution[0];
        $current_res_y = $this->resolution[1];
        $data = $this->artifact_collection;

        $t_x = 0;
        $t_y = $current_res_y;
        $x = 0;
        $y = $current_res_y;

        $x_list = array();
        $y_list = array();

        foreach ($data as $item) {
            //if(($item->status == "complete") || ( $item->status == "incomplete")){
            if(($item->status != "notdue") && ( $item->status != "notgraded")){
            //if((strpos($item->status, "not")===false) && (strpos($item->status, "show")===false)){
            //if((strpos($item->status, "not")===false) || (strpos($item->status, "show")!==false)){
                $t_x = (($item->category * $item->weight) * $current_res_x) + $x;
                $t_y = (((($item->category * $item->weight) * $current_res_y)*-1)*$item->grade) + $y;
                $x_list[] = $x;
                $y_list[] = $y;
                $x = $t_x;
                $y = $t_y;
                if($item === end($data)){
                    break;
                }
            }
        }
        //calculate the projection slope
        $current_proj_slope = (($y - $current_res_y)/($x));
        //calculate the projection slope in relation to two points
        $mProjection =(($current_proj_slope*($current_res_x-$x))+$y);
        //find the slope for the best possiable
        $m_of_one = (($current_res_y - 0)/(0 - $current_res_x));
        //calculate all possible
        $best_possible  = [[$x, $y], [$current_res_x, ((/*(-1)*/($m_of_one*($current_res_x - $x)) + $y))]];
        //calculate worst possible
        $worst_possible = [[$x, $y], [$current_res_x, $y]];
        //calculate genneral projection
        $projection     = [[$x, $y], [$current_res_x, $mProjection]];
        //if classAverage is not empty, include it
        if(empty($class_average)){
            return [$best_possible, $worst_possible, $projection, $class_average];
        }
        return [$best_possible, $worst_possible, $projection];
    }

    /**
     * make_completed_area()- generates the completed area
     *
     * @return array - an array of points for the completed area
     */
    private function make_completed_area(){
        //initlize local copies of class data
        $current_res_x = $this->resolution[0];
        $current_res_y = $this->resolution[1];
        $data = $this->artifact_collection;
        //initilize arrays and local vars
        $t_x = 0;
        $t_y = $current_res_y;
        $x = 0;
        $y = $current_res_y;
        $points = array();
        //foreach data item, iterate until we can find the last item
        foreach ($data as $item) {
            //if(($item->status == "complete") || ( $item->status == "incomplete")){
            //if((strpos($item->status, "not")===false) && (strpos($item->status, "show")===false)){
            //if((strpos($item->status, "not")===false) || (strpos($item->status, "show")!==false)){
            if(($item->status != "notdue") && ( $item->status != "notgraded")){
                $t_x = (($item->category * $item->weight) * $current_res_x) + $x;
                $t_y = (((($item->category * $item->weight) * $current_res_y)*-1)*$item->grade) + $y;
                $points[] = [$x, $y];
                $x = $t_x;
                $y = $t_y;
            }
        }
        $points[] = [0 , $current_res_y];
        return $points;
    }

    /**
     * produce_complete_array() - Creates a large JSON object to be passed on to the view
     *
     * @return JSON a JSON object that will be passed to the view
     */
    public function produce_complete_array(){
        //initilize arrays
        $artifacts_result    = array();
        $trend_line_result    = array();
        $grade_area_results   = array();
        $curr_area_results    = array();
        $projection_results  = array();
        $finial_result       = array();
        //create artifacts
        $set_arti = $this->make_artifacts();
        //create sets of said points
        foreach ($set_arti as $item){
            $artifacts_result[] = "M " . implode(",", $item[0]) . " L " . implode(",", $item[1]) . " L " . implode(",", $item[2]) . " L " . implode(",", $item[3]) . " L " . implode(",", $item[4]);
        }

        //create trend line
        $set_trend = $this->make_trendLine();
        $counter = 0;
        foreach ($set_trend as $item){
            if($counter != 0){
                $trend_line_result[] = " L " . implode(",", $item[0]);
            }
            else{
                $trend_line_result[] = "M " . implode(",", $item[0]);
            }
            $counter++;
        }

        //create grade range
        $set_areas = $this->make_grade_ranges();
        //create set of points for grade range
        foreach($set_areas as $item){
            $grade_area_results[] = "M " . implode(",", $item[0]) . " L " . implode(",", $item[1]) . " L " . implode(",", $item[2]) . " L " . implode(",", $item[3]);
        }

        //create completed area
        $set_curr_area = $this->make_completed_area();
        $curr_area_results[] = "M " . implode(",",$set_curr_area[0]);
        $counter = 0;
        //create set of points for this area
        foreach($set_areas as $item){
            if($counter != 0){
                $curr_area_results[] = " L " . implode(",",$item[0]) . " L " . implode(",", $item[1]);
            }
            $counter++;
        }

        //create grade projections
        $set_projections = $this->make_projections(false);
        //create set of points for the projections
        foreach($set_projections as $item){
            //print_r2($item);
            $projection_results[] = "M " . implode(",", (array)$item[0]) . " L " . implode(",", (array)$item[1]);
        }

        //begin wrapping all results into JSON\
        /*require_once('./lib.php');
        print_r2($artifacts_result);
        print_r2($set_arti);*/
        $res = [];
        //bundle artifacts
        for($i = 0; $i < count($artifacts_result); $i++){
            $status = $set_arti[$i];
            //if the status is not due, we give it a zero and do not color it. Note: these sections should be the "last"
            if($status[5] === "notdue"){
                $res[] = ["type" => "path",
                          "path" => $artifacts_result[$i],
                          "opacity" => 0.0,
                          "stroke-width" => 1];
            }
            //if the item is incomplete, mark it as red
            else if($status[5] === "incomplete" || $status[5] === "incompleted"){
                $res[] = ["type" => "path",
                          "path" => $artifacts_result[$i],
                          "fill" => "#ca102c",
                          "opacity" => 1.0,
                          "stroke-width" => 1,
                          "href" => $status[6]];
            }
            //if an item is graded, we set it to the defualt color
            else if($status[5] === "complete" || $status[5] === "completed"){
                $res[] = ["type" => "path",
                          "path" => $artifacts_result[$i],
                          "fill" => "#323232",
                          "opacity" => 1.0,
                          "stroke-width" => 1,
                          "href" => $status[6]];
            }
            //if an item is not graded, we do not do anything
            else if($status[5] === "notgraded"){
                $res[] = ["type" => "path",
                          "path" => $artifacts_result[$i],
                          "opacity" => 0.0,
                          "stroke-width" => 1];
            }
            //if I am in show all, and I want to show all ungraded, change to yellow
            else if($status[5] === "notgradedshow"){
                $res[] = ["type" => "path",
                          "path" => $artifacts_result[$i],
                          "fill" => "#fff80b",
                          "opacity" => 1.0,
                          "stroke-width" => 1,
                          "href" => $status[6]];
            }
            //if I am in show all, anmd I want to show all undue, show and change to blue
            else if($status[5] === "notdueshow"){
                $res[] = ["type" => "path",
                          "path" => $artifacts_result[$i],
                          "fill" => "#fff80b",
                          "opacity" => 1.0,
                          "stroke-width" => 1,
                          "href" => $status[6]];
            }
        }
        require_once('./lib.php');
        //print_r2($res);

        //bundle artifacts
        $tmpres = json_encode($res);
        $finial_result[] = json_encode(["ArtifactsJSON" => $tmpres]);

        $res = [];

        //begin wrapping grade areas
        $res[] = ["type"    => "path",
          "path"    => $grade_area_results[0],
          "fill"    => "#81c4ff",
          "opacity" => 0.5,
          "stroke"  => "none"];

        $res[] = ["type"    => "path",
          "path"    => $grade_area_results[1],
          "fill"    => "#82ffab",
          "opacity" => 0.5,
          "stroke"  => "none"];

        $res[] = ["type"    => "path",
          "path"    => $grade_area_results[2],
          "fill"    => "#ffbf48",
          "opacity" => 0.5,
          "stroke"  => "none"];

        $res[] = ["type"    => "path",
          "path"    => $grade_area_results[3],
          "fill"    => "#ff4600",
          "opacity" => 0.5,
          "stroke"  => "none"];
        //bundle grade areas
        $tmpres = json_encode($res);
        $finial_result[] = json_encode(["GradesJSON" => $tmpres]);

        $res = [];

        //bundle adn initilize trendline
        $res[] = ["type"            => "path",
                  "path"            => implode("", $trend_line_result),
                  "stroke-width"    => 3];
        $tmpres = json_encode($res);
        $finial_result[] = json_encode(["GradeTrendJSON" => $tmpres]);

        $res = [];

        //wrapup projections
        $res[] = ["type"             => "path",
                  "path"             => $projection_results[0],
                  "stroke-width"     => 3,
                  "stroke"           => "#5aa7bb"];

        $res[] = ["type"             => "path",
                  "path"             => $projection_results[1],
                  "stroke-width"     => 3,
                  "stroke"           => "#a32d24"];

        $res[] = ["type"             => "path",
                  "path"             => $projection_results[2],
                  "stroke-width"     => 3,
                  "stroke"           => "#a32df4"];
        //bundle projections
        $tmpres = json_encode($res);
        $finial_result[] = json_encode(["ProjectionJSON" => $tmpres]);

        $res = [];

        //wrapup current completd area
        $res[] = ["type"            => "path",
                  "path"            => implode("",$curr_area_results),
                  "stroke-width"    => 3,
                  "opacity"         => 0.5];
        //bundle this area
        $tmpres = json_encode($res);
        $finial_result[] = json_encode(["CompletedArea" => $tmpres]);
        $res = [];

        //get the finial item
        $names = $this->get_title();
        $tmpres = json_encode($names);
        $finial_result[] = json_encode(["Title" => $tmpres]);
        $res = [];
        //get the projection lables
        $projectionLabels = $this->get_proj_grades($set_projections);
        $tmpres = json_encode($projectionLabels);
        $finial_result[] = json_encode(["ProjLabels" => $tmpres]);

        //create teh final encode
        $data = json_encode($finial_result, JSON_FORCE_OBJECT);
        //send data
        return $data;
    }
}
