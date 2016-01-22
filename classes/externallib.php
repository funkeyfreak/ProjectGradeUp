<?php

/**
 * The external API for block_projectgradeup
 *
 * @package     Project Grade Up
 * @copyright   2015 Dalin Williams
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Dalin Williams <dalinwilliams@gmail.com>
 */
//namespace block_projectgradeup;

require_once("$CFG->libdir/externallib.php");

//resources that are somehow not included in the example
//require_once("$CFG->dirroot/webservice/externallib.php");
/*
use external_api;
use external_function_parameters;
use external_value;
use external_format_value;
use external_single_structure;
use external_multiple_structure;
use invalid_parameter_exception;
*/
 /**
  * This is the external API for block_projectgradeup
  *
  * @copyright  2015 Dalin Williams
  * @package    Project Grade Up
  * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
  * @author     Dalin Williams <dalinwilliams@gmail.com>
  */
class block_projectgradeup_external extends external_api {
    //##########################################################################
    //####################### Future Work ######################################
    //##########################################################################
    /**
     * Gets and returns the classes using projectgradeup
     *
     * @return array(object) The requested information
     */
    //public static function block_projectgradeup_get_classes_served() {
    //    return true/*TODO*/;
    //}
    /**
     * Gets and returns the class average for all classes
     *
     * @return array(mixed) The averages associated with each class
     */
    //public static function block_projectgradeup_get_all_class_average() {
    //    return true/*TODO*/;
    //}

    //##########################################################################
    //####################### Function Parameters ##############################
    //##########################################################################
    /**
     * Returns a description of method parameters
     * @return external_function_parameters
     */
    public static function get_burnup_parameters(){
        return new external_function_parameters(
            array(
                'courseid'  => new external_value(PARAM_INT, 'The course id'),
                'userid'    => new external_value(PARAM_INT, 'The user id'),
                'resolution' => new external_single_structure(
                    array(
                        'width'     => new external_value(PARAM_INT, 'The width of the users viewing area'),
                        'height'    => new external_value(PARAM_INT, 'The height of the users viewing area')
                    )
                ),
                'request'       => new external_value(PARAM_TEXT, 'The request type'),
                'colorblind'   => new external_value(PARAM_INT, 'The color blind notifier')
            )
        );
    }
    /**
     * Returns a description of method parameters
     * @return external_function_parameters
     */
    public static function get_heatmap_parameters(){
        return new external_function_parameters(
            array(
                'courseid'  => new external_value(PARAM_INT, 'The course id'),
                'userid'    => new external_value(PARAM_INT, 'The user id'),
                'resolution' => new external_single_structure(
                    array(
                        'width'     => new external_value(PARAM_INT, 'The width of the users viewing area'),
                        'height'    => new external_value(PARAM_INT, 'The height of the users viewing area')
                    )
                ),
                'request'   => new external_value(PARAM_TEXT, 'The request type'),
                'colorblind'   => new external_value(PARAM_INT, 'The color blind notifier')

            )
        );
    }
    /**
     * Returns a description of method parameters
     * @return external_function_parameters
     */
    public static function get_artifacts_parameters(){
        return new external_function_parameters(
            array(
                'courseid'  => new external_value(PARAM_INT, 'The course id'),
                'userid'    => new external_value(PARAM_INT, 'The user id'),
                'format'    => new external_value(PARAM_TEXT, 'The format of the returned value', VALUE_DEFAULT, 'string'),
                'request'   => new external_value(PARAM_TEXT, 'The request type'),
            )
        );
    }

