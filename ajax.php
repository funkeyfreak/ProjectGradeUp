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
 * remote_trigger - This class handeles php function calls (syncronous or otherwise)
 *
 * @author Dalin WIlliams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 * @copyright 2015 Dalin Williams
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @version 1.0.0
 */

ini_set('display_errors',1);
ini_set('display_startup_errors',1);
//use if need for debug
ini_set("log_errors", 1);
ini_set("error_log", "/tmp/php-error.log");
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

error_reporting(-1);

//flip the booleans for CLI debugging
define('AJAX_SCRIPT', true);
define('CLI_SCRIPT', false);

//required globals
global $USER, $COURSE, $USER, $DB, $CFG;

require_once('../../config.php');
require_once($CFG->dirroot . '/blocks/projectgradeup/lib.php');

$res_x;
$res_y;
$request_name;
$request;
$courseid;
$userid;
$colorblind;
$format;

if(CLI_SCRIPT){
    $res_x          = $argv[1];
    $res_y          = $argv[2];
    $request_name   = $argv[3];
    $request        = $argv[4];
    $courseid       = $argv[5];
    $userid         = $argv[6];
    $colorblind     = $argv[7];
    $format         = $argv[8];

}else{
    $res_x          = required_param('width',  PARAM_INT);
    $res_y          = required_param('height', PARAM_INT);
    $request_name   = required_param('function', PARAM_TEXT);
    $request        = required_param('request', PARAM_TEXT);
    $courseid       = required_param('course', PARAM_INT);
    $userid         = required_param('name', PARAM_INT);
    $colorblind     = required_param('assistance', PARAM_INT);
    $format         = required_param('format', PARAM_TEXT);
}


$context =  context::instance_by_id($courseid);//get_context_instance(CONTEXT_COURSE, $courseid);
$PAGE->set_url(new moodle_url('/projectgradeup/ajax.php', array('request'=>$request_name, 'user'=>$userid, 'course' => $courseid)));

require_sesskey();
require_login($courseid);
require_capability('block/projectgradeup:viewpages', $context);

//send the headers_list
//echo $OUTPUT->header();

###############################################################################
###############################################################################
############################ SANATIZE PARAMS ##################################
###############################################################################
###############################################################################
$resolution = new stdClass();
$resolution->width = $res_x;
$resolution->height = $res_y;

$request_params = new stdClass();
$request_params->request_name = $request_name;
$request_params->request_type = $request;
require_once("./lib.php");
error_log("colorblind");
error_log($colorblind);
if(!$clean = block_projectgradeup_sanatize_ajax($resolution, $request_params, $colorblind, $format)){
    throw new moodle_exception('Invalid request params sent to ajax.php, please review documentation for Project Grade-Up API usage.');
}
$resolution = $clean->res;
$request_name = $clean->request_params->request_name;
$request = $clean->request_params->request_type;
$colorblind = $clean->colorblind;
$format = $clean->format;


//echo ($courseid == SITEID);

//check the user and course ids
if ($courseid == SITEID) {
    throw new moodle_exception('invalidcourse');
}
//if you are not a techer, we need to make sure you can be here
if(!has_capability('block/projectgradeup:viewall', $context)){
    if($userid == $USER->id){
        throw new moodle_exception('Student id missmatch');
    }
}

###############################################################################
###############################################################################
############################ END: SANATIZE PARAMS #############################
###############################################################################
###############################################################################

$results = false;

