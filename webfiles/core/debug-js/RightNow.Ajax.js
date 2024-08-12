if(RightNow.Ajax) throw new Error("The RightNow.Ajax namespace variable has already been defined somewhere.");
YUI().use('io-base', 'io-form', 'io-queue', 'io-upload-iframe', function(Y) {
/**
 * Contains any functions which relate to making or handling Ajax requests.
 * @namespace
 */
RightNow.Ajax = (function(){
    var _additionalRequestData = {},
        _permanentAdditionalRequestData = {},
        _isNavigatingAwayFromPage = false;

    /**
     * Get the RNT_REFERRER value from a url paramater, which the URL up to, but not
     * including, the first "#" character.
     * @param {string} url The URL for which to retreive the RNT_REFERRER
     * @return {string} The URL's RNT_REFERRER value
     * @private
     */
    function _getRntReferrerHeaderFromUrl(url) {
        var hashPosition = url.indexOf('#');
        if(hashPosition > -1) {
            url = url.substr(0, hashPosition);
        }
        return url;
    }

    // If there's a base tag on the page then the HTTP_REFERER for IE AJAX is the href of that base tag.
    // So we'll set our own permanent header (correctly spelled) with the real referrer.
    Y.io.header('RNT_REFERRER', _getRntReferrerHeaderFromUrl(window.location.href));

    return { /** @lends RightNow.Ajax */
        getRntReferrerHeaderFromUrl: _getRntReferrerHeaderFromUrl,

        /**
         * @constant
         * @description A URL of this length is supported by all browsers. IE is the limiting factor here.
         * @link http://support.microsoft.com/kb/208427
         */
        MAX_URL_LENGTH: 2048,

        /**
         * Adds a key-value pair to the next request made. This data will not be
         * passed like form data, but will appear as a top level key in the post
         * data. These values will be added to the post data before on_before_ajax_request.
         * @param {string} key The name of the data to add
         * @param {string} value The value to add
         * @param {boolean=} permanent Denotes if parameter should be sent with every Ajax request made and not cleared out after each request
         */
        addRequestData: function(key, value, permanent) {
            permanent = permanent || false;
            if(permanent)
                _permanentAdditionalRequestData[key] = value;
            else
                _additionalRequestData[key] = value;
        },

        /**
         * Takes an object and converts it to a POST data string. Functions or sub
         * objects within the object will not be added to the string.
         * @param {Object} postData Object The object to convert to POST data
         * @return {string} The object data converted into POST format
         */
        convertObjectToPostString: function(postData)
        {
            var postString = "";
            for(var post in postData)
            {
                if(!postData.hasOwnProperty(post) || typeof postData[post] === "function" || typeof postData[post] === "object" || postData[post] === undefined)
                    continue;
                postString += encodeURIComponent(post) + "=" + encodeURIComponent(postData[post]) + "&";
            }

            // Trim trailing '&'
            if(postString.length > 0)
                postString = postString.substring(0, postString.length - 1);

            return postString;
        },

        /**
         * Takes an object and converts it to a GET data string. Functions, sub-
         * objects, undefined, null, or empty string values within the object
         * will not be added to the string.
         * @param {Object} requestData Object The object to convert to GET data
         * @return {string} The object data converted into GET format
         */
        convertObjectToGetString: function(requestData)
        {
            var getString = "",
                key, value;
            for(key in requestData)
            {
                value = requestData[key];
                if(requestData.hasOwnProperty(key) && typeof value !== "function" && typeof value !== "object" && typeof value !== "undefined" && value !== null && value !== "")
                {
                    getString += "/" + encodeURIComponent(key) + "/" + encodeURIComponent(value);
                }
            }
            return getString;
        },

        /**
         * Makes an asynchronous request
         *
         * @param {string} url The URL to make the request
         * @param {Object} postData The data to send with the request; keys & values are converted to POST/GET parameters
         * @param {Object=} requestOptions Configuration options for the request
         * @example
         * requestOptions object may inclue the following:
         *      successHandler: (function) callback function for the successful request,
         *      scope: (object) scope to apply to successHandler (defaults to window if none supplied),
         *      data: (object) {eventName: (string) name of the RightNow event to fire upon the response,
         *                       data: (object) data to send along w/ the event,
         *                       ignoreFailure: (boolean) whether to ignore a failed request or to
         *                                      display an error dialog (defaults to false)},
         *      failureHandler: (function) callback function for a failed request,
         *      upload: (string) ID of the form that contains files to upload,
         *      timeout: (int) time to wait (in milliseconds) for the request to complete (defaults to 20000),
         *      type: (string) "POST", "GET", "GETPOST" (attempt to use GET but fallback to POST if a
         *                      valid GET request cannot be made) (defaults to "POST"),
         *      json: (boolean) Whether or not to automatically JSON parse the response before calling
         *                      successHandler (defaults to false),
         *      isResponseObject: (boolean) Denotes if the request will return a JSON-encoded ResponseObject.
         *                          If true and the result has an error, a generic error message is displayed.
         *                          If the result is valid, only the actual result is returned.
         *                          The 'json' option must be enabled for this option to work (defaults to false)
         *      headers: (object=) Optional object literal containing specific HTTP headers and values to send in the request (e.g. { 'Content-Type': 'application/xml;charset=utf-8' })
         *      cors: (boolean) Denotes if the request is for a Cross-Origin resource.
         * @return {Object} The instance of the YUI.io request object
         */
        makeRequest: function(url, postData, requestOptions)
        {
            if(typeof requestOptions === "undefined" || !requestOptions)
                requestOptions = {};

            if(requestOptions.cors === true && !requestOptions.xdrLoaded && Y.UA.ie && Y.UA.ie < 10) {
                //io-xdr module is required for cross domain requests in IE9
                var requestObject = {};
                Y.use('io-xdr', function(Y) {
                    requestOptions.xdrLoaded = true;
                    requestObject = RightNow.Ajax.makeRequest(url, postData, requestOptions);
                });

                //NOTE: the first time this returns, it will return an empty object because YUI.use executes asynchronously
                //From the 2nd request onwards, this will return the actual Y.io request because if the io-xdr module is loaded already
                //YUI.use executes synchronously
                return requestObject;
            }

            requestOptions.type = requestOptions.type || "POST";
            requestOptions.url = url;
            requestOptions.post = Y.merge(postData || {}, _additionalRequestData, _permanentAdditionalRequestData);
            _additionalRequestData = {};

            RightNow.Event.fire("on_before_ajax_request", requestOptions);

            requestOptions.data = RightNow.Lang.cloneObject(requestOptions.data);

            if (requestOptions.type === "GETPOST" && requestOptions.url !== url) {
                // If the above hook changed the default URL, there's no guarantee that the
                // user-function supports GET, so fall back to POST.
                requestOptions.type = "POST";
            }
            var callback = {
                    failureHandler: requestOptions.failureHandler || this.genericFailure
                },
                context,
                argument;

            if(requestOptions.upload)
                requestOptions.type = "POST";

            if(requestOptions.scope)
                context = requestOptions.scope;

            if(requestOptions.data !== null && typeof requestOptions.data !== "undefined")
                argument = requestOptions.data;

            if(!requestOptions.challengeHandler)
                requestOptions.challengeHandler = new RightNow.UI.AbuseDetection.Default().getChallengeHandler();

            if(!requestOptions.successHandler)
                requestOptions.successHandler = this.genericSuccess;

            var userHandler = requestOptions.successHandler,
                jsonWrapper = function(response, argument, transactionID) {
                    var parsedResponse,
                        defaultAjaxErrorMessage = RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG"),
                        callFailureHandler = function(ajaxError){
                            callback.failureHandler.call(context || window, {"ajaxError": ajaxError}, argument, transactionID);
                        };
                    try {
                        parsedResponse = RightNow.JSON.parse(response.responseText);
                        if(requestOptions.isResponseObject === true || parsedResponse.isResponseObject === true) {
                            if(parsedResponse.errors && parsedResponse.errors.length){
                                var errorMessage = (parsedResponse.errors[0].displayToUser) ? parsedResponse.errors[0].externalMessage : defaultAjaxErrorMessage;
                                callFailureHandler(errorMessage);
                            }
                            parsedResponse = parsedResponse.result;
                            if(!parsedResponse) {
                                return;
                            }
                        }
                        // Flag response with a unique attribute to indicate response has already been parsed as JSON
                        parsedResponse._isParsed = true;
                    }
                    catch(e) {
                        callFailureHandler(defaultAjaxErrorMessage);
                    }
                    userHandler.call(context || window, parsedResponse, argument, transactionID);
                };

            // When json, don't doubly assign this function in the AbuseDetection handler case
            if(requestOptions.json === true && ("" + userHandler !== "" + jsonWrapper))
                requestOptions.successHandler = jsonWrapper;

            //Check to see if ajaxRequest is for CCL and if so do not append session param
            if(RightNow.Url.getSession() !== "") {
                if(!(/getChatQueueAndInformation/.test(url))) {
                    requestOptions.url = RightNow.Url.addParameter(requestOptions.url, 'session', RightNow.Url.getSession());
                }
                else if((/getChatQueueAndInformation/.test(url)) && requestOptions.data.data.cookies_enabled !== true) {
                    requestOptions.url = RightNow.Url.addParameter(requestOptions.url, 'session', RightNow.Url.getSession());
                }
            }

            /**@ignore*/
            callback.success = function(transactionID, responseObject, args) {
                if(RightNow.UI.AbuseDetection.doesResponseIndicateAbuse(responseObject)) {
                    try {
                        requestOptions.challengeHandler(RightNow.JSON.parse(RightNow.Text.getSubstringAfter(responseObject.responseText, "\n")), requestOptions, RightNow.UI.AbuseDetection.isRetry());
                    }
                    catch(e) {
                        RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG"), {"icon": "WARN"});
                    }
                }
                else {
                    RightNow.Ajax.checkForSocialExceptions(responseObject);
                    // if the response indicates json, update success handler
                    if(responseObject.getResponseHeader && responseObject.getResponseHeader('content-type') === 'application/json' && ("" + userHandler !== "" + jsonWrapper)) {
                        requestOptions.successHandler = jsonWrapper;
                    }
                    requestOptions.successHandler.call(this, responseObject, args, transactionID);
                }
            };

            /**@ignore*/
            callback.failure = function(transactionID, responseObject, args){
                // 161031-000163 - log to ACS on AJAX timeout
                if (responseObject.status === 0 && responseObject.statusText === 'timeout') {
                    var data = {
                        'url': url,
                        'method': requestOptions.type,
                        'payload': postData
                    };
                    RightNow.ActionCapture.record('cpAjax', 'timeout', data);

                    // provide a timeout-specific error message
                    responseObject.suggestedErrorMessage = RightNow.Interface.getMessage('REQUEST_COL_PCT_ED_HEAVY_SYS_L_RR_AGAIN_MSG');
                }
                if (!_isNavigatingAwayFromPage)
                    callback.failureHandler.call(this, responseObject, args);
            };

            var cfg = {
                on: callback,
                timeout: requestOptions.timeout || 20000, //Default to 20 second timeout. If you've waited around this long, I salute you.
                arguments: argument,
                context: context
            };

            if(requestOptions.cors === true) {
                cfg.xdr = {
                    credentials: true,
                    use: 'native'
                };
            }

            if (requestOptions.type === "GETPOST") {
                var getString = ((requestOptions.url.charAt(requestOptions.url.length - 1) === '/')
                        ? requestOptions.url.substring(0, requestOptions.url.length - 1)
                        : requestOptions.url) +
                    this.convertObjectToGetString(requestOptions.post);
                if (getString.length < this.MAX_URL_LENGTH) {
                    cfg.method = "GET";
                    return Y.io(getString, cfg);
                }
            }

            cfg.data = this.convertObjectToPostString(requestOptions.post) || undefined;
            cfg.method = (requestOptions.type === "GETPOST" ? "POST" : requestOptions.type);

            if (requestOptions.upload) {
                cfg.on.complete = callback.success;
                cfg.form = {
                    id: requestOptions.upload,
                    upload: true
                };
            }

            // make sure x-requested-with is reset on every request
            Y.io.header('X-Requested-With', 'xmlhttprequest');

            if (requestOptions.headers)
                cfg.headers = requestOptions.headers;

            return Y.io(requestOptions.url, cfg);
        },

        /**
         * Generic function to handle the response from an Ajax request. Checks data
         * integrity, decodes data, and fires the event found in the response data argument.
         * @param {Object} responseObject The object received from a successful request
         * @param {Object} argument Any callback data that the caller supplied
         */
        genericSuccess: function(responseObject, argument) {
            if(typeof responseObject === 'object' && ('responseText' in responseObject || '_isParsed' in responseObject)) {
                var parsedData = '';
                if('_isParsed' in responseObject) {
                    // Remove object flag
                    delete responseObject._isParsed;
                    parsedData = responseObject;
                }
                else {
                    try {
                        parsedData = RightNow.JSON.parse(responseObject.responseText);
                    }
                    catch(e) {
                        RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage("THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG"), {'icon': 'WARN'});
                    }
                }

                if(argument && argument.eventName) {
                    RightNow.Event.fire(argument.eventName, parsedData, argument.data);
                }
            }
            else {
                RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG'), {'icon': 'WARN'});
            }
        },

        /**
         * Generic function used to handle failures during an Ajax request
         * @param {Object} o The object received from a failed request
         */
        genericFailure: function(o)
        {
            if (o.ajaxError) {
                RightNow.UI.Dialog.messageDialog(o.ajaxError, {"icon": "WARN"});
            } else if (o.status === 0 || o.statusText === 'timeout') {
                RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage('REQUEST_COL_PCT_ED_HEAVY_SYS_L_RR_AGAIN_MSG'), {"icon": "WARN"});
            } else if (!o.argument || !o.argument.ignoreFailure) {
                RightNow.UI.Dialog.messageDialog(RightNow.Interface.getMessage('THERE_PROB_REQ_ACTION_COULD_COMPLETED_MSG'), {"icon": "WARN"});
            }
        },

        /**
         * Function to allow aborting from an Ajax request
         * @param {Object} request The connection object returned from makeRequest
         * @param {Object} callback Callback object with failure handler to execute after abort
         * @return {boolean} Denotes if abortion was successful
         */
        abortRequest: function(request, callback)
        {
            if (request.abort) {
                if (callback) {
                    request.on("failure", callback);
                }
                request.abort();
                return true;
            }
            return false;
        },

        /**
         * We want to know if the user is trying to navigate away form the page so we can ignore server errors
         * @param {boolean} isNavigating true if the window is navigating away from the current page or to the same page
         */
        setIsNavigatingAwayFromPage: function(isNavigating)
        {
            _isNavigatingAwayFromPage = isNavigating;
        },

        /**
         * Checks responseObjects for errors related to not having a social user or not being logged in.
         *   - When the user is not logged on, evt_requireLogin is fired.
         *   - When the user does not have a social user association, evt_userInfoRequired is fired.
         *   - When the user has a social user association but it is blank, evt_userInfoRequired is fired.
         * @param {Object} responseObject The response object received from a successful request
         */
        checkForSocialExceptions: function(responseObject)
        {
            if(responseObject.responseText !== "null") {
                try {
                    var parsedData = RightNow.JSON.parse(responseObject.responseText);
                    if(parsedData.errors && parsedData.errors.length > 0) {
                        Y.Array.each(parsedData.errors, function(error) {
                            if(error.errorCode === 'ERROR_USER_NOT_LOGGED_IN')
                                RightNow.Event.fire('evt_requireLogin');
                            if(error.errorCode === 'ERROR_USER_HAS_NO_SOCIAL_USER' || error.errorCode === 'ERROR_USER_HAS_BLANK_SOCIAL_USER')
                                RightNow.Event.fire('evt_userInfoRequired');
                        });
                    }
                }
                catch (e) {
                    // pass - malformed json from response
                }
            }
        },

        /**
         * Checks if a responseObject or string indicates a social user error.
         * @param {Object|string} responseObjectOrString An object or string
         * @return {boolean} An object or string
         */
        indicatesSocialUserError: function(responseObjectOrString) {
            var stringsToCheck = [];

            if (typeof responseObjectOrString === 'object') {
                if (responseObjectOrString.responseText && responseObjectOrString.responseText !== 'undefined') {
                   stringsToCheck.push(responseObjectOrString.responseText);
                }
                else if(responseObjectOrString.errors && responseObjectOrString.errors !== 'undefined') {
                    Y.Array.each(responseObjectOrString.errors, function(error) {
                        stringsToCheck.push(error.errorCode);
                    });
                }
            }
            else if (typeof responseObjectOrString === 'string') {
                stringsToCheck.push(responseObjectOrString);
            }

            return Y.Array.some(stringsToCheck, function(errorToCheck) {
                if(/ERROR_USER_NOT_LOGGED_IN|ERROR_USER_HAS_NO_SOCIAL_USER|ERROR_USER_HAS_BLANK_SOCIAL_USER/.test(errorToCheck) ||
                   /User is not logged in|User does not have a display name/.test(errorToCheck))
                    return true;
            });
        }

    };
}());

