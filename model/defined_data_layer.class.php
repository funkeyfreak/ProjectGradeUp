<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
  * The implemetation of the abstract base class data_layer_abstracts
  *
  * @author Dalin Williams <dalinwilliams@gmail.com>
  * @package Project Grade-Up
  * @version 0.0.1
  */
namespace datalayermodel;
//retuire the abstractions
require_once ("data_layer_abstracts.class.php");
require_once ('lib.php');
/**
 * We will go ahead and use the default instance of this class
 * @see pgu_artifact
 */
class artifact extends \datalayermodel\pgu_artifact{}
/**
 * We will go ahead and use the default instance of this class
 * @see pgu_class_date_time
 */
class class_date_time extends \datalayermodel\pgu_class_date_time{}
/**
 * We will go ahead and use the default instance of this class
 * @see pgu_artifact_date_time
 */
class artifact_date_time extends \datalayermodel\pgu_artifact_date_time{}
/**
 * We will go ahead and use the default instance of this class
 * @see pgu_artifact_types
 */
class artifact_types extends \datalayermodel\pgu_artifact_types{}
/**
 * We will go ahead and use the default instance of this class
 * @see pgu_artifact_difficulties
 */
class artifact_difficulties extends \datalayermodel\pgu_artifact_difficulty{}


/**
 * The delaration of the abstract class data_layer_abstracts
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 * @see data_layer_abstracts
 * @version 0.0.1
 */
