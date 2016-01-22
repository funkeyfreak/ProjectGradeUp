<?php
/**
 * date_time.class.php was created to serve the purpose of managing the whole
 * of the submitted data for the heat-map portion of our application
 * 
 * @package Project Burn-Up
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @version 0.0.1
 */
/**
 * The genneral packages namespace
 * 
 * @package Project Grade-Up
 */
namespace classes;
/**
 * Description of artifact_date_times
 *
 * @author Dalin Williams
 */
class date_times {    
    /**
     * Holds the current instance of the class
     * 
     * @var instance(artifacts) 
     */
    private static $instance;    
    /**
     * Holds the list of artifacts incoming from the datalayer
     * 
     * @var artifact_date_times[] $artifact_date_times - An array of artifact_date_time objects
     */
    private static $artifact_date_time;
    /**
     *
     * @var type artifact_date_times[] $class_date_times - An array of class_date_time objects
     */
    private static $class_date_time;
    /**
     *
     * @var type 
     */
    private static $class;
    /**
     *
     * @var type 
     */
    private static $user;
    /**
     * The artifacts constructor - calls the data-layer function and gets the artifacts
     */
    private function __construct($class = null, $user = null) {
        /*
         * if(isset($class, $user){
         *      $obj = new $defined_data_layer($class, $user);
         *      $cdt = $obj->getClassAverage($class, $user);
         *      $adt= $obj->get_artifact_date_time($class, $user);
         *      //$obj = new \DL\definedDataLayer($dsn, $un, $pw, $tables, $cols);
         *   
         *      self::$class_date_times = $cdt;
         *      self::$artifact_date_times  = $adt;
         * }
         * else{
         *      $class = self::$class;
         *      $user = self::$user;
         *      $obj = new $defined_data_layer($class, $user);
         *      $cdt = $obj->getClassAverage($class, $user);
         *      $adt= $obj->get_artifact_date_time($class, $user);
         *      //$obj = new \DL\definedDataLayer($dsn, $un, $pw, $tables, $cols);
         *   
         *      self::$class_date_times = $cdt;
         *      self::$artifact_date_times  = $adt;
         * }
         */

        $dsn =  'mysql:host=localhost;dbname=prototype';
        $un = 'root';
        $pw = '';
        $tables = '';
        $cols = '';
        require_once('definedDataLayer.class.php');
        $obj = new \DL\definedDataLayer($dsn, $un, $pw, $tables, $cols);
        $cdt = $obj->get_class_date_time_data(NULL,NULL);
        $adt= $obj->get_artifact_date_time_data(NULL,NULL);
        self::$class_date_time = $cdt;
        self::$artifact_date_time  = $adt;
    }

    /**
     * An array cloning funciton
     * 
     * @param type $array
     * @return type array
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
     * This function creates the initial instance of this instance. If this is not
     * the initial creation of the instance, we make sure to delete any session 
     * data before we continue.
     * 
     * @param type $class
     * @param type $user
     */
    public static function create_initial_issue($class, $user){
        if(self::$instance == null){
            self::$instance = new date_times($class, $user);
            self::$class = $class;
            self::$user = $user;
        }
        else{
            //unset the session data
            //unset($_SESSION['aritfactsList']);
            self::$instance = new date_times($class, $user);
            self::$class = $class;
            self::$user = $user;
        }
    }

    /**
     * getSelf() - This function will the containing object of self
     * 
     * @param Bool $isUpdate true if we are trying to update the instance, false if not
     * @return $instance
     * @final
     */
    public static function get_self($isUpdate){
        //if we are updating this instance
        if($isUpdate){
            self::$instance = new date_times();
        }
        else{
            //if the instance exists
            if(self::$instance===NULL){
                //if the instance does not work, create one
                self::$instance = new date_times();
            }
        }
        //returns the artifacts class object
        return clone self::$instance;
    }
    /**
     * get_artifact_date_time() - Returns a list of artifact_date_times
     * 
     * @return artifact_date_time[] $this->artifact_date_time - the artifact_date_time of this instance
     */
    public static function get_artifact_date_time($isUpdate){
        //if we are updating this instance
        //$result = ($isUpdate) ? self::$instance = new date_times() : ((self::$instance === NULL) ? self::$instance = new date_times(); : NULL);
        if($isUpdate){
            //unset the session data
            //unset($_SESSION['aritfactsList']);
            self::$instance = new date_times();
        }
        else{
            //if the instance exists
            if(self::$instance===NULL){
                //if the instance does not work, create one
                self::$instance = new date_times();
            }
        }
        //return the list of artifacts
        return self::array_clone(self::$artifact_date_time);
    }
    /**
     * get_class_date_time() - Returns a list of class_date_time
     * 
     * @return class_date_time[] $this->class_date_time - the class_date_time of this instance
     */
    public static function get_class_date_time($isUpdate){
        //if we are updating this instance
        //$result = ($isUpdate) ? self::$instance = new date_times() : ((self::$instance === NULL) ? self::$instance = new date_times(); : NULL);
        if($isUpdate){
            //unset the session data
            //unset($_SESSION['aritfactsList']);
            self::$instance = new date_times();
        }
        else{
            //if the instance exists
            if(self::$instance===NULL){
                //if the instance does not work, create one
                self::$instance = new date_times();
            }
        }
        //return the list of artifacts
        //print_r2(self::$class_date_time);
        return self::array_clone(self::$class_date_time);
    }
    /**
     * updateArtifacts() updates the artifacts existing in this singleton instance
     */
    public static function update_date_times(){
        //unset the session data
        //unset($_SESSION['aritfactsList']);
        //creating new artifacts
        self::$instance = new date_times();
    }
}