    //##########################################################################
    //####################### Function Definitions #############################
    //##########################################################################
    public static function get_burnup($courseid, $userid, $resolution, $request, $colorblind){
        global $CFG;
        //require the files and classes needed
        require_once("$CFG->dirroot/blocks/projectgradeup/burnup.class.php");
        require_once("$CFG->dirroot/blocks/projectgradeup/lib.php");

        //validate the incoming parameters
        $params = self::validate_parameters(self::get_burnup_parameters(), array('courseid' => $courseid, 'userid' => $userid,
                    'resolution' => $resolution, 'request' => $request, 'colorblind' => $colorblind));

        //now check for security
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        self::validate_context($context);
        require_capability('block/projectgradeup:viewpages', $context);

        //not needed for now
        //$transaction = $DB->start_delegated_transaction();

        $resolution = (object)$resolution;

        //if it is taler than it is wide, flip the resolution
        if($resolution->width > $resolution->height){
            $temp = $resolution->width;
            $resolution->width = $resolution->height;
            $resolution->height = $temp;
        }
        //if the resolution is not set, set to default
        if($resolution->width === 0 || $resolution->height === 0){
            $resolution->width = 1080;
            $resolution->height = 720;
        }

        $result = false;

        switch ($request) {
            case 'single':
                //if this course does not exist in the database
                if(!($DB->get_record('block_projectgradeup', array('course_id'=>$course_id)))){
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
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$course_id, 'studentid'=>$userid,'student'=>'student')))){
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
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$course_id, 'studentid'=>$userid,'student'=>'student')))){
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
        //not needed for now
        //$transaction->allow_commit();

        return $results;
    }
    /*public static function get_all_burnup($courses, $userid, $resolution){

    }
    public static function get_all_burnup_course($courseid, $users, $resolution){

    }*/
    public static function get_heatmap($courseid, $userid, $resolution, $request, $colorblind){
        global $CFG;
        //require the files and classes needed
        require_once("$CFG->dirroot/blocks/projectgradeup/heat_map.class.php");
        require_once("$CFG->dirroot/blocks/projectgradeup/lib.php");

        //validate the incoming parameters
        $params = self::validate_parameters(self::get_burnup_parameters(), array('courseid' => $courseid, 'userid' => $userid,
                    'resolution' => $resolution, 'request' => $request, 'colorblind' => $colorblind));

        //now check for security
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        self::validate_context($context);
        require_capability('block/projectgradeup:viewpages', $context);

        //not needed for now
        //$transaction = $DB->start_delegated_transaction();

        $resolution = (object)$resolution;

        //if it is taler than it is wide, flip the resolution
        if($resolution->width > $resolution->height){
            $temp = $resolution->width;
            $resolution->width = $resolution->height;
            $resolution->height = $temp;
        }
        //if the resolution is not set, set to default
        if($resolution->width === 0 || $resolution->height === 0){
            $resolution->width = 1080;
            $resolution->height = 720;
        }

        $result = false;

        switch ($request) {
            case 'single':
                //if this course does not exist in the database
                if(!($DB->get_record('block_projectgradeup', array('course_id'=>$course_id)))){
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
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$course_id, 'studentid'=>$userid,'student'=>'student')))){
                    throw new invalid_parameter_exception('The user is not enrolled in the selected course');
                }
                if(($results = block_projectgradeup_get_heatmap($courseid, $userid, $resolution, uesrtime(time()), $colorblind))){
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
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$course_id, 'studentid'=>$userid,'student'=>'student')))){
                    throw new invalid_parameter_exception('The user is not enrolled in the selected course');
                }
                if(!($results = block_projectgradeup_get_heatmap_all($userid, $resolution, uesrtime(time()), $colorblind))){
                    throw new invalid_response_exception('No data found in all heatmap request (pgu)');
                }
                $results = [$results];
                break;
            case 'course':
                //additonal security
                require_capability('block/projectgradeup:viewall', $context);
                //if we still have a false
                if(!($results = block_projectgradeup_get_heatmap_course($courseid, $resolution, uesrtime(time()), $colorblind))){
                    throw new invalid_response_exception('No data found in heatmap burnup request (pgu)');
                }
                break;
            default:
                //we do not support 'strange' requests!
                throw new invalid_parameter_exception("Request $resuest is not a valid pgu get_heatmap request");
                break;
        }
        //not needed for now
        //$transaction->allow_commit();
        return $results;
    }
    /*public static function get_all_heatmap($courses, $userid, $resolution){

    }
    public static function get_all_heatmap_course($courseid, $users, $resolution){

    }*/

    public static function get_artifacts($courseid, $userid, $format, $request){
        global $CFG;
        //require the files and classes needed
        require_once("$CFG->dirroot/blocks/projectgradeup/burnup.class.php");
        require_once("$CFG->dirroot/blocks/projectgradeup/lib.php");

        //validate the incoming parameters
        $params = self::validate_parameters(self::get_burnup_parameters(), array('courseid' => $courseid, 'userid' => $userid,
                    'format' => $format, 'request' => $request));

        //now check for security
        $context = get_context_instance(CONTEXT_COURSE, $courseid);
        self::validate_context($context);
        require_capability('block/projectgradeup:viewpages', $context);

        //not needed for now
        //$transaction = $DB->start_delegated_transaction();

        $result = false;
        //two requests handled, single for requests focused on artifacts for a single course, and all for artifacts focused on a users entireity
        switch ($request) {
            case 'single':
                //if this course does not exist in the database
                if(!($DB->get_record('block_projectgradeup', array('course_id'=>$course_id)))){
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
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$course_id, 'studentid'=>$userid,'student'=>'student')))){
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
                if(!($DB->get_record_sql($user_course_sql, array('courseid'=>$course_id, 'studentid'=>$userid,'student'=>'student')))){
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
        //not needed for now
        //$transaction->allow_commit();

        return $results;
    }


    //##########################################################################
    //####################### Function Returnes ################################
    //##########################################################################
    /**
     * Returns a description of the methods return values
     * @return external_function_returns
     */
    public static function get_burnup_returns(){
        return new external_multiple_structure(
            new external_value(PARAM_TEXT, 'The JSON string containing the burnup data in RAPHAEL')
        )
    }
    /*public static function get_burnup_returns(){
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'finial_result' => new external_value(PARAM_TEXT, 'The JSON string containing the burnup data in RAPHAEL')
                )
            )
        )s
    }*/
    /**
     * Returns a description of the methods return values
     * @return external_function_returns
     */
    public static function get_heatmap_returns(){
        return new external_multiple_structure(
            new external_value(PARAM_TEXT, 'The JSON string containing the heatmap data in RAPHAEL')
        )
    }
    /*public static function get_heatmap_returns(){
            return new external_multiple_structure(
                new external_single_structure(
                    array(
                        'finial_result' => new external_value(PARAM_TEXT, 'The JSON string containing the heatmap data in RAPHAEL')
                    )
                )
            )
        }*/
    /**
     * Returns a description of the methods return values
     * @return external_function_returns
     */
     public static function get_artifacts_returns(){
         return new external_multiple_structure(
            new external_value(PARAM_TEXT, 'The JSON string representation of the artifacts')
         )
     }
    /*public static function get_artifacts_returns(){
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'finial_result'   => new external_value(PARAM_TEXT, 'The JSON string representation of the artifacts')
                )
            )
        );
    }*/

    /**
     * Returns a description of the methods return values
     * @return external_function_returns
     * @todo Create a function which aggregates the entireity of the heatmaps for a course
     */
    /*public static function get_full_heatmap_returns(){
        return new external_single_structure(
            array(
                'finial_result' => new external_value(PARAM_TEXT, 'The JSON string containing the heatmap data in RAPHAEL')
            )
        );
    }*/

    /*public static function get_all_artifacts($courses, $userid, $format){

    }
    public static function get_all_artifacts_course($courseid, $users, $format){

    }*/
}
