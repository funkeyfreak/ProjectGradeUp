//block_projectgradeup/artifact
/* jshint ignore:start */
define(['jquery', 'core/log', 'block_projectgradeup/ajax',
        'block_projectgradeup/core'
    ],
    function($, log, ajax, parent) {
        //initilize parent to the strings object
        parent = parent.strings;
        /**********************************************************************
         *
         * artifactVer - Start
         *
         *********************************************************************/
        //A helper function for verifying incoming Objects
        var artifactVer = function() {
            this.grade = 0;
            this.weight = 0;
            this.category = 0;
            this.url = 0;
            this.status = 0;
            this.title = 0;
            this.type = 0;
        };

        artifactVer.prototype = Object.create(artifactVer.prototype);
        artifactVer.prototype.constructor = artifactVer;
        /**********************************************************************
         *
         * artifactVer - End
         *
         *********************************************************************/

        /**********************************************************************
         *
         * artifactCollection - Start
         *
         *********************************************************************/
        var artifactCollection = function(incomingArtifacts,
            inportedArtifacts, refArtifactCollection) {
            log.debug('Creating New Artifact Collection');
            this.collection = 'unset';
            this.error = 'no error';
            this.collectionList = 'empty';

            //if inportedArtifacts in not set, use the new one
            if (typeof incomingArtifacts !== 'undefined' ||
                inportedArtifacts.constructor !== Array) {
                log.debug('Creating from scratch....');
                try {
                    this.collectionList = incomingArtifacts;
                    this.collection = JSON.parse(JSON.parse(
                        incomingArtifacts));
                } catch (e) {
                    throw 'ERROR #' + errorEnum.PARAM_ERROR +
                        ' in artifact.js::artifactCollection constructor ' +
                        e;
                } finally {
                    log.debug(
                        'Loaded and initilized new Artifact Colllection'
                    );
                }
                log.debug('Completed');
            } else if (inportedArtifacts.constructor === Array &&
                refArtifactCollection instanceof artifactCollection) {
                log.debug(
                    'Creating from existing refArtifactCollection and inportedArtifacts...'
                );
                for (var i = 0; i < inportedArtifacts.length; i++) {
                    log.debug('...');
                    //verity the artufact
                    var valid = this.artifactSanatizer(
                        inportedArtifacts[i]);
                    //if it was not properly validated
                    if (valid === false) {
                        //verify the index and delete
                        //var index = inportedArtifacts.indexOf(i);
                        //if (index > -1) {
                        refArtifactCollection.collection.splice(i, 1);
                        //}
                    }
                    /* else {
                                            //otherwise save it
                                            inportedArtifacts[i] = valid;
                                        }*/

                }
                refArtifactCollection.collection = inportedArtifacts;
                this.collection = refArtifactCollection.collection;
                this.collectionList = refArtifactCollection.collectionList;
                log.debug('Completed');
            } else if (inportedArtifacts.constructor === Array) {
                log.debug('Creating from existing inportedArtifacts...');
                for (var i = 0; i < inportedArtifacts.length; i++) {
                    log.debug('...');
                    //verity the artufact
                    var valid = this.artifactSanatizer(
                        inportedArtifacts[i]);
                    //if it was not properly validated
                    if (valid === false) {
                        //verify the index and delete
                        //var index = inportedArtifacts[i].indexOf(i);
                        //if (index > -1) {
                        refArtifactCollection.collection.splice(i, 1);
                        //}
                    }
                    /* else {
                                            //otherwise save it
                                            inportedArtifacts[i] = valid;
                                        }*/
                }
                this.collection = inportedArtifacts;
                log.debug('Completed');
            } else {
                throw 'ERROR #' + errorEnum.PARAM_ERROR +
                    ' \n Invalid parameters passed in the class artifact mehtod constructor';
            }
            /*for (var j = 0; j < inportedArtifacts.length; j++) {
                this['artifact' + j] = inportedArtifacts[j];
            }*/
        };

        artifactCollection.prototype = Object.create(artifactCollection.prototype);
        artifactCollection.prototype.constructor = artifactCollection;

        //artifact class number 1
        /************************
         * Hiarchy:
         *      mainModule  = 0
         *      ajax        = 1
         *      artifacts   = 2
         *      burnup      = 3
         *      heatmap     = 4
         *      displayAPI  = 5
         */
        var errorEnum = {
            USER_ERROR: 20,
            CODE_ERROR: 21,
            PARAM_ERROR: 22,
            OTHER_ERROR: 23
        };

        artifactCollection.prototype = {
                indexSanatizer: function(index) {
                    //if index is undefined
                    if (typeof index === 'undefined') {
                        index = false;
                    }
                    //if index is not an integer
                    if (Number(index) === index && index % 1 === 0) {
                        try {
                            //cast n to integer
                            index = parseInt(index, 10);
                        } catch (e) {
                            //print the error to the console
                            log.debug(e);
                            log.debug('\n ERROR CODE #' + errorEnum.USER_ERROR);
                            log.debug(
                                'In artifactCollection method indexSanatizer'
                            );
                        }
                    }
                    //if nindexis larger than the length of our collection
                    if (index > this.collection.length) {
                        index = false;
                    }
                    return index;
                },
                verifyArtifactProperties: function(artifact) {
                    //create a new object for viewing the stuffs
                    var verificationObj = new artifactVer();
                    //loop through incomming artifacts and populate the object
                    for (var prop in artifact) {
                        switch (prop) {
                            case 'title':
                                verificationObj[prop] += 1;
                                break;
                            case 'grade':
                                verificationObj[prop] += 1;
                                break;
                            case 'weight':
                                verificationObj[prop] += 1;
                                break;
                            case 'category':
                                verificationObj[prop] += 1;
                                break;
                            case 'status':
                                verificationObj[prop] += 1;
                                break;
                            case 'type':
                                verificationObj[prop] += 1;
                                break;
                            default:
                                return false;
                        }
                        //if there are duplicate properties
                        if (verificationObj[prop] > 1) {
                            return false;
                        }
                    }
                    //if complete, is artifact, return artifact
                    return artifact;
                },
                artifactSanatizer: function(artifact) {
                    var results = false;
                    //check and make sure object is of object
                    if (typeof artifact === 'undefined') {
                        return results;
                    }

                    if (artifact.constructor === Array) {
                        var obj = null;
                        try {
                            obj = artifact.reduce(function(o, v, i) {
                                o[i] = v;
                                return o;
                            }, {});
                        } catch (e) {
                            log.debug(e);
                            log.debug('\n ERROR CODE #' + errorEnum.USER_ERROR);
                            log.debug(
                                'In artifactCollection method artifactSanatizer'
                            );
                        }
                        //make sure we have an artifact
                        obj = this.verifyArtifactProperties(obj);

                        /*if (obj === false) {
                            return false;
                        }*/
                        return obj;
                    }

                    if (artifact.constructor !== Array) {
                        if (artifact instanceof Object) {
                            return artifact;
                        } else {
                            return false;
                        }
                    }
                },
                getArtifactN: function(n) {
                    var results = false;
                    //sanatize incoming params
                    n = this.indexSanatizer(n);
                    //check the result of sanatization
                    if (n !== false) {
                        results = this.collection[n];
                    } else {
                        //let the user know hwat happened
                        throw 'ERROR #' + errorEnum.PRARM_ERROR +
                            'Invalid index in artifactCollection method getArtifactN';
                    }
                    //otherwise return the element at position n
                    return results;
                },
                deleteArtifact: function(n) {
                    var results = false;
                    //sanatize incoming params
                    n = this.indexSanatizer(n);
                    //if the result of sanatization is not false
                    if (n !== false) {
                        results = n;
                        //delete element n in this.collection
                        if (n !== -1) {
                            results = this.collection.splice(n, 1);
                        } else {
                            return results;
                        }
                    }
                    //return the index updated
                    return results;
                },
                updateArtifact: function(n, updatedArtifact) {
                    var results = false;
                    //sanatize incoming params
                    n = this.indexSanatizer(n);
                    updatedArtifact = this.artifactSanatizer(
                        updatedArtifact);
                    //check the result of sanatization
                    if (n !== false && updatedArtifact !== false) {
                        this.collection[n] = updatedArtifact;
                        results = true;
                    } else {
                        //tell the user what went wrong
                        var toThrow = (n === false) ? ((updatedArtifact ===
                                    false) ?
                                'The index and artifact are incorrect' :
                                'The index is incorrect') :
                            'The artifact format is incorrect';
                        throw 'ERROR CODE #' + errorEnum.PARAM_ERROR +
                            toThrow;
                    }
                    return results;
                }
            }
            /**********************************************************************
             *
             * artifactCollection - End
             *
             *********************************************************************/

        var initilize = function() {
            return artifactCollection;
        };

        return artifactCollection;

    });

/* jshint ignore:end */