class defined_data_layer extends \datalayermodel\data_layer_abstracts{
    //some added methods and helper funcitons
    /**
     * A simple helper function which gets the actual percentage grade of an artifact
     * @param  int $artifact_id The artifact id
     * @param  int $grade       The artifacts grade
     * @param  int(optional) $class       The optional class param
     * @return int              The out-of-100 grade for the artifact
     */
    private function get_percentage_grade($artifact_id, $grade, $class = null){
        global $DB;
        if(!isset($class)){
            $class = $this->course_id;
        }
        //pull the maximum grade for the artifact
        $max_grade = $DB->get_record('grade_items', array('id'=>(int)$artifact_id, 'courseid'=>(int)$class));
        return round($grade/$max_grade->grademax, 2);
    }
    //we will use the default constructor
    /**
     * The implementation of the abstract variant
     * @see data_layer_abstracts
     */
    public function get_artifact_data($class = null, $user = null, $opt_args = null){
        global $DB;
        if(!isset($user) && !isset($class)){
            $user = $this->user_id;
            $class = $this->course_id;
        }
        //initilize the results to false
        $results = false;
        //get the data for the artifact object
        $artifacts = $DB->get_records('pgu_artifacts',
            array('class_id' => $class, 'user_id' => $user), null,
            'id,grade,weight,category,status,title,type,artifact_id');
        foreach($artifacts as $item){
            $results[] = new artifact($this->get_percentage_grade($item->artifact_id, $item->grade), $item->weight, $item->category, null,
                         $item->status, $item->title, $item->type);
        }
        return $results;
    }
    /**
     * The implementation of the abstract variant
     * @see data_layer_abstracts
     */
    public function get_class_average($res = null, $class = null, $opt_args = null){
        global $DB;
        if(!isset($class)){
            $class = $this->course_id;
        }
        if(!isset($res)){
            $res = array(1080,720);
        }
        $res_x = $res[0];
        $res_y = $res[1];

        $results = false;
        //selects all the users in the artifacts table
        $artifact_user = $DB->get_records('pgu_artifacts', array('class_id' => $class),null, 'user_id');
        //generate a list per user
        $artifacts_per_user = array();
        foreach ($artifact_user as $item) {
            $artifacts_collection = $DB->get_records('pgu_artifacts',
                array('class_id' => $class, 'user_id' => $item->user_id), null,
                'id,grade,weight,category,status,title,type,artifact_id');
            $artifacts_user = new \stdClass();
            $artifacts_user->user = $item->user_id;
            $artifacts_user->artifacts = $artifacts_collection;
            $artifacts_per_user[] = $artifacts_user;
        }
        $average_cluster = new \stdClass();
        //place grades in formattable format
        $avg_grades = array();
        $all_artifacts = array();
        foreach($artifacts_per_user as $item){
            $user_grades = null;
            $user_artifact = null;
            //loop over the artifacts for this user
            foreach($item->artifacts as $value){
                //have the projection continue until we are no longer due
                if($value->status == 'notdue'){
                    break;
                }
                //extract the grade
                $user_grades[] = $this->get_percentage_grade($value->artifact_id, $value->grade);
                //extract the artifact
                $user_artifact[] = $value;
            }
            //contains the grades for the user
            $avg_grades[] = $user_grades;
            //contains the artifacts for the current user
            $all_artifacts[] = $user_artifact;
        }
        //find the average grades
        $averaged = array();
        //slice off the first artifact to use as a template
        $template_artifact = $all_artifacts[0];
        for($i = 0; $i < count($avg_grades[0]); $i++){
            //slice along an index
            $slice_o_grades = array_column($avg_grades, $i);

            //average said index
            $grade_average = array_sum($slice_o_grades)/count($slice_o_grades);
            //add average to an array for future use
            $averaged[] = $grade_average;
            $tmp = $template_artifact[$i]->grade;
            //set the template's grade for this artifact to the average
            $template_artifact[$i]->grade = round($grade_average,2);
            $tmp = $template_artifact[$i]->grade;
        }
        $x = 0;
        $y = $res_y;
        $t_x = 0;
        $t_y = $res_y;
        $points = array();
        foreach($template_artifact as $item){
            if(($item->status != "notdue") && ( $item->status != "notgraded")){
                $t_x = (($item->category * $item->weight) * $res_x) + $x;
                $t_y = (((($item->category * $item->weight) * $res_y)*-1)*$item->grade) + $y;
                $points[] = [$x, $y];
                $x = $t_x;
                $y = $t_y;
            }

        }
        //append last dangeling set
        $points[]  = [$x, $y];
        $path = null;
        for($i = 0; $i < count($points); $i++){
            //on the first point, denote path with starting 'M'
            if($i == 0){
                $path .= 'M ' . implode(',', $points[$i]);
            }
            else{
                $path .= ' L ' . implode(',', $points[$i]);
            }
        }
        //lastly, append the begining point and a line straingt to the x axis
        $path .= ' L ' . $points[count($points)-1][0] . ',' . $res_y;
        $path .= ' L ' . implode(',', $points[0]);

        $results[] = ['type' => 'path',
                     'path' => $path,
                     'stroke-width' => 2,
                     'fill' => '#FFFFFF',
                     'opacity' => .2,
                     'title' => get_string('classaveragechart', 'block_projectgradeup')];
        return $results;
    }
    /**
     * The implementation of the abstract variant
     * @see data_layer_abstracts
     */
    public function get_class_date_time_data($class = null, $user = null, $opt_args = null){
        global $DB;
        if(!isset($user) && !isset($class)){
            $user = $this->user_id;
            $class = $this->course_id;
        }
        //initalize the results to false, if there are no artifacts we will get false
        $results = false;
        //get the data for the class_date_time
        $class_date_time = $DB->get_record('pgu_class_date_time', array('class_id' => $class),
            'id,class_short_name,class_number,class_start_date,class_end_date,class_difficulty');
        $start_date = new \DateTime();
        $end_date = new \DateTime();
        $start_date->setTimestamp($class_date_time->class_start_date);
        $end_date->setTimestamp($class_date_time->class_end_date);
        $results = new class_date_time($class_date_time->class_short_name, $class_date_time->class_number,
                   $start_date, $end_date, $class_date_time->class_difficulty);
        return $results;
    }
    /**
     * The implementation of the abstract variant
     * @see data_layer_abstracts
     */
    public function get_artifact_date_time_data($class = null, $user = null, $opt_args = null){
        global $DB, $CFG;
        if(!isset($user) && !isset($class)){
            $user = $this->user_id;
            $class = $this->course_id;
        }
        //initilize the results to false, if there are no artifacts we will get false
        $results = false;
        //get the artifact_date_time data
        $artifact_date_time = $DB->get_records('pgu_artifact_date_time', array('class_id' => $class),
            'id,artifact_name,artifact_start_date,artifact_end_date,class_id,artifact_weight,artifact_category');
        foreach($artifact_date_time  AS $item){
            $start_date = new \DateTime();
            $end_date = new \DateTime();
            $start_date->setTimestamp($item->artifact_start_date);
            $end_date->setTimestamp($item->artifact_end_date);
            $results[] =  new artifact_date_time($item->artifact_name, $start_date, $end_date,
                $item->class_id, $item->artifact_weight, $item->artifact_category, $item->artifact_difficulty);
        }
        return $results;
    }
    /**
     * The implementation of the abstract variant
     * @see data_layer_abstracts
     */
    public function get_artifact_types_data($opt_args = null){
        global $DB, $CFG;
        $results = false;
        $artifact_types = $DB->get_records('pgu_artifact_types');
        foreach($artifact_types as $item){
            $results[] = new artifact_types($item->type, $item->suffix,
                         $item->duration, $item->course_id);
        }
        return $results;
    }
    /**
     * The implementation of the abstract variant
     * @see data_layer_abstracts
     */
    public function get_artifact_difficulty_data($opt_args = null)
    {
        global $DB;
        $results = false;
        $artifact_difficulty = $DB->get_records('pgu_artifact_difficulty');
        foreach ($artifact_difficulty as $item) {
            $results[] = new artifact_difficulty($item->type, $item->suffix,
                       $item->difficulty, $item->course_id);
        }
    }
}
