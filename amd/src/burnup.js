//
// Copyright (c) 2015 by Placeholder Corporation. All Rights Reserved.
//

/**
 * @package Project Grade-Up
 * @copyright 2015 Dalin Williams
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Dalin Williams <dalinwilliams@gmail.com>
 */
/* jshint ignore:start */
define(["block_projectgradeup/core", "jquery", "block_projectgradeup/artifact",
    "core/log",
    "block_projectgradeup/ajax", M.cfg.wwwroot +
    "/blocks/projectgradeup/externaljs/raphael.js"
], function(parent, $, artifact, log, ajax) {
    //initilize parent to the strings object
    parent = parent.strings;
    require([M.cfg.wwwroot +
        "/blocks/projectgradeup/externaljs/g.raphael-min.js"
    ]);
    var burnupJsonsCreator = function(artifacts, res) {
        this.artifactManager = artifacts;
        this.artifactCollection = artifacts.collection;
        //artifacts.collection;
        //type check the resolution
        var hres = (typeof res == undefined) ? this.typeCheckResolution(
            res) : res;
        this.resolution = [];
        this.resolution[0] = hres.width;
        this.resolution[1] = hres.height;
        this.selectedArtifacts = [];
    }

    burnupJsonsCreator.prototype = Object.create(burnupJsonsCreator.prototype);
    burnupJsonsCreator.prototype.constructor = burnupJsonsCreator;
    /**
     * burnupJsonsCreator - The object which contains all calculations and methods for the creation of the Burnup chart
     */
    burnupJsonsCreator.prototype = {
        /**
         * createArtifacts - creates a collection of artifacts to use in the calculations
         * @return array(objects) An array of artifacts
         */
        createArtifacts: function() {
            //log.debug("About to clone mate");
            //var res = parent.clone(this.resolution);
            var res = this.resolution;
            //log.debug("we are trying to use this resolution");
            //log.debug(this.resolution);

            //log.debug("Cloned mate");
            var currentResX = res[0];
            var currentResY = res[1];

            //var data = parent.clone(this.artifactCollection);
            var data = this.artifactCollection;
            //initilize all the arrays

            var pointsBL = [];
            var pointsBR = [];
            var pointsTL = [];
            var pointsTR = [];

            var status = [];
            var urls = [];
            var set = [];
            //ilitilize temp variables
            var x = 0;
            var y = currentResY;
            var tX = 0;
            var tY = currentResY;
            //clear selected artifact
            this.selectedArtifacts = [];
            //log.debug(currentResX);
            //log.debug(currentResY);

            //calculate artifacts!
            for (var i = 0; i < data.length; i++) {
                if ((data[i].status != "notdue") && (data[i].status !=
                        "notgraded")) {

                    tX = ((data[i].category * data[i].weight) *
                        currentResX) + x;
                    tY = ((((data[i].category * data[i].weight) *
                            currentResY) * -1) * data[i].grade) +
                        y;
                    pointsBL.push([x, currentResY]);
                    pointsBR.push([tX, currentResY]);
                    pointsTL.push([x, y]);
                    pointsTR.push([tX, tY]);
                    status.push(data[i].status);
                    urls.push(data[i].url);
                    x = tX;
                    y = tY;
                    this.selectedArtifacts.push(data[i]);
                }
            }
            //calculate artifacts for notdue and notgraded artifacts
            for (var i = 0; i < data.length; i++) {
                if ((data[i].status == "notdue") || (data[i].status ==
                        "notgraded")) {

                    tX = ((data[i].category * data[i].weight) *
                        currentResX) + x;
                    tY = ((((data[i].category * data[i].weight) *
                        currentResY) * -1) * 1) + y;
                    pointsBL.push([x, currentResY]);
                    pointsBR.push([tX, currentResY]);
                    pointsTL.push([x, y]);
                    pointsTR.push([tX, tY]);
                    status.push(data[i].status);
                    urls.push(data[i].url);
                    x = tX;
                    y = tY;

                    this.selectedArtifacts.push(data[i]);
                }
            }
            //fourm all point  sets into the finial set
            for (var i = 0; i < pointsBL.length; i++) {
                set.push([pointsBL[i], pointsTL[i], pointsTR[i],
                    pointsBR[i], pointsBL[i], status[i],
                    urls[i]
                ]);
            }
            //log.debug(set);
            //log.debug("tester");
            return set;
        },
        /**
         * createTrendLine - Creates the trendline raphael JSON
         *
         * @return string JSON string
         */
        createTrendLine: function() {
            //var res = parent.clone(this.resolution);
            var res = this.resolution;
            var currentResX = res[0];
            var currentResY = res[1];

            //var data = parent.clone(this.artifactCollection);
            var data = this.artifactCollection;
            //initilize all the arrays
            var pointsTL = [];
            var pointsTR = [];

            //ilitilize temp variables
            var x = 0;
            var y = currentResY;
            var tX = 0;
            var tY = currentResY;
            var finialResult = [];

            //create a set of pointsBL
            for (var i = 0; i < data.length; i++) {
                if ((data[i].status != "notdue") && (data[i].status !=
                        "notgraded")) {
                    tX = ((data[i].category * data[i].weight) *
                        currentResX) + x;
                    tY = ((((data[i].category * data[i].weight) *
                            currentResY) * -1) * data[i].grade) +
                        y;

                    pointsTL.push([x, y]);
                    pointsTR.push([tX, tY]);
                    x = tX;
                    y = tY;

                }
            }

            for (var i = 0; i < pointsTL.length; i++) {
                finialResult.push(pointsTL[i]);
            }
            finialResult.push([x, y]);
            return finialResult;

        },
        /**
         * createGradeRanges - Creates the grade ranges and returns the JSON
         *
         * @return array(object) The grade ranges in JSON format
         */
        createGradeRanges: function() {
            //var holder = parent.clone(this.resolution);
            var holder = this.resolution;
            //log.debug("while the grader is using this resolution");
            //log.debug(this.resolution);
            var currentResX = holder[0];
            var currentResY = holder[1];

            var a = [
                [0, currentResY],
                [currentResX, (currentResY * 0)],
                [currentResX, (currentResY * .1)],
                [0, currentResY]
            ];

            var b = [
                [0, currentResY],
                [currentResX, (currentResY * .1)],
                [currentResX, (currentResY * .2)],
                [0, currentResY]
            ];

            var c = [
                [0, currentResY],
                [currentResX, (currentResY * .2)],
                [currentResX, (currentResY * .3)],
                [0, currentResY]
            ];

            var d = [
                [0, currentResY],
                [currentResX, (currentResY * .3)],
                [currentResX, (currentResY * .4)],
                [0, currentResY]
            ];

            return [a, b, c, d];
        },
        /**
         * createProjections - Calculates and genereates the projections
         *
         * @return array(object) The grade projections in JSON format
         */
        createProjections: function(classAverage) {
            //var res = parent.clone(this.resolution);
            var res = this.resolution;
            var currentResX = res[0];
            var currentResY = res[1];

            //var data = parent.clone(this.artifactCollection);
            var data = this.artifactCollection;
            //initilize all the arrays
            var xList = [];
            var yList = [];

            //ilitilize temp variables
            var x = 0;
            var y = currentResY;
            var tX = 0;
            var tY = currentResY;

            //create a set of pointsBL
            for (var i = 0; i < data.length; i++) {
                if ((data[i].status != "notdue") && (data[i].status !=
                        "notgraded")) {
                    tX = ((data[i].category * data[i].weight) *
                        currentResX) + x;
                    tY = ((((data[i].category * data[i].weight) *
                            currentResY) * -1) * data[i].grade) +
                        y;

                    xList.push(x);
                    yList.push(y);

                    x = tX;
                    y = tY;

                    if (i === data.length) {
                        break;
                    }

                }
            }
            //calculate the projection slope
            var currentProjectionSlope = ((y - currentResY) / (
                x));
            //calculate the projection slope in relation to two points
            var mProjection = ((currentProjectionSlope * (
                currentResX - x)) + y);
            //find the slope for the best possiable
            var mOfOne = ((currentResY - 0) / (0 - currentResX));
            //calculate all possible
            var bestPossible = [
                [x, y],
                [currentResX, (((mOfOne * (currentResX - x)) +
                    y))]
            ];
            //calculate worst possible
            var worstPossible = [
                    [x, y],
                    [currentResX, y]
                ]
                //calculate genneral projection
            var projection = [
                [x, y],
                [currentResX, mProjection]
            ];
            //if classAverage is not empty, include it
            if (typeof classAverage !== "undefined") {
                return [bestPossible, worstPossible, projection,
                    classAverage
                ];
            }
            return [bestPossible, worstPossible, projection];
        },
        /**
         * createCompletedArea - Calculate the completed area and returns the JSON
         *
         * @return string The completed area
         */
        createCompletedArea: function() {
            //var res = parent.clone(this.resolution);
            var res = this.resolution;
            var currentResX = res[0];
            var currentResY = res[1];

            //var data = parent.clone(this.artifactCollection);
            var data = this.artifactCollection;
            //initilize all the arrays
            var xList = [];
            var yList = [];

            //ilitilize temp variables
            var x = 0;
            var y = currentResY;
            var tX = 0;
            var tY = currentResY;

            //create arrays
            var points = [];

            //create a set of pointsBL
            for (var i = 0; i < data.length; i++) {
                if ((data[i].status != "notdue") && (data[i].status !=
                        "notgraded")) {
                    tX = ((data[i].category * data[i].weight) *
                        currentResX) + x;
                    tY = ((((data[i].category * data[i].weight) *
                            currentResY) * -1) * data[i].grade) +
                        y;
                    points.push([x, y]);

                    x = tX;
                    y = tY;
                }
            }

            points.push([0, currentResX]);

            return points;
        },
        produceCompleteArray: function() {
            log.debug("Started...");
            //initilize arrays
            var tempres;
            var artifactsResult = [];
            var trendLineResult = [];
            var gradeAreaResults = [];
            var currAreaResults = [];
            var projectionResults = [];
            var finialResult = [];

            //create artifacts
            var setArti = this.createArtifacts();

            //create sets of said points
            for (var i = 0; i < setArti.length; i++) {
                artifactsResult.push("M " + setArti[i][0].join() +
                    " L " + setArti[i][1].join() + " L " +
                    setArti[i][2].join() + " L " + setArti[
                        i][3].join() + " L " + setArti[i][4]
                    .join());
            }
            log.debug("Created Artifacts...");

            //create trend line
            var setTrend = this.createTrendLine();
            for (var i = 0; i < setTrend.length; i++) {
                if (i != 0) {
                    trendLineResult.push(" L " + setTrend[i].join());
                } else {
                    trendLineResult.push("M " + setTrend[i].join());
                }
            }
            log.debug("Created Trend Lines...");

            //create completed area
            var setAreas = this.createGradeRanges();
            for (var i = 0; i < setAreas.length; i++) {
                gradeAreaResults.push("M " + setAreas[i][0].join() +
                    " L " + setAreas[i][1].join() + " L " +
                    setAreas[i][2].join() + " L " +
                    setAreas[i][3].join());
            }
            log.debug("Created Areas...");

            //create completed area
            var setCurrArea = this.createCompletedArea();
            currAreaResults.push("M " + setCurrArea[0].join());
            //log.debug(setCurrArea);
            for (var i = 0; i < setCurrArea.length; i++) {
                //log.debug(setCurrArea[i].join());
                if (i != 0) {
                    currAreaResults.push(" L " + setCurrArea[i]
                        .join());
                }
            }
            log.debug("Created Current Area...");

            //create grade projections
            var setProjections = this.createProjections();
            //log.debug(setProjections);
            for (var i = 0; i < setProjections.length; i++) {
                projectionResults.push("M " + setProjections[i]
                    [0].join() + " L " + setProjections[i][
                        1
                    ].join());
            }
            log.debug("Created Projections...");

            //begin wrappingall resullts into JSONS
            var res = [];
            //bundle artifacts
            for (var i = 0; i < artifactsResult.length; i++) {
                var status = setArti[i];
                //if the status is not due, we give it a zero and do not color it. Note: these sections should be the "last"
                if (status[5] === "notdue") {
                    res.push({
                        type: "path",
                        path: artifactsResult[i],
                        opacity: 0.5,
                        "stroke-width": 1
                    });
                }
                //if the item is incomplete, mark it as red
                else if (status[5] === "incomplete" || status[5] ===
                    "incompleted") {
                    res.push({
                        type: "path",
                        path: artifactsResult[i],
                        fill: "#ca102c",
                        opacity: 1.0,
                        "stroke-width": 1//,
                        //href: status[6]
                    });
                }
                //if an item is graded, we set it to the defualt color
                else if (status[5] === "complete" || status[5] ===
                    "completed") {
                    res.push({
                        type: "path",
                        path: artifactsResult[i],
                        fill: "#323232",
                        opacity: 1.0,
                        "stroke-width": 1//,
                        //href: status[6]
                    });
                }
                //if an item is not graded, we do not do anything
                else if (status[5] === "notgraded") {
                    res.push({
                        type: "path",
                        path: artifactsResult[i],
                        opacity: 0.5,
                        "stroke-width": 1
                    });
                }
                //if I am in show all, and I want to show all ungraded, change to yellow
                else if (status[5] === "notgradedshow") {
                    res.push({
                        type: "path",
                        path: artifactsResult[i],
                        fill: "#fff80b",
                        opacity: 1.0,
                        "stroke-width": 1//,
                        //href: status[6]
                    });
                }
                //if I am in show all, anmd I want to show all undue, show and change to blue
                else if (status[5] === "notdueshow") {
                    res.push({
                        type: "path",
                        path: artifactsResult[i],
                        fill: "#aff80b",
                        opacity: 1.0,
                        "stroke-width": 1//,
                        //href: status[6]
                    });
                } else {
                    res.push({
                        type: "path",
                        path: artifactsResult[i],
                        fill: "#af1ff8",
                        opacity: 1.0,
                        "stroke-width": 1//,
                        //href: status[6]
                    });
                }
            }

            //bundle artifacts
            tempres = JSON.stringify(res);
            finialResult.push(JSON.stringify({
                ArtifactsJSON: tempres
            }));
            log.debug("Artifact Production Completed");
            //reset res
            res = [];

            res.push({
                type: "path",
                path: gradeAreaResults[0],
                fill: "#81c4ff",
                opacity: 0.5,
                stroke: "none"
            });

            res.push({
                type: "path",
                path: gradeAreaResults[1],
                fill: "#82ffab",
                opacity: 0.5,
                stroke: "none"
            });

            res.push({
                type: "path",
                path: gradeAreaResults[2],
                fill: "#ffbf48",
                opacity: 0.5,
                stroke: "none"
            });

            res.push({
                type: "path",
                path: gradeAreaResults[3],
                fill: "#ff4600",
                opacity: 0.5,
                stroke: "none"
            });

            //bundle the grade areas
            tempres = JSON.stringify(res);
            finialResult.push(JSON.stringify({
                GradesJSON: tempres
            }));
            log.debug("Grade Regions Production Completed");
            //reset res
            res = [];

            //bundle and initilize the trendline
            res.push({
                type: "path",
                path: trendLineResult.join(" "),
                "stroke": "#333333",
                "stroke-width": 5
            });

            tempres = JSON.stringify(res);
            finialResult.push(JSON.stringify({
                GradeTrendJSON: tempres
            }));
            log.debug("Grade Trends Production Completed");
            //reset res
            res = [];

            //wrapup projections
            res.push({
                type: "path",
                path: projectionResults[0],
                "stroke-width": 3,
                stroke: "#5aa7bb"
            });

            res.push({
                type: "path",
                path: projectionResults[1],
                "stroke-width": 3,
                stroke: "#a32d24"
            });

            res.push({
                type: "path",
                path: projectionResults[2],
                "stroke-width": 3,
                stroke: "#a32df4"
            });

            //bundle projections
            tempres = JSON.stringify(res);
            finialResult.push(JSON.stringify({
                ProjectionJSON: tempres
            }));
            log.debug("Projections Production Completed");
            //reset res
            res = [];

            //wrapup current completed area
            res.push({
                type: "path",
                path: currAreaResults.join(" "),
                "stroke-width": 3,
                opacity: 0.5
            });

            //bundle this area
            tempres = JSON.stringify(res);
            finialResult.push(JSON.stringify({
                CompletedArea: tempres
            }));
            log.debug("Area Production Completed");
            //reset res
            res = [];

            //get teh finial item
            var name = this.getTitle();
            res.push(name);
            tempres = JSON.stringify(name);
            res = [];
            finialResult.push(JSON.stringify({
                Title: tempres
            }));
            //get the projection lables
            //log.debug("Here is")
            //log.debug(setProjections);
            //log.debug("=================================");
            var projectionLabels = this.getProjectionGrades(
                setProjections);
            res.push(projectionLabels);
            tempres = JSON.stringify(projectionLabels);
            finialResult.push(JSON.stringify({
                ProjLabels: tempres
            }));
            log.debug("Labels Production Completed");
            //create the final encode
            var data = JSON.stringify(finialResult);

            return data;
        },
        typeCheckResolution: function(res) {
            if (typeof res === "undefined") {
                res = {};
                res.width = (document.getElementById(parent.burnupPresentationDivId)
                    .offsetWidth == 0) ? 1080 : document.getElementById(
                    parent.burnupPresentationDivId).offsetWidth;
                res.height = (2 / 3) * res.width;
            } else if (!("width" in res) || !("height" in res)) {
                res = {};
                res.width = (document.getElementById(parent.burnupPresentationDivId)
                    .offsetWidth == 0) ? 1080 : document.getElementById(
                    parent.burnupPresentationDivId).offsetWidth;
                res.height = (2 / 3) * res.width;
            } else if (Array.isArray(res) && res.length == 2) {
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
                    res.width = (document.getElementById(parent
                            .burnupPresentationDivId).offsetWidth ==
                        0) ? 1080 : document.getElementById(
                        parent.burnupPresentationDivId).offsetWidth;
                    res.height = (2 / 3) * res.width;
                }
            }
            return res;
        },
        typeCheckColorBlind: function(cb) {
            if (typeof cb === "undefined") {
                cb = $(parent.colorblindCheckBoxId).is(
                    ":checked");
            } else if (typeof colorblind !== "boolean") {
                cb = $(parent.colorblindCheckBoxId).is(
                    ":checked");
            }
            return (cb) ? 1 : 0;
        },
        getTitle: function() {
            //var data = parent.clone(this.selectedArtifacts);
            var data = this.selectedArtifacts;
            var result = [];
            for (var i = 0; i < data.length; i++) {
                result.push({
                    title: data[i].title,
                    grade: data[i].grade,
                    index: i
                });
            }
            return result;
        },
        getProjectionGrades: function(projections) {
            //var data = parent.clone(this.artifactCollection);
            var data = projections; //this.artifactCollection;
            //get the resolution
            //var res = parent.clone(this.resolution);
            var res = this.resolution;
            var y = res[1];
            var result = [];
            //build the results array
            //log.debug("The data");
            //log.debug(data);
            result.push({
                title: "Best Possible",
                grade: (((data[0][1][1] / y) - 1) * -
                    100)
            });
            result.push({
                title: "Worst Posssible",
                grade: (((data[1][1][1] / y) - 1) * -
                    100)
            });
            result.push({
                title: "Current Projection",
                grade: (((data[2][1][1] / y) - 1) * -
                    100)
            });

            if (result.length == 4) {
                result.push({
                    title: "Class Average",
                    grade: (((data[3][1][1] / y) - 1) *
                        -100)
                });
            }
            return result;
        }
    };



    //all of the sets are initilized
    var svgContainer,
        paper,
        elementSetArtifacts,
        elementSetRegions,
        elementSetGeneral,
        elementSetText,
        elementSetLines,
        elementSetArticatctBoundingBox,
        elementSetGradeMeasurements;
    //hover handler variables
    var posx, posy, bbox, tempBBox;

    /**
     * Set of functions used on the burn up chart
     *
     * @type burnup object
     */
    burnup = {
        /**
         * res - burnup\"s resolution
         *
         * @var res object(int)
         */
        res: {
            width: 0,
            height: 0
        },
        /**
         * artifactCollection - The artifact collection for burnup, initilized to null
         *
         * @var artifactCollection array(objects)
         */
        artifactCollection: null,
        /**
         * parseError - Parses all errors thrown by the controler
         *
         * @param string e The incoming string of error
         */
        parseError: function(e) {
            debug.log(
                "Oh nose! An error was found in projectgradeup/burnup.js::getData"
            )
            debug.log(e);
            $(parent.presentationLoadingDiv).html(
                "<h1>Sorry, teacher-student view is not ready, please log in as one of your students to view their charts</h1>"
            );
        },
        /**
         * typeCheckResolution - A function to check and format the resolution according to the burnups standards
         *
         * @param object(int) res A optional resolution object for type-checking
         * @return object(int) A resolution object with a designated with and height
         */
        typeCheckResolution: function(res) {
            if (typeof res === "undefined") {
                res = {};
                res.width = (document.getElementById(parent.burnupPresentationDivId)
                    .offsetWidth == 0) ? 1080 : document.getElementById(
                    parent.burnupPresentationDivId).offsetWidth;
                res.height = (2 / 3) * res.width;
            } else if (!("width" in res) || !("height" in res)) {
                res = {};
                res.width = (document.getElementById(parent.burnupPresentationDivId)
                    .offsetWidth == 0) ? 1080 : document.getElementById(
                    parent.burnupPresentationDivId).offsetWidth;
                res.height = (2 / 3) * res.width;
            } else if (Array.isArray(res) && res.length == 2) {
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
                    res.width = (document.getElementById(parent
                            .burnupPresentationDivId).offsetWidth ==
                        0) ? 1080 : document.getElementById(
                        parent.burnupPresentationDivId).offsetWidth;
                    res.height = (2 / 3) * res.width;
                }
            }
            return res;
        },
        /**
         * doBurnup - A function to initilize the burnup process
         *
         * @param string e The string of JSON objects which create the burnup chart
         */
        doBurnup: function(e) {
            //clear and display loading
            log.debug("Loading Burnup...");
            $(parent.burnupPresentationDiv).empty();
            var error = JSON.parse(e);
            if (error.hasOwnProperty("error")) {
                log.debug(error.error);
                log.debug(error.stacktrace);
                $(parent.burnupPresentationDiv).html("<h3>" +
                    error.debuginfo + "</h3>");
                log.debug(error.debuginfo);
                //$(parent.burnupPresentationDiv).html() = invalidParamsFunc(error.debuginfo);
            } else {
                this.res = this.typeCheckResolution();
                log.debug("Creating Burnup");
                var going = JSON.parse(e);
                loadGraph(going);
            }


        },
        /**
         * doPureBurnup - Re-creates a new burnup no matter what data is passed
         *
         * @param string e Incoming JSON objects
         */
        doPureBurnup: function(e) {
            log.debug("Loading Burnup...");
            $(parent.burnupPresentationDiv).empty();
            var error = JSON.parse(e);
            if (error.hasOwnProperty("error")) {
                log.debug(error.error);
                log.debug(error.stacktrace);
                $(parent.burnupPresentationDiv).html("<h3>" +
                    error.debuginfo + "</h3>");
                log.debug(error.debuginfo);
                //$(parent.burnupPresentationDiv).html() = invalidParamsFunc(error.debuginfo);
            } else {
                this.res = this.typeCheckResolution();
                this.artifactCollection = new artifact(e);
                //parent.artifactCollection = this.artifactCollection;
                this.artifactCollection = this.artifactCollection;
                var creator = new burnupJsonsCreator(this.artifactCollection,
                    this.res);
                var JSONS = creator.produceCompleteArray();
                log.debug("Creating Burnup!");
                loadGraph(JSONS);
            }

        },
        /**
         * refreshBurnup - Reloads the bunrup chart with data while keeping some features loaded
         */
        refreshBurnup: function() {
            log.debug("Loading Burnup...");
            $(parent.burnupPresentationDiv).empty();
            var creator = new burnupJsonsCreator(this.artifactCollection,
                this.res);
            //var creator = new burnupJsonsCreator(parent.artifactCollection, this.res);
            var JSONS = creator.produceCompleteArray();
            log.debug("Creating Burnup!");
            loadGraph(JSONS);
        },
        /**
         * setAllDivAttr - A helper function to get all data concerning individual artifacts
         *
         * @param array(object) elementSet The incoming set of elemets in which the information lies
         * @param array(object) artifactAttr The array of objects ids in which we must search
         * @param string name The class name of the context menu
         */
        setAllDivAttr: function(elementSet, artifactAttr, name) {
            var className = ((typeof name === "undefined") ?
                parent.contextMenuDefultDivId : name);
            for (var i = 0; i < elementSet.length; i++) {
                elementSet[i].node.setAttribute("class",
                    className + "." + i);
                elementSet[i].node.setAttribute("value",
                    artifactAttr[i].title);
                elementSet[i].node.setAttribute("id",
                    artifactAttr[i].index);
            }
        },
        /**
         * setOneDivAttr - DEPRICATED
         */
        setOneDivAttr: function(element, name, id, title) {
            var className = ((name === undefined) ? parent.contextMenuDefultDivId :
                name);
            element.node.setAttribute("class", className);
            if (typeof id !== "undefined") {
                element.node.setAttribute("id", id);
            }
            if (typeof id !== "undefined") {
                element.node.setAttribute("value", title);
            }
        },
        /**
         * getProjectionsData - Gets all data for the projections
         *
         * @param array(object) elementSet The collection of elements containing the projection data
         * @param projectionAttr A collection containing the projection element ids\
         * @return array(object) Information concerning the projection text
         */
        getPorjectionsData: function(elementSet, projectionAttr) {
            var projText = {};
            for (var i = 0; i < elementSet.length; i++) {
                var holder = elementSet[i].id.toString();
                projText[holder] = projectionAttr[i];
            }
            return projText;
        },
        /**
         * getArtifactsData - Gets the artifact data
         *
         * @param array(object) elementSet The incoming set of data-containing elements
         * @param array(object) artifactAttr The set of artifacts from which we are extracting the data
         * @return string The artifact name and other misc. information contained within the element
         */
        getArtifactsData: function(elementSet, artifactAttr) {
            var artiText = [];
            for (var i = 0; i < elementSet.length; i++) {
                //adding text to each artifact
                var holder = elementSet[i].id.toString();
                artiText[holder] = artifactAttr[i];
            }
            return artiText;
        },
        /**
         * getLowestProjectionGrade - Get the lowest projected grade
         *
         * @param array(object) elementSet The set of elements in which our information lies
         * @param array(object) projectionAttr The attributes of the projection data
         * @return int The lowest grade projection value
         */
        getLowestProjectionGrade: function(elementSet,
            projectionAttr) {
            var lowestGrade = Number.MAX_SAFE_INTEGER;
            for (var i = 0; i < elementSet.length; i++) {
                if (projectionAttr[i].grade < lowestGrade) {
                    lowestGrade = projectionAttr[i].grade;
                }
            }
            return lowestGrade;
        },
        /**
         * DEPRECATED
         */
        openInNewTab: function(url) {
            var win = window.open(url, "_blank");
            win.focus();
        }

    };

    /**
     * A set of functions used for events (user based) on the burnup chart
     *
     * @var interactionFunctions
     */
    var interactionFunctions = {
        /**
         * hoverHandler - Handels all hover functionality
         *
         * @param object e The event object
         */
        hoverHandler: function(e) {
            elementSetArtifacts.forEach(function(arti) {
                tempBBox = arti.getBBox();
                if (Raphael.isPointInsideBBox(tempBBox,
                        posx, posy)) {

                    interactionFunctions.inMouse(arti);
                } else {
                    interactionFunctions.outMouse(arti);
                }
            });
        },
        /**
         * inMouse - Handels all mouse-in-element events
         *
         * @param object e The event object
         */
        inMouse: function(e) {
            hoverAnimation = Raphael.animation({
                fill: parent.artifactColorOnHover,
                opacity: parent.opacityFull
            }, parent.hoverAnimationWait);
            e.animate(hoverAnimation.delay(parent.hoverAnimationDelay));
        },
        /**
         * outMouse - Handels all mouse-out-element events
         *
         * @param object e The event object
         */
        outMouse: function(e) {
            hoverAnimation = Raphael.animation({
                opacity: parent.opacityNone
            }, parent.hoverAnimationWait);
            e.animate(hoverAnimation.delay(parent.hoverAnimationDelay));
        }
    };
    //extend jquery function
    $.fn.exists = function() {
            return this.length > 0;
        }
        /**
         * Function which prepares the raphael visual representation (svg)
         *
         * @param {json} incomming_data
         * @returns void
         */
    loadGraph = function(JSONS) {
        //display then remove a small loading emblem
        if ($(parent.presentationLoadingDiv).exists()) {
            $(parent.presentationLoadingDiv).remove();
        }
        //temporary raphael sets
        var tempArti,
            tempGrades,
            tempTrends,
            tempProjections,
            tempNotches = [];
        //temporary holders of data pertaining to the hoverover events
        var artifactText,
            projectionText,
            artiText,
            projText,
            lowestGrade;

        var windowSize = {
            windowHeight: window.screen.height,
            windowWidth: window.screen.depth
        };
        //initilize the paper and the element sets
        svgContainer = document.getElementById(parent.burnupPresentationDivId);
        paper = Raphael(svgContainer, burnup.res.width, burnup.res.height);
        elementSetArtifacts = paper.set();
        elementSetRegions = paper.set();
        elementSetGeneral = paper.set();
        elementSetText = paper.set();
        elementSetLines = paper.set();
        elementSetArticatctBoundingBox = paper.set();
        elementSetGradeMeasurements = paper.set();
        //initilize the background
        var background = paper.rect(0, 0, burnup.res.width, burnup.res
            .height).attr({
            fill: parent.rectangleColor,
            "fill-opacity": parent.opacityHalf,
            stroke: parent.strokeNone
        });

        var inLimit = true;
        var percentage = 0;
        var strokeWidth = 2; //burnup.res.height/100;

        /*elementSetGradeMeasurements.push({
            paper.path(Raphael.format("M{0},{1}L{2},{3}", , , , )),//.attr({"text : A, \"stroke-width\" : 3"});
            paper.path(Raphael.format("M{0},{1}L{2},{3}", burnup.res.width*.95, burnup.res.height*.85, burnup.res.weight, burnup.res.height*.85)),//.attr({"text : B, \"stroke-width\" : 3"});
            paper.path(Raphael.format("M{0},{1}L{2},{3}", burnup.res.width*.95, burnup.res.height*.75, burnup.res.weight, burnup.res.height*.75)),//.attr({"text : C, \"stroke-width\" : 3"});
            paper.path(Raphael.format("M{0},{1}L{2},{3}", burnup.res.width*.95, burnup.res.height*.65, burnup.res.weight, burnup.res.height*.65))//.attr({"text : D, \"stroke-width\" : 3"});
        });*/

        //initilize the background
        paper.add(background);
        //load in the JSON into the temporary variables
        tempArti = paper.add(JSON.parse((JSON.parse(JSON.parse(
            JSONS)[0]).ArtifactsJSON)));
        tempGrades = paper.add(JSON.parse((JSON.parse(JSON.parse(
            JSONS)[1]).GradesJSON)));
        tempTrends = paper.add(JSON.parse((JSON.parse(JSON.parse(
            JSONS)[2]).GradeTrendJSON)));
        tempProjections = paper.add(JSON.parse((JSON.parse(JSON.parse(
            JSONS)[3]).ProjectionJSON)));
        //set the element sets. NOTE: order of initilization matters
        elementSetArtifacts = tempArti.clone();
        elementSetGeneral = tempGrades.clone();
        elementSetGeneral.push(tempTrends.clone());
        elementSetArticatctBoundingBox = tempArti.clone();
        elementSetLines = tempProjections.clone();
        //gather data pertaining to the hoverover element
        artifactText = JSON.parse(JSON.parse(JSON.parse(JSONS)[5]).Title);
        projectionText = JSON.parse(JSON.parse(JSON.parse(JSONS)[6])
            .ProjLabels);
        artiText = burnup.getArtifactsData(
            elementSetArticatctBoundingBox, artifactText);
        projText = burnup.getPorjectionsData(elementSetLines,
            projectionText);
        lowestGrade = burnup.getLowestProjectionGrade(
            elementSetLines, projectionText);
        //add attributes to all bounding boxes
        burnup.setAllDivAttr(elementSetArticatctBoundingBox,
            artifactText);
        //initilize the hoverover handeler
        elementSetArticatctBoundingBox.attr({
            fill: parent.artifactColorInBBox,
            stroke: parent.strokeInBBox,
            "stroke-width": 1,
            opacity: 0.3
        }).hover(
            function(e) {
                bbox = this.getBBox();
                if (paper.width / 2 > e.pageX) {
                    this.marker = this.marker || paper.popup((
                            bbox.x + .5 * bbox.width), (
                            bbox.y + .30 * bbox.height),
                        artiText[this.id.toString()].title +
                        parent.hoverMessageGrade + artiText[
                            this.id.toString()].grade.toFixed(
                            2) * 100 + "%", "right", 5).insertBefore(
                        this);
                } else {
                    this.marker = this.marker || paper.popup((
                            bbox.x + .5 * bbox.width), (
                            bbox.y + .30 * bbox.height),
                        artiText[this.id.toString()].title +
                        parent.hoverMessageGrade + artiText[
                            this.id.toString()].grade.toFixed(
                            2) * 100 + "%", "left", 5).insertBefore(
                        this);
                }
                this.marker.show();
                //posx = e.pageX - $(document).scrollLeft() - $(parent.burnupPresentationDiv).offset().left;
                // posy = e.pageY - $(document).scrollTop() - $(parent.burnupPresentationDiv).offset().top;
                // Pass it on to the Hover Event Handeler
                interactionFunctions.hoverHandler(e);
            },
            function(e) {
                this.marker && this.marker.hide();
                posx = e.pageX - $(document).scrollLeft() - $(
                    parent.burnupPresentationDiv).offset().left;
                posy = e.pageY - $(document).scrollTop() - $(
                    parent.burnupPresentationDiv).offset().top;
                // Pass it on to the Hover Event Handeler
                interactionFunctions.hoverHandler(e);
            }
        ).mousedown(function(e) {
            if (e.which == 3) {
                //parent.popupIdHolder = this.node.id;
                parent.presentationLoadingDivId = this.node
                    .id;
                $(parent.burnupPopupDiv).dialog("open"); //parent.burnupPopupDiv

                var tester = 0;
            }
        });
        //initilize hover over handler for the projections
        elementSetLines.hover(
            function(e) {
                bbox = this.getBBox();
                var offset = $(parent.burnupPresentationDiv).offset();
                var realX = e.pageX - offset.left;
                var realY = e.pageY - offset.top;
                this.attr({
                    "stroke-width": 10
                });
                this.marker = paper.popup(realX - bbox.width,
                    realY, projText[this.id.toString()].title +
                    " : " + projText[this.id.toString()].grade
                    .toFixed(2), "left", 5).insertBefore(
                    this);
                this.marker.show();
            },
            function(e) {
                this.attr({
                    "stroke-width": 3
                });
                this.marker && this.marker.hide();
            }
        );

        while (inLimit) {
            if (percentage < .5) {
                tempNotches = paper.path("M" + burnup.res.width * .89 +
                    "," + (burnup.res.height * percentage) +
                    "L" + burnup.res.width + "," + burnup.res.height *
                    percentage).attr({
                    strioke: "0DF0FD",
                    "stroke-width": strokeWidth,
                    title: ((1 - percentage) * 100)
                });
            } else if (percentage < .7) {
                tempNotches = paper.path("M" + burnup.res.width * .95 +
                    "," + (burnup.res.height * percentage) +
                    "L" + burnup.res.width + "," + burnup.res.height *
                    percentage).attr({
                    strioke: "0DF0FD",
                    "stroke-width": strokeWidth,
                    title: ((1 - percentage) * 100)
                });
            } else {
                tempNotches = paper.path("M" + burnup.res.width * .97 +
                    "," + (burnup.res.height * percentage) +
                    "L" + burnup.res.width + "," + burnup.res.height *
                    percentage).attr({
                    strioke: "0DF0FD",
                    "stroke-width": strokeWidth,
                    title: ((1 - percentage) * 100)
                });
            }
            percentage += .1;
            elementSetGradeMeasurements.push(tempNotches);
            if (percentage >= 1) {
                break;
            }
        }
        //add percentage numbers
        percentage = 0.0;
        while(percentage < 1){
            if(percentage == 0){
                paper.text((paper.width-((paper.width*.015)*5)),paper.height-((paper.height*.015)*2),percentage+"%").attr({"font-size": (paper.width*.015)+"px", "font-weight": "800", fill: "grey", stroke:"black", "stroke-width": (paper.width*.0015)+"px","text-anchor": "start"});
            }
            else{
                paper.text((paper.width-((paper.width*.015)*5)),paper.height-(paper.height*percentage)-((paper.height*.015)*2),Math.round(percentage*100)+"%").attr({"font-size": (paper.width*.015)+"px", "font-weight": "800", fill: "grey", stroke:"black", "stroke-width": (paper.width*.0015)+"px","text-anchor": "start"});
            }
            percentage += 0.1;
        }



        //initilize hover over handeler for ticks
        elementSetGradeMeasurements.hover(
            function(e) {
                bbox = this.getBBox();
                var offset = $(parent.burnupPresentationDiv).offset();
                var realX = e.pageX - offset.left;
                var realY = e.pageY - offset.top;
                this.attr({
                    "stroke-width": 10
                });
                this.marker = paper.popup(realX - bbox.width,
                    realY, parseInt(this.node.textContent)+"%",
                    "left", 5).insertBefore(this);
                this.marker.show();
            },
            function(e) {
                this.attr({
                    "stroke-width": 2
                });
                this.marker && this.marker.hide();
            }
        );
    };
    /**
     * An extension to the document class
     */
    document.oncontextmenu = function() {
            return false;
        }
        /**
         * tessterFunction - A place-holder function for the creation of the burnup chart
         */
    var testerFunction = function() {
            var request = new ajax();
            request.createArtifactRequest("single", function(e) {
                burnup.doPureBurnup(e);
            }, function(e) {
                heatMap.parseError(e);
            });
        }
        /**
         * init - Moodles requirejs fucniton
         */
    var initilize = function() {
            testerFunction();
        }
        //testerFunction();

    return {
        bunrup: burnup,
        testerfunction: testerFunction,
        init: initilize
    }

});
/* jshint ignore:end */
