<?php
/**
 * block_projectgradeup external services
 *
 * @package     Project Grade Up
 * @copyright   2015 Dalin William
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author      Dalin Williams <dalinwilliams@gmail.com>
 */

$functions = array(
    #Services to flush out in future work
    /*#A service to get all classes using the plugin, with some other cool data
    'block_projectgradeup_get_classes_served' => array(
        'classname' => 'block_projectgradup_external',
        'methodname' => 'get_classes_served',
        'classpath' => 'blocks/projectgradeup/externallib.php',
        'description' => 'Return information about the classes served by the plugin',
        'type' => 'read',
        //'capabilities' => '',
    ),
    #A service to get all class average
    'block_projectgradeup_get_class_average_grade' => array(
        'classname' => 'block_projectgradup_external',
        'methodname' => 'get_class_average_grade',
        'classpath' => 'blocks/projectgradeup/externallib.php',
        'description' => 'Return all class averages per artifact',
        'type' => 'read',
        //'capabilities' => '',
    ),*/
    #A service to get a completed burnup
    'block_projectgradeup_get_burnup' => array(
        'classname' => 'block_projectgradup_external',
        'methodname' => 'get_burnup',
        'classpath' => 'blocks/projectgradeup/externallib.php',
        'description' => 'Return a completed raphael burnup',
        'type' => 'read',
        //'capabilities' => '',
    ),
    #A service to get a completed heatmap
    'block_projectgradeup_get_heatmap' => array(
        'classname' => 'block_projectgradup_external',
        'methodname' => 'get_heatmap',
        'classpath' => 'blocks/projectgradeup/externallib.php',
        'description' => 'Return a completed raphael heatmap',
        'type' => 'read',
        //'capabilities' => '',
    ),
    #A service to get all artifacts for a user in a course
    'block_projectgradeup_get_artifacts' => array(
        'classname' => 'block_projectgradup_external',
        'methodname' => 'get_artifacts',
        'classpath' => 'blocks/projectgradeup/externallib.php',
        'description' => 'Return all artifacts within a class for a user',
        'type' => 'read',
        //'capabilities' => '',
    )
);


/*Allow the services to be called on insstallion by default*/
$services = array(
    #Allow access to all of our services
    'Project Grade-Up services' => array(
        'functions' => array ('block_projectgradeup_get_class_avg, block_projectgradeup_get_artifacts, block_projectgradeup_get_burnup, block_projectgradeup_get_heatmap'),
        //'requiredcapability' => '',
        'restrictedusers' =>0,
        'enabled'=>1,
    ),
    /*#Gets the artifacts
   'block_projectgradeup_artifacthandeler' => array(                             //the name of the web service
       'functions' => array ('block_projectgradeup_get_artifacts'),              //web service functions of this service
       //'requiredcapability' => '',                                               //if set, the web service user need this capability to access
                                                                                 //any function of this service. For example: 'some/capability:specified'
       'restrictedusers' =>0,                                                    //if enabled, the Moodle administrator must link some user to this service
                                                                                 //into the administration
       'enabled'=>1,                                                             //if enabled, the service can be reachable on a default installation
   ),
   #Gets the burnup chart
   'block_projectgradeup_burnuphandeler' => array(
       'functions' => array ('block_projectgradeup_get_burnup'),
       //'requiredcapability' => '',
       'restrictedusers' =>0,
       'enabled'=>1,
   ),
   #Gets teh heatmap
   'block_projectgradeup_heatmaphandeler' => array(
       'functions' => array ('block_projectgradeup_get_heatmap'),
       //'requiredcapability' => '',
       'restrictedusers' =>0,
       'enabled'=>1,
   ),
   #Gets the class average
   'block_projectgradeup_classavghandeler' => array(
       'functions' => array ('block_projectgradeup_get_class_avg'),
       //'requiredcapability' => '',
       'restrictedusers' =>0,
       'enabled'=>1,
   )*/
);