switch ($request_name){
    case 'heatmap':
        #======================================================================
        #======================================================================
        #===================== OLD FUNCTIONS ==================================
        #====== These old functions will remain just in case I need them ======
        #======================================================================
        //require only the files needed for this transaction
        //require_once('heat_map.class.php');
        //set the resolution to some default amount if needed rather than crash
        //$res = array($res_x, $res_y);
        //call the heatmap function class
        //$heat_map = new \classes\heat_map($res, $user_time, $userid, $courseid);
        //$results = $heat_map->get_all();
        #======================================================================
        #======================================================================
        #===================== END: OLD FUNCTIONS =============================
        #======================================================================
        #======================================================================

        //require the files and classes needed
        require_once("$CFG->dirroot/blocks/projectgradeup/lib.php");
        //print_r2($resolution);

        switch ($request) {
            case 'single':
                //if this course does not exist in the database
                if(!($DB->get_record('block_projectgradeup', array('course_id'=>$courseid)))){
                    throw new invalid_parameter_exception('This course does not have PGU enabled, please contact your administratior/teacher');
                }
                //if the user does not exist within the course
                $user_course_sql = 'SELECT
                                        u.id AS user_id
                                    FROM
                                        mdl_role_assignments ra
                                        JOIN mdl_user u ON u.id = ra.userid
                                        JOIN mdl_role r ON r.id = ra.roleid
                                        JOIN mdl_context cxt ON cxt.id = ra.contextid
                                        JOIN mdl_course c ON c.id = cxt.instanceid
                                    WHERE ra.userid = u.id
                                        AND ra.contextid = cxt.id
                                        AND cxt.contextlevel =50
                                        AND cxt.instanceid = c.id
                                        AND r.shortname = :student
                                        AND c.id = :courseid
                                     	AND u.id = :studentid';
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$courseid, 'studentid'=>$userid,'student'=>'student')))){
                    throw new invalid_parameter_exception('The user is not enrolled in the selected course');
                }
                if(!($results = block_projectgradeup_get_heatmap($courseid, $userid, $resolution, usertime(time()), $colorblind))){
                    throw new invalid_response_exception('No data found in single heatmap request (pgu)');
                }
                break;
            case 'all':
                //if the user does not exist within the course
                $user_course_sql = 'SELECT
                                        u.id AS user_id
                                    FROM
                                        mdl_role_assignments ra
                                        JOIN mdl_user u ON u.id = ra.userid
                                        JOIN mdl_role r ON r.id = ra.roleid
                                        JOIN mdl_context cxt ON cxt.id = ra.contextid
                                        JOIN mdl_course c ON c.id = cxt.instanceid
                                    WHERE ra.userid = u.id
                                        AND ra.contextid = cxt.id
                                        AND cxt.contextlevel =50
                                        AND cxt.instanceid = c.id
                                        AND r.shortname = :student
                                        AND c.id = :courseid
                                        AND u.id = :studentid';
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$courseid, 'studentid'=>$userid,'student'=>'student')))){
                    throw new invalid_parameter_exception('The user is not enrolled in the selected course');
                }
                if(!($results = block_projectgradeup_get_heatmap_all($userid, $resolution, usertime(time()), $colorblind))){
                    throw new invalid_response_exception('No data found in all heatmap request (pgu)');
                }
                $results = [$results];
                break;
            case 'course':
                //additonal security
                require_capability('block/projectgradeup:viewall', $context);
                //if we still have a false
                if(!($results = block_projectgradeup_get_heatmap_course($courseid, $resolution, usertime(time()), $colorblind))){
                    throw new invalid_response_exception('No data found in heatmap burnup request (pgu)');
                }
                break;
            default:
                //we do not support 'strange' requests!
                throw new invalid_parameter_exception("Request $resuest is not a valid pgu get_heatmap request");
                break;
            }
        break;
    case 'burnup':
        #======================================================================
        #======================================================================
        #===================== OLD FUNCTIONS ==================================
        #====== These old functions will remain just in case I need them ======
        #======================================================================
        //require only the files needed for this transaction
        //require_once('burnup.class.php');
        //require_once('defined_data_layer.class.php');
        //get the resolution
        //$res = array($res_x, $res_y);
        //call the burn up function. No args needed
        //$artifacts = new \datalayermodel\defined_data_layer($courseid, $userid);
        //call the burnupp class
        //$burnup = new \classes\burnup($res, $artifacts->get_artifact_data());
        //$results = $burnup->produceCompleteArray();
        #======================================================================
        #======================================================================
        #===================== END: OLD FUNCTIONS =============================
        #======================================================================
        #======================================================================
        //require the files and classes needed
        require_once("$CFG->dirroot/blocks/projectgradeup/lib.php");

        switch($request){
            case 'single':
                //if this course does not exist in the database
                if(!($DB->get_record('block_projectgradeup', array('course_id'=>$courseid)))){
                    throw new invalid_parameter_exception('This course does not have PGU enabled, please contact your administratior/teacher');
                }
                //if the user does not exist within the course
                $user_course_sql = 'SELECT
                                        u.id AS user_id
                                    FROM
                                        mdl_role_assignments ra
                                        JOIN mdl_user u ON u.id = ra.userid
                                        JOIN mdl_role r ON r.id = ra.roleid
                                        JOIN mdl_context cxt ON cxt.id = ra.contextid
                                        JOIN mdl_course c ON c.id = cxt.instanceid
                                    WHERE ra.userid = u.id
                                        AND ra.contextid = cxt.id
                                        AND cxt.contextlevel =50
                                        AND cxt.instanceid = c.id
                                        AND r.shortname = :student
                                        AND c.id = :courseid
                                     	AND u.id = :studentid';
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$courseid, 'studentid'=>$userid,'student'=>'student')))){
                    throw new invalid_parameter_exception('The user is not enrolled in the selected course');
                }
                if(!($results = block_projectgradeup_get_burnup($courseid, $userid, $resolution, $colorblind))){
                    throw new invalid_response_exception('No data found in single burnup request (pgu)');
                }
                $results = [$results];
                break;
            case 'all':
                //if the user does not exist within the course
                $user_course_sql = 'SELECT
                                        u.id AS user_id
                                    FROM
                                        mdl_role_assignments ra
                                        JOIN mdl_user u ON u.id = ra.userid
                                        JOIN mdl_role r ON r.id = ra.roleid
                                        JOIN mdl_context cxt ON cxt.id = ra.contextid
                                        JOIN mdl_course c ON c.id = cxt.instanceid
                                    WHERE ra.userid = u.id
                                        AND ra.contextid = cxt.id
                                        AND cxt.contextlevel =50
                                        AND cxt.instanceid = c.id
                                        AND r.shortname = :student
                                        AND c.id = :courseid
                                        AND u.id = :studentid';
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$courseid, 'studentid'=>$userid,'student'=>'student')))){
                    throw new invalid_parameter_exception('The user is not enrolled in the selected course');
                }
                if(!($results = block_projectgradeup_get_burnup_all($userid, $resolution, $colorblind))){
                    throw new invalid_response_exception('No data found in all burnup request (pgu)');
                }
                break;
            case 'course':
                //additonal security
                require_capability('block/projectgradeup:viewall', $context);
                //if we still have a false
                if(!($results = block_projectgradeup_get_burnup_course($courseid, $resolution, $colorblind))){
                    throw new invalid_response_exception('No data found in course burnup request (pgu)');
                }
                break;
            default:
                //we do not support 'strange' requests!
                throw new invalid_parameter_exception("Request $resuest is not a valid pgu get_burnup request");
                break;
        }
        break;
    case 'artifact':
        #======================================================================
        #======================================================================
        #===================== OLD FUNCTIONS ==================================
        #====== These old functions will remain just in case I need them ======
        #======================================================================
        //require the php file
        //require_once('defined_data_layer.class.php');
        //get the data from the artifact class
        //$artifacts = new \datalayermodel\defined_data_layer($courseid, $userid);
        //check the args, see if they are more than just the title of the request
        //$results = ($format=='ajax') ? json_encode($artifacts->get_artifact_data()) : $artifacts->get_artifacts();
        #======================================================================
        #======================================================================
        #===================== END: OLD FUNCTIONS =============================
        #======================================================================
        #======================================================================
        //require the files and classes needed
        require_once("$CFG->dirroot/blocks/projectgradeup/lib.php");
        switch ($request) {
            case 'single':
                //if this course does not exist in the database
                if(!($DB->get_record('block_projectgradeup', array('course_id'=>$courseid)))){
                    throw new invalid_parameter_exception('This course does not have PGU enabled, please contact your administratior/teacher');
                }
                //if the user does not exist within the course
                $user_course_sql = 'SELECT
                                        u.id AS user_id
                                    FROM
                                        mdl_role_assignments ra
                                        JOIN mdl_user u ON u.id = ra.userid
                                        JOIN mdl_role r ON r.id = ra.roleid
                                        JOIN mdl_context cxt ON cxt.id = ra.contextid
                                        JOIN mdl_course c ON c.id = cxt.instanceid
                                    WHERE ra.userid = u.id
                                        AND ra.contextid = cxt.id
                                        AND cxt.contextlevel =50
                                        AND cxt.instanceid = c.id
                                        AND r.shortname = :student
                                        AND c.id = :courseid
                                        AND u.id = :studentid';
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$courseid, 'studentid'=>$userid,'student'=>'student')))){
                    throw new invalid_parameter_exception('The user is not enrolled in the selected course');
                }
                if(!($results = block_projectgradeup_get_artifacts($courseid, $userid, $format))){
                    throw new invalid_response_exception('No data found in single artifacts request (pgu)');
                }
                $results = [$results];
                break;
            case 'all':
                //if the user does not exist within the course
                $user_course_sql = 'SELECT
                                        u.id AS user_id
                                    FROM
                                        mdl_role_assignments ra
                                        JOIN mdl_user u ON u.id = ra.userid
                                        JOIN mdl_role r ON r.id = ra.roleid
                                        JOIN mdl_context cxt ON cxt.id = ra.contextid
                                        JOIN mdl_course c ON c.id = cxt.instanceid
                                    WHERE ra.userid = u.id
                                        AND ra.contextid = cxt.id
                                        AND cxt.contextlevel =50
                                        AND cxt.instanceid = c.id
                                        AND r.shortname = :student
                                        AND c.id = :courseid
                                        AND u.id = :studentid';
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$courseid, 'studentid'=>$userid,'student'=>'student')))){
                    throw new invalid_parameter_exception('The user is not enrolled in the selected course');
                }
                if(!($results = block_projectgradeup_get_artifacts_all($userid, $format))){
                    throw new invalid_response_exception('No data found in all artifacts request (pgu)');
                }
                break;
            case 'course':
                //additonal security
                require_capability('block/projectgradeup:viewall', $context);
                //if we still have a false
                if(!($results = block_projectgradeup_get_artifacts_course($courseid, $format))){
                    throw new invalid_response_exception('No data found in course artifacts request (pgu)');
                }
                break;
            default:
                //we do not support 'strange' requests!
                throw new invalid_parameter_exception("Request $resuest is not a valid pgu get_artifacts request");
                break;
        }


        break;
    default:
        throw new moodle_exception('Invalid argument passed to ajax.php in projectgradeup');
        break;
}

echo json_encode($results);

//echo $OUTPUT->footer();

die();
