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
 * The main view of the projectgradeup block
 *
 * @author Dalin Williams <dalinwilliams@gmail.com>
 * @package Project Grade Up
 * @license GNU 3
 * @version 1.0.0
 */

 ini_set("log_errors", 1);
 ini_set("error_log", "/tmp/php-error.log");
 error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);


require_once('../../config.php');
require_once('lib.php');

global $DB, $OUTPUT, $PAGE, $THEME, $USER;

// Check for all required variables.
$courseid = required_param('courseid', PARAM_INT);


$blockid = required_param('blockid', PARAM_INT);

// Next look for optional variables.
$id = optional_param('id', 0, PARAM_INT);

$context = context_course::instance($courseid);
//validate the course
if (!$course = $DB->get_record('course', array('id' => $courseid))) {
    print_error('invalidcourse', 'block_projectgradeup', $courseid);
}
//only students and teachers can view this!
require_login($course);
require_capability('block/projectgradeup:viewpages', $context);


$course_details = get_course($id);
$course_heading =  get_string('viewheading', 'block_projectgradeup') . $course_details->fullname;

//adding the header
$PAGE->set_url('/blocks/projectgradeup/view.php', array('id' => $courseid));

$PAGE->set_pagelayout('base');
$PAGE->set_title($course_heading);
$PAGE->set_heading($course_heading);

$Hover_Message_Grade = get_string('hovermessagegrade','block_projectgradeup');
$Invalid_No_E_Call = get_string('invalidnoecall', 'block_projectgradeup');

//$PAGE->requires->js_call_amd('block_projectgradeup/core', 'init', array($Hover_Message_Grade, $Invalid_No_E_Call));

/*$PAGE->requires->js_amd_inline(
'
    require(["core/log", "block_projectgradeup/core", "block_projectgradeup/heatmap", "block_projectgradeup/burnup"], function(){
        log.debug("WORKED");
    });
');*/

$PAGE->requires->css(new moodle_url('/blocks/projectgradeup/theme/jquery-ui.min.css'));

//$PAGE->requires->css(new moodle_url('/blocks/projectgradeup/theme/jquery-ui.min.css'));

//adding the navigation bread crumbs
$settingsnode = $PAGE->settingsnav->add(get_string('blockname', 'block_projectgradeup'));
$editurl = new moodle_url('/blocks/projectgradeup/view.php', array('id' => $id, 'courseid' => $courseid, 'blockid' => $blockid));
$editnode = $settingsnode->add(get_string('pluginname', 'block_projectgradeup'), $editurl);
$editnode->make_active();

echo $OUTPUT->header();
echo $OUTPUT->heading($course_heading);

$current_version_number = "1.0.0";

//TEST CODE
/*block_projectgradeup_update_pgu_artifacts();
block_projectgradeup_update_pgu_artifacts_date_time_update();
block_projectgradeup_update_class_date_time();

//the test function
block_projectgradeup_get_all_heatmap_graph(4, [1080, 720], usertime(time()), true);


require_once('./burnup.class.php');
$burn = new \classes\burnup([1080,720], 3, 4);
$burn->produce_complete_array();

require_once('./heat_map.class.php');
$heat = new \classes\heat_map([1080, 720], usertime(time()), 4, 3, null, null, false);
$heat->get_all();*/

echo '<div class = m_pgu_main>';

$T_USER_ID = 4;
echo '<input id="m_pgu_hidden_userid" type="hidden" name="User Id" value="'.$T_USER_ID.'">';
echo '<input id="m_pgu_hidden_courseid" type="hidden" name="Course Id" value="'.$courseid.'">';
echo '<input id="m_pgu_hidden_version" type="hidden" name="Version Number" value="'.$current_version_number.'">';
echo '<input id="m_pgu_hidden_is_teacher" type="hidden" name="Is Teacher" value="'.has_capability('block/projectgradeup:teachersettings', $context).'">';

