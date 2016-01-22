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
 * The core library for Project Grade-Up
 * @package Project Grade-Up
 * @copyright 2015 Dalin Williams
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Dalin Williams <dalinwilliams@gmail.com>
 */

/* jshint ignore:start */
define(['core/log', 'jquery'], function(log, $) {
    log.debug('Within core strings module of PGU');
    var strings = {
        burnupPresentationDiv: '#m_pgu_div_burnup_view_graph',
        burnupPresentationDivId: 'm_pgu_div_burnup_view_graph',
        burnupDisplayButtonId: 'm_pgu_button_show_burnup',
        burnupDisplayButtonDiv: '#m_pgu_button_show_burnup',
        burnupPopupDiv: '#m_pgu_div_burnup_popup',
        burnupPopupDivId: 'm_pgu_div_burnup_popup',
        burnupPopupInput: '#m_pgu_input_field_text',
        burnupPopupButtonSubmit: '#m_pgu_button_submit',
        burnupPopupButtonDelete: '#m_pgu_button_delete',
        burnupPopupButtonCancel: '#m_pgu_button_cancel',
        contextMenuDefultDivId: 'context-menu-one box menu-1',
        artifactColorOnHover: '#FF3300',
        colorBlindFillL: '#333333',
        opacityFull: 1.0,
        opacityNone: 0.0,
        opacityHalf: 0.5,
        hoverAnimationWait: 50,
        hoverAnimationDelay: 0,
        animationWait: 0,
        burnupPresentationDivLoad: '#m_pgu_div_burnup_load',
        rectangleColor: '#8f8f8f',
        strokeNone: 'none',
        strokeInBBox: 'fff',
        artifactColorInBBox: 'fff',
        hoverMessageGrade: ' \n ',
        hiddenUserId: '#m_pgu_hidden_userid',
        hiddenCourseId: '#m_pgu_hidden_courseid',
        hiddenIsTeacher: '#m_pgu_hidden_is_teacher',
        heatmapPresentationDiv: '#m_pgu_div_heatmap_view_graph',
        heatmapPresentationDivId: 'm_pgu_div_heatmap_view_graph',
        heatmapDisplayButtonId: 'm_pgu_button_show_heatmap',
        heatmapDisplayButtonDiv: '#m_pgu_button_show_heatmap',
        heatmapAllPresentationDiv: '#m_pgu_div_heatmap_all_view_graph',
        heatmapAllPresentationDivId: 'm_pgu_div_heatmap_all_view_graph',
        presentationLoadingDiv: '#svgLoading',
        presentationLoadingDivId: 'svgLoading',
        invalidParamsDiv: '#svgError',
        invalidParamsDivId: 'svgError',
        invalidNoECall: 'There is no previous data to pull from!',
        popupIdHolder: 0,
        artifactCollection: 'empty',
        colorblindCheckBox: 'm_pgu_input_colorblind',
        colorblindCheckBoxId: '#m_pgu_input_colorblind',
        chartTypeCheckBox: "m_pgu_input_charttype",
        chartTypeCheckBoxId: "#m_pgu_input_charttype",
        semesterCheckBox: "m_pgu_input_semester",
        semesterCheckBoxId: "#m_pgu_input_semester",
        currentInteractionGlifText: 'You are here!',
        loadingDivContents: "<div id='" + this.presentationLoadingDivId +
            "'><h1>loading. . .</h1></div>",
        invalidParams: "<div id='" + this.invalidParamsDivId +
            "'><h1>Invalid Paramater Found</h1></div>",
        invalidParamsFunc: function(error) {
            return "<div id='" + this.invalidParamsDivId +
                "'><h1>" + error + "</h1></div>";
        },
        colorblindCheckBoxId: '#m_pgu_input_colorblind',
        versionDivId: 'm_pgu_hidden_version',
        versionDiv: '#m_pgu_hidden_version',
        teacherSelectDiv: '#m_pgu_student_select',
        checkVersion: function(mvn) {
            if (typeof mvn === 'undefined') {
                mvn = 1;
            }
            //get the version number
            var version = $(this.versionDiv)[0].value;
            //for now, force to verification mode
            if (typeof nvm === 'string') {
                switch (mvn) {
                    case 'verify':
                        if (/\d{1,}\.\d{1,2}\.\d{1,2}$/.test(
                                version)) {
                            return true;
                        } else {
                            return false;
                        }
                    default:
                        throw 'Invalid opperation in core.js in projectgradeup::checkVersion';
                }
            }
            //string to check if above a version number
            var rString = new RegExp("[" + mvn +
                "-9]+\\.\\d{1,2}\\.\\d{1,2}$");
            return rString.test(version);
        },
        clone: function(obj) {
            var copy;

            // Handle the 3 simple types, and null or undefined
            if (null == obj || "object" != typeof obj) return
            obj;

            // Handle Date
            if (obj instanceof Date) {
                copy = new Date();
                copy.setTime(obj.getTime());
                return copy;
            }

            // Handle Array
            if (obj instanceof Array) {
                copy = [];
                for (var i = 0, len = obj.length; i < len; i++) {
                    copy[i] = this.clone(obj[i]);
                }
                return copy;
            }

            // Handle Object
            if (obj instanceof Object) {
                copy = {};
                for (var attr in obj) {
                    if (obj.hasOwnProperty(attr)) copy[attr] =
                        this.clone(obj[attr]);
                }
                return copy;
            }
            throw new Error(
                "Unable to copy obj! Its type isn't supported."
            );
        }
    };

    var initilize = function(langugeStrings) {
        log.debug('Initilized core strings for PGU');
        strings.hoverMessageGrade = '\n' + langugeStrings[0];
        strings.invalidNoECall = langugeStrings[1];
        //langugeStrings
        return strings;
    };



    return {
        strings: strings,
        init: initilize
    };
});
/* jshint ignore:end */
