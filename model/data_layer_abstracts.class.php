<?php
/**
 * data_layer_definitions.php was created to manage the data of projectgradeup
 * This class represents the datalayer portion of projectgradeup.
 *
 * @package Project Grade-Up
 * @author Dalin Williams<dalinwilliams@gamil.com>
 * @version 0.0.1
 */
/**
 * Description of the datalayermodel sub namespace
 *
 * @author Dalin Williams
 */
namespace datalayermodel;
/**
 * The definition of the abstract and class container of data_layer_definitions
 * the datalayer/model
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 */
abstract class data_layer_abstracts{
     /**
      * The course id in which the instance is called
      *
      * @var $course_id
      */
     public $course_id;
     /**
      * The optional user field that can be filled signifying the user calling the instance
      *
      * @var $user_id
      */
     public $user_id;
     /**
      * Boolean value to indicate if nosql implemetation
      *
      * @var bool, true if implemetation
      */
     protected $NOSQL;
     /**
      * Boolean value to indicate if rest implemetation
      *
      * @var bool, true if implemetation
      */
     protected $REST;
     /**
      * Boolean value to indicate if sql implemetation
      *
      * @var bool, true if implemetation
      */
     protected $SQL;
     /**
      * Boolean value to indicate if faltfile implemetation
      *
      * @var bool, true if implemetation
      */
     protected $FLATFILE;
     /**
      * Boolean value to indicate if soap implemetation
      *
      * @var bool, true if implemetation
      */
     protected $SOAP;
     /**
      * The detault constructor, should be overridden by the implemetation class
      *
      * @param $type - the type of opperation
      * @param $args - an optional array of arguements to include
      */
     public function __construct($course_id=null, $user_id=null,$type=null, $args = null){
         if(!empty($course_id)){
             $this->course_id = $course_id;
         }
         if(!empty($user_id)){
             $this->user_id = $user_id;
         }
         if(!empty($type)){
              switch ($type) {
                case 'nosql':
                  $this->NOSQL = true;
                  $this->handle_NOSQL($args);
                  break;
                case 'rest':
                  $this->REST = true;
                  $this->handle_REST($args);
                  break;
                case 'sql';
                  $this->SQL = true;
                  $this->handle_SQL($args);
                  break;
                case 'flatfile':
                  $this->FLATFILE = true;
                  $this->handle_FLATFILE($args);
                  break;
                case 'soap':
                  $this->SOAP = true;
                  $this->handle_SOAP($args);
                  break;
                default:
                  return false;
              }
          }
     }
     /**
      * Default handler for NOSQL - should be extended by the implemetation class
      *
      * @param $args - The array of args for the fucntion
      */
     public function handle_NOSQL($args = null){
        return false;
     }
     /**
      * Default handler for SQL - should be extended by the implemetation class
      *
      * @param $args - The array of args for the fucntion
      */
     public function handle_SQL($args = null){
        return false;
     }
     /**
      * Default handler for NOSQL - should be extended by the implemetation class
      *
      * @param $args - The array of args for the fucntion
      */
     public function handle_FLATFILE($args = null){
        return false;
     }
     /**
      * Default handler for REST - should be extended by the implemetation class
      *
      * @param $args - The array of args for the fucntion
      */
     public function handle_REST($args = null){
        return false;
     }
     /**
      * Default handler for SOAP - should be extended by the implemetation class
      *
      * @param $args - The array of args for the fucntion
      */
     public function handle_SOAP($args = null){

     }
    /**
     * get_artifact_data: Gets the artifact data for a given user in a given
     * course. Note, this abstraction exists for the use of a seprate
     * implemetation of this product
     *
     * @param (int) class - the id of the class
     * @param (int) user - the id of the user
     * @return An array of artifacts
     */
    abstract public function get_artifact_data($class = null, $user = null, $opt_args = null);
    /**
     * Name: get_class_average: Gets the class average for a given course. Note,
     * this abstraction exists for the use of a seprate implemetation of this
     * product
     *
     * @param (int) class - the id of the class
     * @param (array) res - the resolution
     * @return The full projection data
     */
    abstract public function get_class_average($res = null, $class = null, $opt_args = null);
    /**
     * get_class_date_time_data: Gets the class date and time information for a
     * given user in a given course
     *
     * @param (int) class - the id of the class
     * @param (int) user - the id of the user
     * @return The class date_time info
     */
    abstract public function get_class_date_time_data($class = null, $user = null, $opt_args = null);