//if this is a teacher, provide a dropdown with all students,p currently disabled until a new version
if(has_capability('block/projectgradeup:teachersettings', $context) && false){
    $select_name = get_string('teacherstudentselect', 'block_projectgradeup');
    $please_select = get_string('teacherpleaseselect', 'block_projectgradeup');
    $selecterror = get_string('teacherstudentselecterror', 'block_projectgradeup');

    echo '<select class="m_pgu_student_select" name="'.$select_name.'">';
    echo '<option value="">--'.$please_select.'--'.'</option>';
    $users_in_course_sql = 'SELECT a.user_id,
                                    u.username,
                                    u.firstname,
                                    u.lastname
                            FROM {pgu_artifacts} AS a
                            LEFT JOIN(SELECT
                                        u.id AS user_id
                                    FROM
                                        {ro le_assignments} ra
                                        JOIN {user} u ON u.id = ra.userid
                                        JOIN {role} r ON r.id = ra.roleid
                                        JOIN {context} cxt ON cxt.id = ra.contextid
                                        JOIN {course} c ON c.id = cxt.instanceid
                                    WHERE ra.userid = u.id
                                        AND ra.contextid = cxt.id
                                        AND cxt.contextlevel =50
                                        AND cxt.instanceid = c.id
                                        AND r.shortname = :student
                                        AND c.id = :courseid) AS users ON a.user_id = users.user_id
                            WHERE users.user_id IS NULL
                                AND a.class_id = :courseid';
    try {
        $students = $DB->get_records_sql($users_in_course_sql,array('student' => 'student','courseid' => $course_id));
        foreach ($students as $item) {
            echo '<option value="'.$item->user_id.'">'.$item->firstname.' '.$item->lastname.'-'.$item->username.'</option>';
        }
    } catch (Exception $e) {
        echo '<option value="'.$selecterror.'">'.$selecterror.'</option>';
    }
    echo '</select>';
    echo '<br>';
};

//////////////////ERROR BOX JIC////////////////////////////////////////
/*require_once('heat_map.class.php');
echo "<pre>";
echo "<h3>DEBUG</h3>";
$test123 = new \classes\heat_map([1680, 420], usertime(time()), $T_USER_ID, $courseid, $artifact_date_times = null, $class_date_times = null, $color_blind = false, $force_date_calc = false);
$results_test = $test123->get_all();
print_r($results_test);
echo "</pre>";*/
/////////////END ERROR BOX JIC////////////////////////////////////////
//div burnup container
$burnup = 'burnup';
echo '<div class="m_pgu_div_span" id="m_pgu_div_'.$burnup.'_container">';

$burnup_text = get_string('burnuptext','block_projectgradeup');
echo '<h3 class="m_pgu_text_label">'.$burnup_text.'</h2>';

$burnuphelp = get_string('burnuphelp', 'block_projectgradeup');
echo '<pre>'.$burnuphelp. '</pre>';

$burnup = 'burnup';
echo '<div id="m_pgu_div_'.$burnup.'_view_graph" class="m_pgu_div_view_graph"></div>';
//burnup popup holder

echo '<div id="m_pgu_div_'.$burnup.'_popup" class="m_pgu_div_popup">';
//echo '<p>'$replace_text'</p>';
$burnup_popup_text = get_string('burnuppopuptext', 'block_projectgradeup');
echo '<p>'.$burnup_popup_text.'</p>';
//echo '<p>Please enter in a grade (e.g. 80) for replacement in this artifact.</p>';
echo '<input type="text" id="m_pgu_input_field_text"/>';

echo '</div>';

echo '</div>';

echo '</br>';

//div burnup container
$heatmap = 'heatmap';
echo '<div class="m_pgu_div_span" id="m_pgu_div_'.$heatmap.'_container">';

$heatmap_text = get_string('heatmaptext','block_projectgradeup');
echo '<h3 class="m_pgu_text_label">'.$heatmap_text.'</h2>';

$heatmaphelp = get_string('heatmaphelp', 'block_projectgradeup');
echo '<pre>' .$heatmaphelp. '</pre>';