/**
 * Contains constants and functions for recording user actions within clickstreams.
 * @namespace
 */
RightNow.Ajax.CT = (function()
{
    var entries = [],
        /**@inner*/
        insert = function(callback, scope){
            if(entries && entries.length) {
                //dqa doesn't return anything: callback function or no-op handler fcn that won't try to parse results or throw an error if the request is interrupted
                RightNow.Ajax.makeRequest(RightNow.Ajax.CT.DQA_SERVICE, {data:RightNow.JSON.stringify(entries)}, {successHandler: callback || function(){}, failureHandler: callback || function(){}, "scope": scope});
            }
            entries = [];
        };

    return {

        /**@constant**/CLICKSTREAM: 1,
        /**@constant**/SOLVED_COUNT: 2,
        /**@constant**/LINKS: 3,
        /**@constant**/ANS_STATS: 4,
        /**@constant**/STATS: 5,
        /**@constant**/KEYWORD_SEARCHES: 8,
        /**@constant**/WIDGET_STATS: 9,
        /**@constant**/GA_SESSIONS: 11,
        /**@constant**/GA_SESSION_DETAILS: 12,
        /**@constant**/DQA_SERVICE:  '/ci/dqa/publish',
        /**
         * This function is used to insert an action to DQA. However, it will not commit until commitActions function is called.
         * @param {number} type Action type
         *  CLICKSTREAM(1) Clickstream action
         *  SOLVED_COUNT(2) Solved Count action
         *  LINKS (3) Links action
         *  ANS_STATS(4) inserting into ans_stats table
         *  STATS(5) inserting into ans_stats table
         *  KEYWORD_SEARCHES(8) keyword searches
         *  WIDGET_STATS(9) Widget stats
         * @param {Object} action DQA action in JavaScript object.
         */
        addAction: function(type, action)
        {
            entries.push({"type":type, "action":action});
        },

        /**
         * Sends current action queue to server
         * @param {function()=} callback Callback function to execute when request completes
         * @param {Object=} scope Scope to apply to the callback function
         */
        commitActions: function(callback, scope)
        {
            insert(callback, scope);
        },

         /**
         * This function is used to add submit a click action to DQA. Action will be
         * submitted to the server immediately.
         * @param {number} type Action type
         *  CLICKSTREAM(1) Clickstream action
         *  SOLVED_COUNT(2) Solved Count action
         *  LINKS (3) Links action
         *  ANS_STATS(4) inserting into ans_stats table
         *  STATS(5) inserting into ans_stats table
         *  KEYWORD_SEARCHES(8) keyword searches
         *  WIDGET_STATS(9) Widget Stats
         *  GA_SESSIONS (11) Guided Assistant Sesssions
         *  GA_SESSION_DETAILS (12) Guided Assistant Sesssion Details
         * @param {Object} action DQA action in JSON format.
         * @param {function()=} callback Callback function to execute when request completes
         * @param {Object=} scope Scope to apply to the callback function
         */
        submitAction: function(type, action, callback, scope)
        {
            this.addAction(type, action);
            insert(callback, scope);
        }
    };
}());

(function(){
     // set up a listener for when the user navigates away from the page
     // also check to make sure that the handler wasn't already set for window.onbeforeunload
     // if it was then fire it.
     var beforeUnload = window.onbeforeunload;
     window.onbeforeunload = function(e){if(beforeUnload){beforeUnload(e);} RightNow.Ajax.setIsNavigatingAwayFromPage(true);};
})();
});