    /**
     * get_artifact_date_time_data: Gets the artifacts date and time information for a
     * given user in a given course
     *
     * @param (int) class - the id of the class
     * @param (int) user - the id of the user
     * @return An array of artifact date_time info
     */
    abstract public function get_artifact_date_time_data($class = null, $user = null, $opt_args = null);
    /**
     * get_artifact_types: Gets the artifact types for use in a class
     *
     * @return An array of artifact types
     */
    abstract public function get_artifact_types_data($opt_args = null);
    /**
     * get_artifact_difficulty: Gets the artifact difficulty for use in a class
     *
     * @return An array of artufact difficulty objects
     */
    abstract public function get_artifact_difficulty_data($opt_args = null);
}

/**
 * The artifact base class. It can be extended to have methods
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 * @version 0.0.1
 */
abstract class pgu_artifact{
    /**
     * The grade of the artifact
     * @var $grade
     */
    public  $grade;
    /**
     * The weight for the artifact
     * @var $weight
     */
    public  $weight;
    /**
     * The weight of the artifacts category
     * @var $weight
     */
    public  $category;
    /**
     * The url for the artifact
     * @var $url
     */
    public  $url;
    /**
     * The graded status of the artifact
     * @var $status
     */
    public  $status;
    /**
     * The title of the artifact
     * @var $title
     */
    public  $title;
    /**
     * The type of artifact
     * @var $type
     */
    public  $type;
    /**
     * The default constructor, should be overridden by user unless they want
     * this definition
     *
     * @param $g the grade for the artifact
     * @param $w the weight for the artifact
     * @param $c the category weight of the artifact
     * @param $u the url for the artifact
     * @param $s the graded status of the artifact
     * @param $t the title of the artifact
     * @param $ty the type of the artifact
     */
    function __construct($g,$w,$c,$u,$s,$t,$ty){
        $this->grade = $g;
        $this->weight = $w;
        $this->category = $c;
        $this->url = $u;
        $this->status = $s;
        $this->title = $t;
        $this->type = $ty;
    }
}
/**
 * The class_date_time abstract base class, can be extended to have methods
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 * @version 0.0.1
 */
abstract class pgu_class_date_time{
    /**
     * The class name
     * @var $class_name
     */
    public $class_name;
    /**
     * The class number, up to 5 digits in length
     * @var $class_number
     */
    public $class_number;
    /**
     * The class start date
     * @var $class_start_date
     */
    public $class_start_date;
    /**
     * The class end date
     * @var $class_end_date
     */
    public $class_end_date;
    /**
     * The class difficulty, on a scale from one to five
     * @var $class_difficulty
     */
    public $class_difficulty;
    /**
     * The default constructor, should be overridden by user unless they want
     * this definition
     *
     * @param $n the name
     * @param $cn the number
     * @param $csd the start date
     * @param $ced end date
     * @param $cd the difficulty
     */
    function __construct($n, $cn, $csd, $ced, $cd) {
        $this->class_name = $n;
        $this->class_number = $cn;
        $this->class_start_date = $csd;
        $this->class_end_date = $ced;
        $this->class_difficulty = $cd;
    }
}

/**
 * The artifact_date_time abstract base class, can be extended to have methods
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 * @version 0.0.1
 */
