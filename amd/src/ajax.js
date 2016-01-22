//block_projectgradeup/ajax
/* jshint ignore:start */
define(['jquery', 'core/ajax', 'core/log', 'block_projectgradeup/core'],
    function($, ajax, log, parent) {
        //initilize parent to the strings object
        parent = parent.strings;
        /**
         * The class for ajax calls
         * @param  {integer} userId   The id of the user, obtained from the hidden fields
         * @param  {integer} courseId The id of the course, obtained from the hidden field
         * @return {void}          Nothing at all
         */
        var ajaxInstance = function(userId, courseId) {
            userId = (typeof userId === 'undefined') ? $(parent.hiddenUserId)[
                0].value : userId;
            courseId = (typeof courseId === 'undefined') ? $(parent.hiddenCourseId)[
                0].value : courseId;
            this.userId = userId;
            this.courseId = courseId;
        };
        //ajaxInstance.prototype.constructor = ajaxInstance;
        var errorEnum = {
            USER_ERROR: 10,
            CODE_ERROR: 11,
            PARAM_ERROR: 12,
            OTHER_ERROR: 13
        };

        ajaxInstance.prototype = {
            typeCheckFunctions: function(func, err) {
                if (typeof func !== 'function') {
                    throw err;
                }
                return true;
            },
            typeCheckRequestType: function(requestType) {
                if (typeof requestType === 'undefined') {
                    return 'single';
                }
                switch (requestType) {
                    case 'single':
                    case 'course':
                    case 'all':
                        return requestType;
                        break;
                    default:
                        return 'single';

                }
            },

            typeCheckFormat: function(format) {
                //for now, we pretty much hardset format but we
                //change format if and only if it is not a pre-set
                //value and the version number is over version 2
                switch (format) {
                    case 'string':
                        break;
                    case 'json':
                        //if we are not in atleast version 2
                        if (!parent.checkVersion(2)) {
                            format = 'string';
                        }
                        break;
                    default:
                        format = 'string';
                }
                return format;
            },
            typeCheckResolution: function(res, graphType) {
                if(typeof graphType === 'undefined'){
                    graphType = 'none'
                }
                if (typeof res === 'undefined') {
                    //check for truthy-ness of js
                    res = {};
                    if(graphType == 'heatmap'){
                        res.width = (document.getElementById(
                                    parent.heatmapPresentationDivId
                                )
                                .offsetWidth == 0) ? 1080 :
                            document.getElementById(
                                parent.heatmapPresentationDivId
                            ).offsetWidth;
                        res.height = 0.25 * res.width;
                    }
                    else if(graphType == 'burnup'){
                        res.width = (document.getElementById(parent.burnupPresentationDivId)
                            .offsetWidth == 0) ? 1080 : ((document.getElementById(
                            parent.burnupPresentationDivId).offsetWidth) * .9);
                        res.height = (((2 / 3) * res.width) * .9);
                    }
                    else{
                        res.height = ($(parent.heatmapPresentationDiv)
                                .innerWidth() == 0) ? 720 : $(parent.heatmapPresentationDiv)
                            .innerWidth();
                        res.width = ($(parent.heatmapPresentationDiv)
                                .innerHeight() == 0) ? 1080 : $(parent.heatmapPresentationDiv)
                            .innerHeight();
                    }
                    /*res.width = 1080;
                    res.height = 200;*/
                } else if (!('width' in res) || !('height' in res)) {
                    res = {};
                    if(graphType == 'heatmap'){
                        res.width = (document.getElementById(
                                    parent.heatmapPresentationDivId
                                )
                                .offsetWidth == 0) ? 1080 :
                            document.getElementById(
                                parent.heatmapPresentationDivId
                            ).offsetWidth;
                        res.height = 0.25 * res.width;
                    }
                    else if(graphType == 'burnup'){
                        res.width = (document.getElementById(parent.burnupPresentationDivId)
                            .offsetWidth == 0) ? 1080 : (document.getElementById(
                            parent.burnupPresentationDivId).offsetWidth * .9);
                        res.height = (((2 / 3) * res.width) * .9);
                    }
                    else{
                        res.height = ($(parent.heatmapPresentationDiv)
                                .innerWidth() == 0) ? 720 : $(parent.heatmapPresentationDiv)
                            .innerWidth();
                        res.width = ($(parent.heatmapPresentationDivId)
                                .innerHeight() == 0) ? 1080 : $(parent.heatmapPresentationDiv)
                            .innerHeight();
                    }
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
                        if(graphType == 'heatmap'){
                            res.width = (document.getElementById(
                                    parent
                                    .heatmapPresentationDivId
                                ).offsetWidth ==
                                0) ? 1080 : document.getElementById(
                                parent.heatmapPresentationDivId
                            ).offsetWidth;
                            res.height = 0.25 * res.width;
                        }
                        else if(graphType == 'burnup'){
                            res.width = (document.getElementById(parent
                                    .burnupPresentationDivId).offsetWidth ==
                                0) ? 1080 : (document.getElementById(
                                parent.burnupPresentationDivId).offsetWidth * .9);
                            res.height = (((2 / 3) * res.width) * .9);
                        }
                        else{
                            res.width = $(parent.heatmapPresentationDiv).innerHeight();
                            res.height = $(parent.heatmapPresentationDiv).innerHeight();
                        }
                    }
                }
                return res;
            },
            typeCheckColorBlind: function(cb) {
                if (typeof cb === 'undefined') {
                    cb = $(parent.colorblindCheckBoxId).is(
                        ':checked');
                } else if (typeof colorblind !== 'boolean') {
                    cb = $(parent.colorblindCheckBoxId).is(
                        ':checked');
                }
                /*else {
                    cb = false;
                }*/
                //return 1 or 0 debendent on booleanvalue of cb
                return (cb) ? 1 : 0;
            },
            typeCheckRequestName: function(requestName) {
                if (typeof requestName === 'undefined') {
                    throw "Undefined request sent | projectgradeup->ajax.js::typeCheckRequestName. Error #" +
                    errorEnum.USER_ERROR;
                }
                switch (requestName) {
                    case 'burnup':
                    case 'heatmap':
                    case 'artifact':
                        return requestName;
                        break;
                    default:
                        throw "Unlknown request sent | projectgradeup->ajax.js::typeCheckRequestName. Error #" +
                        errorEnum.USER_ERROR;
                }
                /*if ((rn != 'single') && (rn != 'all') &&
                    (rn != 'course')) {
                    //default to the single request
                    rn = 'single';
                }*/
                return rn;
            },
            createHeatmapRequest: function(requestType,
                success, fail, resolution, colorBlind) {
                //clean the requestType
                requestType = this.typeCheckRequestType(requestType);
                //clean the callbacks success and fail
                this.typeCheckFunctions(success,
                    'ERROR CODE #' + errorEnum.CODE_ERROR +
                    'not a valid fucntion for the success callback'
                );
                this.typeCheckFunctions(fail,
                    'ERROR CODE #' + errorEnum.CODE_ERROR +
                    'not a valid fucntion for the fail callback'
                );

                //clean colorblind
                colorBlind = this.typeCheckColorBlind(colorBlind);

                //clean the resolution
                resolution = this.typeCheckResolution(resolution, 'heatmap');

                //throw an error if the user attempts to use before version is complete
                //OR redirect to legacy handeler
                if (!parent.checkVersion(2)) {
                    var requestName = this.typeCheckRequestName(
                        'heatmap');
                    var format = this.typeCheckFormat();
                    this.defaultHandler(resolution, requestName,
                        requestType, colorBlind, format,
                        success, fail);
                    return true;
                    //throw 'This function is not valid on the version of
                    //projectgradeup | projectgradeup->ajax.js::createHeatmapRequest';
                }

                //send the request
                var promise = ajax.call({
                    methodname: 'block_projectgradeup/get_heatmap',
                    args: {
                        courseid: this.courseId,
                        userid: this.userId,
                        resolution: resolution,
                        request: requestType,
                        colorblind: colorBlind
                    }
                });
                promise.done(success).fail(fail);
            },
            createBurnupRequest: function(requestType, success, fail,
                resolution,
                colorBlind) {
                //clean the requestType
                requestType = this.typeCheckRequestType(requestType);
                //clean the callbacks success and fail
                this.typeCheckFunctions(success,
                    'ERROR CODE #' + errorEnum.CODE_ERROR +
                    'not a valid fucntion for the success callback'
                );
                this.typeCheckFunctions(fail,
                    'ERROR CODE #' + errorEnum.CODE_ERROR +
                    'not a valid fucntion for the fail callback'
                );

                //clean colorblind
                colorBlind = this.typeCheckColorBlind(colorBlind);

                //clean the resolution
                resolution = this.typeCheckResolution(resolution, 'burnup');

                if (!parent.checkVersion(2)) {
                    var requestName = this.typeCheckRequestName(
                        'burnup');
                    var format = this.typeCheckFormat();
                    this.defaultHandler(resolution, requestName,
                        requestType, colorBlind, format,
                        success, fail);
                    return true;
                    //throw 'This function is not valid on the version of projectgradeup
                    //| projectgradeup->ajax.js::createArtifactRequest';
                }
                //send the request
                var promise = ajax.call({
                    methodname: 'block_projectgradeup/get_burnup',
                    args: {
                        courseid: this.courseId,
                        userid: this.userId,
                        resolution: resolution,
                        request: requestType,
                        colorblind: colorBlind
                    }
                });
                promise.done(success).fail(fail);
            },
            createArtifactRequest: function(requestType, success, fail,
                format) {
                //clean the requestType
                requestType = this.typeCheckRequestType(requestType);
                //clean the callbacks success and fail
                this.typeCheckFunctions(success,
                    'ERROR CODE #' + errorEnum.CODE_ERROR +
                    'not a valid fucntion for the success callback'
                );
                this.typeCheckFunctions(fail,
                    'ERROR CODE #' + errorEnum.CODE_ERROR +
                    'not a valid fucntion for the fail callback'
                );
                format = this.typeCheckFormat(format);

                //throw an error if the user attempts to use before version is complete
                //OR redirect to legacy handeler
                if (!parent.checkVersion(2)) {
                    var requestName = this.typeCheckRequestName(
                        'artifact');
                    var resolution = this.typeCheckResolution();
                    this.defaultHandler(resolution, requestName,
                        requestType, 0, format, success, fail);
                    return true;
                    //throw 'This function is not valid on the version of projectgradeup
                    //| projectgradeup->ajax.js::createArtifactRequest';
                }
                //send the request
                var promise = ajax.call([{
                    methodname: 'block_projectgradeup/get_artifacts',
                    args: {
                        courseid: this.courseId,
                        userid: this.userId,
                        format: format,
                        request: requestType
                    }
                }]);
                promise[0].done(success).fail(fail);
            },
            defaultHandler: function(resolution, requestName,
                requestType, assistance, format, success, fail) {
                Y.io(M.cfg.wwwroot +
                    '/blocks/projectgradeup/ajax.php?sesskey=' +
                    M.cfg.sesskey + '&width=' + resolution.width +
                    '&height=' + resolution.height +
                    '&function=' + requestName + '&request=' +
                    requestType + '&course=' + this.courseId +
                    '&name=' + this.userId + '&assistance=' +
                    assistance + '&format=' + format, {
                        on: {
                            //we have to wrap the functions since they are not made to work with legacy
                            success: function(x, o) {
                                log.debug('Success');
                                success(o.responseText);
                            },
                            failure: function(x, o) {
                                log.debug('Failure');
                                fail(o.responseText);
                            }
                        }
                    });
            }
        }
        ajaxInstance.prototype = Object.create(ajaxInstance.prototype);
        ajaxInstance.prototype.constructor = ajaxInstance;
        var initilize = function() {
            return ajaxInstance;
        };
        return ajaxInstance;
        /*{
                    init: initilize
                };*/
    });
/* jshint ignore:end */
