<?php
/**
 * artifacts - This class holds the created artifact (returned from the page
 * start) for futher manipulation until the page is refreshed.
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 */
/**
 * This is the genneral classes namespace
 *
 * @package Project Grade-Up
 */
namespace classes;
require_once('dataLayer.class.php');
/*if(!session_id()) {    // check if we have session_start() called
     session_start(); // if not, call it
}*/

/**
 * Description of artifacts
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 */
class artifacts {
    /**
     * Holds the current instance of the class
     *
     * @var instance(artifacts)
     */
    private static $instance;

    /**
     * Holds the list of artifacts incoming from the datalayer
     *
     * @var Artifacts[] $artifacts - An array of artifacts
     */
    private static $artifacts;

    /**
     * Creates the artifact class
     * @param int $user   The users
     * @param int $course The course
     */
    private function __construct($user, $course) {

    }
    /**
     * An array cloning funciton
     *
     * @param type $array
     * @return type
     */
    private static function array_clone($array) {
        return array_map(function($element) {
            return ((is_array($element))
                ? call_user_func(__FUNCTION__, $element)
                : ((is_object($element))
                    ? clone $element
                    : $element
                )
            );
        }, $array);
    }

    /**
     * getSelf() - This function will return the list of artifacts
     *
     * @param Bool $isUpdate true if we are trying to update the instance, false if not
     * @return List of Artifactt
     * @final
     */
    public static function getSelf($isUpdate){
        //if we are updating this instance
        if($isUpdate){
            self::$instance = new artifacts();
        }
        else{
            //if the instance exists
            if(self::$instance===NULL){
                //if the instance does not work, create one
                self::$instance = new artifacts();
            }
        }
        //returns the artifacts class object
        return clone self::$instance;
    }
    /**
     * getArtifacts() - Returns a list of artifacts
     *
     * @return Artifacts[] $this->artifacts - the artifacts of this instance
     */
    public static function getArtifacts($isUpdate){
        //if we are updating this instance
        //$result = ($isUpdate) ? self::$instance = new artifacts() : ((self::$instance === NULL) ? self::$instance = new artifacts(); : NULL);
        if($isUpdate){
            self::$instance = new artifacts();
        }
        else{
            //if the instance exists
            if(self::$instance===NULL){
                //if the instance does not work, create one
                self::$instance = new artifacts();
            }
        }
        //return the list of artifacts
        return self::array_clone(self::$artifacts);
    }
    /**
     * updateArtifacts() updates the artifacts existing in this singleton instance
     */
    public static function updateArtifacts(){
        //unset the session data
        //unset($_SESSION['aritfactsList']);
        //creating new artifacts
        self::$instance = new artifacts();
    }
}