abstract class pgu_artifact_date_time{
    /**
     * The artifact name
     * @var $artifact_name
     */
    public $artifact_name;
    /**
     * The artifact start date
     * @var $artifact_start_date
     */
    public $artifact_start_date;
    /**
     * The artifact_end_date
     * @var $artifact_end_date
     */
    public $artifact_end_date;
    /**
     * The course in which the artifact resides
     * @var $course_id
     */
    public $course_id;
    /**
     * The artifact weight
     * @var $weight
     */
    public $weight;
    /**
     * The artifact category weight
     * @var $category
     */
    public $category;
    /**
     * The difficulty of an artifact
     * @var $defined_difficulty
     */
    public $defined_difficulty;
    /**
     * The default constructor, should be overridden by user unless they want
     * this definition
     *
     * @param $an the artifact name
     * @param $asd the start date
     * @param $aed the end date
     * @param $w the weight
     * @param $c the category weight
     * @param $dd the difficulty of the artifact
     */
    function __construct($an, $asd, $aed, $cid, $w, $c, $dd) {
        $this->artifact_name = $an;
        $this->artifact_start_date = $asd;
        $this->artifact_end_date= $aed;
        $this->course_id = $cid;
        $this->weight = $w;
        $this->category = $c;
        $this->defined_difficulty = $dd;
    }
}
/**
 * The artifact_type base class definition
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 * @version 0.0.1
 */
abstract class pgu_artifact_types{
    /**
     * The artifact type
     * @var $artifact_type
     */
    public $artifact_type;
    /**
     * The suffic related to the artifact type
     * @var $artifact_type
     */
    public $suffix;
    /**
     * The durration in days. The max is ~270 years (in days)
     * @var $duration
     */
    public $duration;
    /**
     * The course in which the type is defined
     * @var $course_id
     */
    public $course_id;
    /**
     * The default constructor, should be overridden by user unless they want
     * this definition
     *
     * @param $i the id
     * @param $n the name
     * @param $d the optional data
     * @param $c the course id
     */
    function __construct($a, $s, $d, $c)
    {
        $this->artifact_type = $a;
        $this->suffix = $s;
        $this->duration = $d;
        $this->course_id = $c;
    }
}
/**
 * The artifact_difficulty base class definition
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 * @version 0.0.1
 */
abstract class pgu_artifact_difficulty{
    /**
     * The artifact type
     * @var $artifact_type
     */
    public $artifact_type;
    /**
     * The suffic related to the artifact type
     * @var $artifact_type
     */
    public $suffix;
    /**
     * The difficulty on a scale. The scale is from 1-5, with 1 being the easiest and 5 being the most difficult
     * @var $duration
     */
    public $difficulty;
    /**
     * The course in which the type is defined
     * @var $course_id
     */
    public $course_id;
    /**
     * The default constructor, should be overridden by user unless they want
     * this definition
     *
     * @param $i the id
     * @param $n the name
     * @param $d the optional data
     * @param $c the course id
     */
    function __construct($a, $s, $d, $c)
    {
        $this->artifact_type = $a;
        $this->suffix = $s;
        $this->difficulty = $d;
        $this->course_id = $c;
    }
}
/**
 * The user abstract base class definition
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 * @version 0.0.1
 */
abstract class pgu_user{
    /**
     * The user id
     * @var $id
     */
    public $id;
    /**
     * The users name, one string
     * @var $name
     */
    public $name;
    /**
     * The data of user
     * @var $data
     */
    public $data = array();
    /**
     * The default constructor, should be overridden by user unless they want
     * this definition
     *
     * @param $i the id
     * @param $n the name
     * @param $d the optional data
     */
    function __construct($i, $n, $d=null){
        $this->id = $i;
        $this->name = $n;
        $this->data = $d;
    }
}

/**
 * The class abstract base class definition
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade-Up
 * @version 0.0.1
 */
abstract class pgu_class{
    /**
     * The user id
     * @var $id
     */
    public $id;
    /**
     * The users name, one string
     * @var $name
     */
    public $name;
    /**
     * The data of user
     * @var $data
     */
    public $data = array();
    /**
     * The default constructor, should be overridden by user unless they want
     * this definition
     *
     * @param $i the id
     * @param $n the name
     * @param $d the optional data
     */
    function __construct($i, $n, $d=null){
        $this->id = $i;
        $this->name = $n;
        $this->data = $d;
    }
}
