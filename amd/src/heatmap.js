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
 * The heatmap library for Project Grade-Up
 * @package Project Grade-Up
 * @copyright 2015 Dalin Williams
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Dalin Williams <dalinwilliams@gmail.com>
 */

/* jshint ignore:start */
define(["block_projectgradeup/core", "jquery", "block_projectgradeup/artifact",
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

        return {
            heatMap: heatMap,
            testerFunction: testerFunction,
            init: initilize
        }

    });
/* jshint ignore:end */