$colorblind_checkbox = get_string('colorblindcheckbox','block_projectgradeup');
echo '<input type="checkbox" name="option" value="colorblind" class="m_pgu_input" id="m_pgu_input_colorblind"/>'.$colorblind_checkbox.'<br/>';

$charttype_checkbox = get_string('charttypecheckbox','block_projectgradeup');
echo '<input type="checkbox" name="option" value="charttype" class="m_pgu_input" id="m_pgu_input_charttype"/>'.$charttype_checkbox.'<br/>';

$semester_checkbox = get_string('semestercheckbox','block_projectgradeup');
echo '<input type="checkbox" name="option" value="semester" class="m_pgu_input" id="m_pgu_input_semester"/>'. $semester_checkbox.'<br/>';

echo '<div id="m_pgu_div_'.$heatmap.'_view_graph" class="m_pgu_div_view_graph"></div>';

echo '</div>';
$PAGE->requires->js_amd_inline('
require(["block_projectgradeup/core", "jquery", M.cfg.wwwroot+"/blocks/projectgradeup/externaljs/jquery-ui.min.js", "core/log", "block_projectgradeup/ajax"],
    function(parent, $, ui, log, ajax   ){
        parent = parent.strings;
        $(parent.burnupPopupDiv).dialog({
            autoOpen: false,
            resizable: false,
            modal: true,
            width:"auto",
            buttons: {
                "Submit": function() {
                    var intOnly = new RegExp(/^\d+(?:\.\d{1,2})?$/);
                    var pulled = $(parent.burnupPopupInput).val();//parent.burnupPopupInput
                    var id = parseInt(parent.presentationLoadingDivId, 10);
                    var replace = (pulled/100);
                    if(intOnly.test(replace)){
                        log.debug(burnup.artifactCollection.collection);
                        burnup.artifactCollection.collection[id].grade = replace;
                        if(replace < Number.MAX_SAFE_INTEGER){
                            if(burnup.artifactCollection.collection[id].status == "notdue"){
                                burnup.artifactCollection.collection[id].status = "notdueshow";
                            }
                            else if(burnup.artifactCollection.collection[id].status == "notgraded"){
                                burnup.artifactCollection.collection[id].status = "notgradedshow";
                            }
                            else{
                                burnup.artifactCollection.collection[id].status = "edited";
                            }
                            burnup.refreshBurnup();
                        }
                    }
                    $(this).dialog("close");
                },
                "Delete" : function(){
                    var id = parseInt(parent.presentationLoadingDivId, 10);
                    burnup.artifactCollection.collection.splice(id,1);
                    //parent.artifactCollection.collection.splice(id,1);
                    burnup.refreshBurnup();
                    $(this).dialog("close");
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
            },
            close: function() {
                $(parent.burnupPopupDiv).val(""); ////parent.burnupPopupInput
            }
        });
        $(parent.colorblindCheckBoxId).change(function(){
            var request = new ajax();
            request.createHeatmapRequest("single", function(e){
                heatMap.doHeatMap(e);
            }, function(e){
                heatMap.parseError(e);
            });
        });

        $(parent.chartTypeCheckBoxId).change(function(){
            heatMap.doHeatMap();
        });

        $(parent.semesterCheckBoxId).change(function(){
            heatMap.doHeatMap();
        });
        log.debug("End controls");
 });
');
//sleep(5);
$PAGE->requires->js_call_amd('block_projectgradeup/burnup', 'init', array());

//$PAGE->requires->js_call_amd('block_projectgradeup/heatmap', 'init', array());

$PAGE->requires->js_amd_inline('require(["block_projectgradeup/core", "jquery", "block_projectgradeup/artifact",
        "core/log",
        "block_projectgradeup/ajax", M.cfg.wwwroot +
        "/blocks/projectgradeup/externaljs/jquery-ui.min.js",
        M.cfg.wwwroot + "/blocks/projectgradeup/externaljs/raphael.js"
    ],
    function(parent, $, arti, log, ajax, ui) {
        //initilize parent to the strings object
        parent = parent.strings;
        log.debug(ui);
        require([M.cfg.wwwroot +
            "/blocks/projectgradeup/externaljs/g.raphael-min.js"
        ]);
        var svgContainer,
            paper,
            elementSetAnnotations,
            elementSetDateTicks,
            elementSetTriangles;
        /**
         * heatMap - A collection of function and variables for heatmap funcitonality
         *
         * @var heatMap
         */
        heatMap = {
                /**
                 * preHeatMap - Holds the previous elements from an origional call
                 *
                 * @param string preHeatMap The previous JSON string
                 */
                preHeatMap: false,

                /**
                 * doHeatMap - Complets and prints out a heatmap
                 *
                 * @param string e The JSON string containing heatmap elements
                 */
                doHeatMap: function(e) {
                    log.debug("Loading Heatmap...");
                    $(parent.heatmapPresentationDiv).empty();
                    if (typeof e === "undefined") {
                        if (this.preHeatMap !== false) {
                            if ($(parent.semesterCheckBoxId).is(
                                    ":checked")) {
                                loadMap(JSON.parse(JSON.parse(this.preHeatMap))[
                                    1]);
                            } else {
                                loadMap(JSON.parse(JSON.parse(this.preHeatMap))[
                                    0]);
                            }
                        } else {
                            log.debug(parent.invalidNoECall);
                            $(parent.heatmapPresentationDiv).html(
                                "<h3>" + parent.invalidNoECall +
                                "</h3>");
                        }
                    } else {
                        var error = JSON.parse(e);
                        this.preHeatMap = e;
                        if (error.hasOwnProperty("error")) {
                            log.debug(error.error);
                            log.debug(error.stacktrace);
                            $(parent.heatmapPresentationDiv).html(
                                "<h3>" + error.debuginfo + "</h3>");
                            log.debug(error.debuginfo);
                        } else {
                            log.debug("Parsing e");
                            var eProcessed = JSON.parse(JSON.parse(e));
                            this.res = this.typeCheckResolution();

                            if ($(parent.semesterCheckBoxId).is(
                                    ":checked ")) {
                                loadMap(eProcessed[1]);
                            } else {
                                loadMap(eProcessed[0]);
                            }
                        }
                    }

                },
                /**
                 * parseError - Handls all errors from bad query results
                 *
                 * @param string e The error string generated
                 */
                parseError: function(e) {
                    log.debug(
                        "Oh nose! An error was found in projectgradeup/heatmap.js::.click"
                    );
                    log.debug(e);
                    $(parent.presentationLoadingDiv).html(
                        "<h1>Sorry, teacher-student view is not ready, please log in as one of your students to view their charts</h1>"
                    );
                },
                /**
                 * typeCheckColorBlind - Check the vision assistance checkbox
                 *
                 * @param boolean cb An optional variable representing the results of the checkbox
                 * @return boolean The result of a checked or uncheck checkbox
                 */
                typeCheckColorBlind: function(cb) {
                    if (typeof cb === "undefined") {
                        cb = $(parent.colorblindCheckBoxId).is(
                            ":checked");
                    } else if (typeof colorblind !== "boolean") {
                        cb = $(parent.colorblindCheckBoxId).is(
                            ":checked");
                    }
                    return cb;
                },
                /**
                 * typeCheckResolution - Check the user\"s resolution
                 *
                 * @param array(int) res The user\" resolution
                 * @return array(int) The adjusted resolution
                 */
                typeCheckResolution: function(res) {
                    if (typeof res === "undefined") {
                        res = {};
                        res.width = (document.getElementById(
                                    parent.heatmapPresentationDivId
                                )
                                .offsetWidth == 0) ? 1080 :
                            document.getElementById(
                                parent.heatmapPresentationDivId
                            ).offsetWidth;
                        res.height = 0.25 * res.width;
                    } else if (!("width" in res) || !("height" in
                            res)) {
                        res = {};
                        res.width = (document.getElementById(
                                    parent.heatmapPresentationDivId
                                )
                                .offsetWidth == 0) ? 1080 :
                            document.getElementById(
                                parent.heatmapPresentationDivId
                            ).offsetWidth;
                        res.height = 0.25 * res.width;
                    } else if (Array.isArray(res) && res.length ==
                        2) {
                        var r;
                        try {
                            r = res.reduce(function(o, v, i) {
                                o[i] = v;
                                return o;
                            }, {});
                            res = r;
                        } catch (e) {
                            log.debug(e);
                        } finally {
                            res = {};
                            res.width = (document.getElementById(
                                    parent
                                    .heatmapPresentationDivId
                                ).offsetWidth ==
                                0) ? 1080 : document.getElementById(
                                parent.heatmapPresentationDivId
                            ).offsetWidth;
                            res.height = 0.25 * res.width;
                        }
                    }
                    return res;
                },
                /**
                 * res - a variable for holding the resolution in object format
                 *
                 * @var res object(int)
                 */
                res: {
                    width: 0,
                    height: 0
                }
            }
            /**
             * loadMap - Load all map elements and variables
             *
             * @var string incoming_data The JSON string contraining the objects
             */
        loadMap = function(incoming_data) {
            if (document.getElementById(parent.heatmapPresentationDivId)
                .hasChildNodes()) {
                $(parent.heatmapPresentationDiv).empty();
            };

            //CHANGE
            var JSONS = JSON.parse(incoming_data);

            var graphChoice,
                colorBlind,
                curr,
                backgroundFill,
                tempHeatMap,
                tempCurrent,
                tempAnnotations,
                tempTicks,
                tempTriangles,
                holderArray = [],
                annotationHolderArray = [],
                titleHolderArray = [],
                currentHolderArray = [];

            graphChoice = $(parent.chartTypeCheckBoxId).is(
                ":checked");
            colorBlind = heatMap.typeCheckColorBlind();
            if (colorBlind) {
                backgroundFill = "#333333"; //INSERT parent.colorBlindFill;
            } else {
                backgroundFill = parent.rectangleColor;
            }

            svgContainer = document.getElementById(parent.heatmapPresentationDivId);
            paper = Raphael(svgContainer, heatMap.res.width,
                heatMap.res
                .height);
            elementSetAnnotations = paper.set();
            elementSetDateTicks = paper.set();
            elementSetTriangles = paper.set();
            //create background
            var background = paper.rect(0, 0, heatMap.res.width,
                heatMap.res.height).attr({
                fill: backgroundFill,
                "fill-opacity": parent.opacityHalf,
                stroke: parent.strokeNone
            });
            //initilize the background
            paper.add(background);

            //decalre json holders
            tempAnnotations = JSON.parse(JSONS[1].annotations);
            currentHolderArray.push(JSON.parse(JSONS[2].current));
            tempCurrent = currentHolderArray;
            tempHeatMap = JSON.parse(JSONS[0].heatmap);
            //setup json for population
            tempHeatMap = tempHeatMap.split("|");
            for (var i = 0; i < tempHeatMap.length; i++) {
                holderArray.push(JSON.parse(tempHeatMap[i]));
            }

            tempTicks = JSON.parse(JSONS[3].ticks);
            elementSetDateTicks = tempTicks;

            tempTriangles = JSON.parse(JSONS[4].triangles);

            //populate paper
            if (!graphChoice) {
                paper.add(tempTriangles);
            } else {
                paper.add(holderArray);
            }

            //populate paper with the current
            curr = paper.add(tempCurrent);
            curr.hover(
                function(e) {
                    var bbox = this.getBBox();

                    if (paper.width / 2 > e.pageX) {
                        this.marker = this.marker || paper.popup(
                            (
                                bbox.x + .7 * bbox.width) +
                            15, (
                                bbox.y + .30 * bbox.height),
                            this.node.textContent, "right",
                            7).insertBefore(
                            this);
                    } else {
                        this.marker = this.marker || paper.popup(
                            (
                                bbox.x + .7 * bbox.width) -
                            15, (
                                bbox.y + .30 * bbox.height),
                            this.node.textContent, "left",
                            7).insertBefore(
                            this);
                    }
                    this.marker.show();
                    this.attr({
                        "opacity": 1,
                        "stroke-width": 15
                    });
                },
                function(e) {
                    this.marker && this.marker.hide();
                    this.attr({
                        "opacity": .50,
                        "stroke-width": 0
                    });
                }
            )


            for (var i = 0; i < tempAnnotations.length; i++) {
                annotationHolderArray.push(tempAnnotations[i].rect);
                titleHolderArray.push(tempAnnotations[i].name);
            }

            elementSetAnnotations = paper.add(annotationHolderArray);

            for (var i = 0; i < elementSetAnnotations.length; i++) {
                elementSetAnnotations[i].node.setAttribute("value",
                    titleHolderArray[i]);
            }

            //this.node.attributes.value
            elementSetAnnotations.hover(
                //hover in
                function(e) {
                    var bbox = this.getBBox();

                    if (paper.width / 2 > e.pageX) {
                        this.marker = this.marker || paper.popup(
                            (
                                bbox.x + .7 * bbox.width) +
                            15, (
                                bbox.y + .30 * bbox.height),
                            this.node.attributes.value.value,
                            "right", 7).insertBefore(this);
                    } else {
                        this.marker = this.marker || paper.popup(
                            (
                                bbox.x + .7 * bbox.width) -
                            15, (
                                bbox.y + .30 * bbox.height),
                            this.node.attributes.value.value,
                            "left", 7).insertBefore(this);
                    }
                    this.marker.show();
                    this.attr({
                        "opacity": 1,
                        "stroke-width": 15
                    });
                },
                //hover out
                function(e) {
                    this.marker && this.marker.hide();
                    this.attr({
                        "opacity": .15,
                        "stroke-width": 2
                    });
                }
            );

            //populate paper with tick marks ?!
            elementSetDateTicks = paper.add(tempTicks);

            elementSetDateTicks.hover(

                function(e) {
                    var bbox = this.getBBox();

                    if (paper.width / 2 > e.pageX) {
                        this.marker = this.marker || paper.popup(
                            (
                                bbox.x + .7 * bbox.width) +
                            5, (
                                bbox.y + .60 * bbox.height),
                            this.node.textContent, "right",
                            7).insertBefore(
                            this);
                    } else {
                        this.marker = this.marker || paper.popup(
                            (
                                bbox.x + .7 * bbox.width) -
                            5, (
                                bbox.y + .60 * bbox.height),
                            this.node.textContent, "left",
                            7).insertBefore(
                            this);
                    }
                    this.marker.show();
                    this.attr({
                        "opacity": 1,
                        "stroke-width": 3
                    });
                },
                //hover out
                function(e) {
                    this.marker && this.marker.hide();
                    this.attr({
                        "opacity": .15,
                        "stroke-width": 0
                    });
                }
            );
            log.debug("Heatmap Complete");
        };

        /**
         * res - A holder value for the resolution of the screen
         *
         * @var array(int)
         */
        var res = heatMap.typeCheckResolution();
        /**
         * testerFunction - Creates an instance of the heatmap graph
         */
        var testerFunction = function() {
                var request = new ajax();
                request.createHeatmapRequest("single", function(e) {
                    heatMap.doHeatMap(e);
                }, function(e) {
                    heatMap.parseError(e);
                });
            }
            /**
             * init - An initilization function
             */
        var initilize = function() {
            testerFunction();
        }

        testerFunction();

        return {
            heatMap: heatMap,
            testerFunction: testerFunction,
            init: initilize
        }

    });
');

//$simplehtml->display();
echo $OUTPUT->footer();

?>
