UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    namespaces: ['RightNow.Ajax', 'RightNow.Event'],
    jsFiles: ['/euf/core/debug-js/RightNow.Ajax.js', '/euf/core/debug-js/RightNow.Event.js']
}, function(Y){
    var acsResults = {};

    //Mock classes that makeRequest calls into
    RightNow.UI = RightNow.UI || {
        Dialog: {
            messageDialog: function (){},
            actionDialog: function (){}
        },
        AbuseDetection: {
            Default: function(){return {getChallengeHandler: function(){}};},
            doesResponseIndicateAbuse: function(){return false;}
        }
    };
    RightNow.Url = RightNow.Url || {getSession: function(){return "";}};
    RightNow.JSON = RightNow.JSON || {parse: function(data){return data;}};

    // Mock the ACS class
    RightNow.ActionCapture = {
        record: function(subject, verb, actionObject) {
            acsResults = {
                'subject': subject,
                'verb': verb,
                'actionObject': actionObject
            };
        }
    };

    var rightnowAjaxTests = new Y.Test.Suite({ name: "RightNow.Ajax" });

    rightnowAjaxTests.add(new Y.Test.Case({
        setUp: function() {
            // don't let prior results affect future tests
            acsResults = {};
        },

        name: 'makeRequest',
        request: function(a, b, c) {
            return RightNow.Ajax.makeRequest(a, b, c);
        },

        'Failure is ignored with ignoreFailure': function() {
            // TODO US534: http://codereview.rightnowtech.com/cru/CPDYN-1410#CFR-464652
        },

        'Failure handler is called': function() {
            // TODO US534: http://codereview.rightnowtech.com/cru/CPDYN-1410#CFR-464652
        },

        'Success handler is called with invalid request': function() {
            var test = this;
            function response(resp) {
                test.resume(function() {
                    Y.Assert.isObject(resp);
                    Y.Assert.isNull(resp.result);
                    Y.Assert.areSame('Invalid Answer ID: ', resp.errors[0].externalMessage);
                });
            }
            this.request('/ci/ajaxRequest/getAnswer/objectID/1', null, {
                successHandler: response
            });
            this.wait();
        },

        'Success handler is called with good request': function() {
            var test = this;
            function response(resp) {
                test.resume(function() {
                    Y.Assert.isObject(resp);
                    Y.Assert.isNotNull(resp.result);
                    Y.Assert.isNumber(resp.result.ID);
                });
            }
            this.request('/ci/ajaxRequest/getAnswer', {objectID: 1}, {
                successHandler: response,
                type: 'POST'
            });
            this.wait();
        },

        'Event is fired': function() {

        },

        'isResponseObject option': function() {
            // TODO US534: http://codereview.rightnowtech.com/cru/CPDYN-1410#CFR-464652
        },

        'timeout is honored': function() {
            // TODO US534: http://codereview.rightnowtech.com/cru/CPDYN-1410#CFR-464652
        },

        'GET type': function() {
            // TODO US534: http://codereview.rightnowtech.com/cru/CPDYN-1410#CFR-464652
        },

        'POST type': function() {
            // TODO US534: http://codereview.rightnowtech.com/cru/CPDYN-1410#CFR-464652
        },

        'GETPOST type': function() {
            // TODO US534: http://codereview.rightnowtech.com/cru/CPDYN-1410#CFR-464652
        },

        'JSON is returned to successHandler when desired with invalid request': function() {
            var test = this;
            function response(resp) {
                test.resume(function() {
                    Y.Assert.isObject(resp);
                    Y.Assert.isObject(resp.result);
                    Y.Assert.isTrue(resp._isParsed);
                    Y.Assert.areSame(1, resp.result.ID);
                });
            }
            this.request('/ci/ajaxRequest/getAnswer/objectID/1', null, {
                successHandler: response,
                json: true,
                type: 'GET'
            });
            this.wait();
        },

        'JSON is returned to successHandler when desired with good request': function() {
            var test = this;
            function response(resp) {
                test.resume(function() {
                    Y.Assert.isObject(resp);
                    Y.Assert.isObject(resp.result);
                    Y.Assert.isTrue(resp._isParsed);
                    Y.Assert.areSame(1, resp.result.ID);
                });
            }
            this.request('/ci/ajaxRequest/getAnswer', {objectID: 1}, {
                successHandler: response,
                json: true,
                type: 'POST'
            });
            this.wait();
        },

        'Non-JSON responses do not indicate they have been parsed': function() {
            var test = this;
            function response(resp) {
                test.resume(function() {
                    Y.Assert.isObject(resp);
                    Y.Assert.isTrue(resp._isParsed === undefined);
                });
            }
            this.request('/ci/ajax/widget/standard/discussion/BestAnswerDisplay/refresh', null, {
                successHandler: response,
                json: false,
                type: 'GET'
            });
            this.wait();
        },

        'RNT_REFERRER header is set to everything up to, but not including, the hash character': function() {
            Y.Assert.areEqual("http://batmanandrobin.com/episode/1",   RightNow.Ajax.getRntReferrerHeaderFromUrl("http://batmanandrobin.com/episode/1"));
            Y.Assert.areEqual("http://batmanandrobin.com/episode/1",   RightNow.Ajax.getRntReferrerHeaderFromUrl("http://batmanandrobin.com/episode/1#"));
            Y.Assert.areEqual("http://batmanandrobin.com/episode/1",   RightNow.Ajax.getRntReferrerHeaderFromUrl("http://batmanandrobin.com/episode/1#nanananananaBatman"));
        },

        'A permanent RNT_REFERRER header is sent for every request': function() {
            var test = this,
                response = function(resp) {
                    test.resume(function() {
                        var urlReferrer = RightNow.Ajax.getRntReferrerHeaderFromUrl(window.location.href);
                        Y.Assert.isTrue(resp.responseText.indexOf('[HTTP_RNT_REFERRER] => ' + urlReferrer) > -1);
                    });
                };

            this.request('/ci/unitTest/wgetRecipient/echoHeaders/1', null, {
                type: 'GET',
                successHandler: response
            });
            this.wait();

            this.request('/ci/unitTest/wgetRecipient/echoHeaders', {foo: 'bar', baz: 'banana'}, {
                type: 'POST',
                successHandler: response
            });
            this.wait();
        },

        'Optional headers option is passed thru to Y.io': function() {
            var test = this,
                tester = function(response) {
                    test.resume(function() {
                        Y.assert(response.responseText.indexOf('[CONTENT_TYPE] => text/markdown') > -1);
                        Y.assert(response.responseText.indexOf('[HTTP_X_REQUESTED_WITH] => xmlhttprequest') > -1);
                    });
                };

            this.request('/ci/unitTest/wgetRecipient/echoHeaders', {}, {
                headers: { 'Content-Type': 'text/markdown' },
                successHandler: tester
            });
            this.wait();
        },

        testCheckForSocialExceptionsNoErrors: function() {
            var loginEventFired = false,
                socialUserEventFired = false;

            RightNow.Event.subscribe('evt_requireLogin', function(eventName, eventData){
                loginEventFired = true;
            }, this);
            RightNow.Event.subscribe('evt_userInfoRequired', function(eventName, eventData){
                socialUserEventFired = true;
            }, this);

            RightNow.Ajax.checkForSocialExceptions({responseText:'{}'});
            Y.Assert.isFalse(loginEventFired);
            Y.Assert.isFalse(socialUserEventFired);
        },

        testCheckForSocialExceptionsNotLoggedInError: function() {
            var loginEventFired = false;

            RightNow.Event.subscribe('evt_requireLogin', function(eventName, eventData){
                loginEventFired = true;
            }, this);

            RightNow.Ajax.checkForSocialExceptions({responseText:'{"errors":[{"errorCode":"ERROR_USER_NOT_LOGGED_IN"}]}'});
            Y.Assert.isTrue(loginEventFired);
        },

        testCheckForSocialExceptionsNoSocialUserError: function() {
            var socialUserEventFired = false;

            RightNow.Event.subscribe('evt_userInfoRequired', function(eventName, eventData){
                socialUserEventFired = true;
            }, this);

            RightNow.Ajax.checkForSocialExceptions({responseText:'{"errors":[{"errorCode":"ERROR_USER_HAS_NO_SOCIAL_USER"}]}'});
            Y.Assert.isTrue(socialUserEventFired);
        },

        testCheckForSocialExceptionsBlankSocialUserError: function() {
            var socialUserEventFired = false;

            RightNow.Event.subscribe('evt_userInfoRequired', function(eventName, eventData){
                socialUserEventFired = true;
            }, this);

            RightNow.Ajax.checkForSocialExceptions({responseText:'{"errors":[{"errorCode":"ERROR_USER_HAS_BLANK_SOCIAL_USER"}]}'});
            Y.Assert.isTrue(socialUserEventFired);
        },

        testIndicatesSocialUserError: function() {
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError({
                responseText:'{"errors":[{"errorCode":"ERROR_USER_NOT_LOGGED_IN"}]}'
            }));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError({
                responseText:'{"errors":[{"errorCode":"ERROR_USER_HAS_NO_SOCIAL_USER"}]}'
            }));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError({
                responseText:'{"errors":[{"errorCode":"ERROR_USER_HAS_BLANK_SOCIAL_USER"}]}'
            }));
            Y.Assert.isFalse(RightNow.Ajax.indicatesSocialUserError({
                responseText:'{"errors":[{"errorCode":""}]}'
            }));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError({
                errors: [{"errorCode": "ERROR_USER_NOT_LOGGED_IN"}]
            }));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError({
                errors: [{"errorCode": "ERROR_USER_HAS_NO_SOCIAL_USER"}]
            }));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError({
                errors: [{"errorCode": "ERROR_USER_HAS_BLANK_SOCIAL_USER"}]
            }));
            Y.Assert.isFalse(RightNow.Ajax.indicatesSocialUserError({
                errors: [{"errorCode": ""}]
            }));
            Y.Assert.isFalse(RightNow.Ajax.indicatesSocialUserError({
                invalidParameter: null
            }));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError("Potato!!! ERROR_USER_NOT_LOGGED_IN"));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError("ERROR_USER_HAS_NO_SOCIAL_USER Pikachu!!!"));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError("   Ola. ERROR_USER_HAS_BLANK_SOCIAL_USER Bueno  "));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError("User is not logged in"));
            Y.Assert.isTrue(RightNow.Ajax.indicatesSocialUserError("User does not have a display name"));
            Y.Assert.isFalse(RightNow.Ajax.indicatesSocialUserError("Llama llama duck"));
        },

        //@@@ 161031-000163
        'log to ACS on AJAX timeout': function() {
            var test = this,
                testUrl = '/ci/ajaxRequest/getAnswer/objectID/1',
                postData = 'abc=123&def=456',
                tester = function(response) {
                    test.resume(function() {
                        // YUI should set these
                        Y.assert(response.status === 0, "response status should be 0");
                        Y.assert(response.statusText === 'timeout', "response statusText should be 'timeout'");

                        // the suggested message is added by RightNow.Ajax; we'll settle for a not=null string
                        Y.assert(response.statusText, "response statusText should be 'timeout'");

                        // verify the ACS data is populated properly
                        Y.assert(acsResults.subject === 'cpAjax', "acsResults.subject should be 'cpAjax'");
                        Y.assert(acsResults.verb === 'timeout', "acsResults.verb should be 'timeout'");
                        Y.assert(acsResults.actionObject.url === testUrl, "acsResults.actionObject.url should be '" + testUrl + "'");
                        Y.assert(acsResults.actionObject.method === 'POST', "acsResults.actionObject.method should be 'POST'");
                        Y.assert(acsResults.actionObject.payload === postData, "acsResults.actionObject.payload should be '" + postData + "'");
                    });
                }, 
                successIsFailure = function(response) {
                    test.resume(function() {
                        // we need this to time out
                        Y.assert(false, "AJAX call did not time out");
                    });
                };

            this.request(testUrl, postData, {
                timeout: 0.001, // force a timeout
                failureHandler: tester,
                successHandler: successIsFailure
            });
            this.wait();
        },

    }));

    rightnowAjaxTests.add(new Y.Test.Case(
    {
        name : "convertObjectToPostStringTest",

        testSimpleItems: function()
        {
            var objectToConvert = {one: "one", two: 2, three: "three"};
            var postString = RightNow.Ajax.convertObjectToPostString(objectToConvert);
            Y.Assert.areSame(postString, "one=one&two=2&three=three");
        },

        testFunctions: function()
        {
            var objectToConvert = {one: "one", func: function(){alert('func');}, two: 2};
            Y.Assert.areSame(RightNow.Ajax.convertObjectToPostString(objectToConvert), "one=one&two=2");
        },

        testObjects: function()
        {
            var objectToConvert = {one: "one", obj: {subOne: "subOne"}, two: 2};
            Y.Assert.areSame(RightNow.Ajax.convertObjectToPostString(objectToConvert), "one=one&two=2");
        },

        testUndefineds: function()
        {
            var objectToConvert = {one: "one", undef: undefined, two: 2};
            Y.Assert.areSame(RightNow.Ajax.convertObjectToPostString(objectToConvert), "one=one&two=2");
        },

        testSpecialCharacters: function()
        {
            var objectToConvert = {one: "one&1", two: "two=2", three:"!@#$%^&*()+=[{]\/?|};:\",<.>~`", "&four": "four"};
            Y.Assert.areSame(RightNow.Ajax.convertObjectToPostString(objectToConvert), "one=one%261&two=two%3D2&three=!%40%23%24%25%5E%26*()%2B%3D%5B%7B%5D%2F%3F%7C%7D%3B%3A%22%2C%3C.%3E~%60&%26four=four");
        }
    }));

    rightnowAjaxTests.add(new Y.Test.Case(
    {
        name : "convertObjectToGetStringTest",

        tearDown: function()
        {
            delete Object.prototype.banana;
        },

        testSimpleItems: function()
        {
            Y.Assert.areSame(RightNow.Ajax.convertObjectToGetString({one: "one", two: 2, three: "three"}), "/one/one/two/2/three/three");
        },

        testValuesThatShouldBeIgnored: function()
        {
            Y.Assert.areSame(RightNow.Ajax.convertObjectToGetString({one: "one", func: function(){alert('func');}, two: 2}), "/one/one/two/2");
            Y.Assert.areSame(RightNow.Ajax.convertObjectToGetString({one: "one", obj: {subOne: "subOne"}, two: 2}), "/one/one/two/2");
            Object.prototype.banana = "BANANA!";
            Y.Assert.areSame(RightNow.Ajax.convertObjectToGetString({one: "one", two: 2}), "/one/one/two/2");
        },

        testFalsyValues: function()
        {
            Y.Assert.areSame(RightNow.Ajax.convertObjectToGetString({one: "one", undef: undefined, two: 2}), "/one/one/two/2");
            Y.Assert.areSame(RightNow.Ajax.convertObjectToGetString({one: "one", banana: null, two: 2}), "/one/one/two/2");
            Y.Assert.areSame(RightNow.Ajax.convertObjectToGetString({one: "one", banana: "", two: 2, banana2: " "}), "/one/one/two/2/banana2/%20");
        },

        testSpecialCharacters: function()
        {
            Y.Assert.areSame(RightNow.Ajax.convertObjectToGetString({one: "one&1", two: "two=2", three:"!@#$%^&*()+=[{]\/?|};:\",<.>~`", "&four": "four"}),
                "/one/one%261/two/two%3D2/three/!%40%23%24%25%5E%26*()%2B%3D%5B%7B%5D%2F%3F%7C%7D%3B%3A%22%2C%3C.%3E~%60/%26four/four");
        }
    }));

    rightnowAjaxTests.add(new Y.Test.Case(
    {
        name : "makeRequestTests",

        testNonExistingUrl: function()
        {
            // IE8 is getting a security popup that is causing this to fail.
            if(Y.UA.ie > 8 || !Y.UA.ie) {
                var context = this;
                RightNow.Event.fire = function(){ context.onBeforeAjaxRequestHandlerForNonExistingUrlCase.apply(context, arguments);};
                var requestObject = RightNow.Ajax.makeRequest("/bananas", {one: "one", two: "two"}, {failureHandler: this.nonExistantUrlFailureHandler, scope: this, data:{three: "three", four: "four"}});
                Y.Assert.isObject(requestObject);
                this.wait();
            }
        },

        testExistingUrl: function()
        {
            var context = this;
            RightNow.Event.fire = function(){ context.onBeforeAjaxRequsthandlerForExistingUrlCase.apply(context, arguments);};
            var requestObject = RightNow.Ajax.makeRequest("/ci/AjaxRequest/submitAnswerRating", {one: "one", two: "two"}, {type: "GET", successHandler: this.existingUrlSuccessHandler, scope: this, data:{three: "three", four: "four"}});
            Y.Assert.isObject(requestObject);
            this.wait();
        },

        onBeforeAjaxRequestHandlerForNonExistingUrlCase: function(type, data)
        {
            Y.Assert.areSame(type, "on_before_ajax_request");
            Y.Assert.isObject(data);
            Y.Assert.areSame(data.type, "POST");
            Y.Assert.areSame(data.url, "/bananas");
            Y.Assert.isObject(data.post);
            Y.Assert.areSame(data.post.one, "one");
            Y.Assert.areSame(data.post.two, "two");
        },

        nonExistantUrlFailureHandler: function(o, args)
        {
            this.resume(function(){
                Y.Assert.isObject(o);
                // The 404 page is https since it's rendered with the standard
                // template, which contains a password field.
                // If _this_ page is https then we get the 404 as expected, but
                // if this page is a different protocol than the 404 page then
                // the response code is 0.
                var expected = (window.location.protocol === 'http:') ? 0 : 404;
                Y.Assert.areSame(expected, o.status, "Values should be the same. window._phantom? " + (typeof window._phantom) + ", and protocol: '" + window.location.protocol + "'");
                Y.Assert.isObject(args);
                Y.Assert.areSame(args.three, "three");
                Y.Assert.areSame(args.four, "four");
            });
        },

        onBeforeAjaxRequsthandlerForExistingUrlCase: function(type, data)
        {
            Y.Assert.areSame(type, "on_before_ajax_request");
            Y.Assert.isObject(data);
            Y.Assert.areSame(data.type, "GET");
            Y.Assert.areSame(data.url, "/ci/AjaxRequest/submitAnswerRating");
            Y.Assert.isObject(data.post);
            Y.Assert.areSame(data.post.one, "one");
            Y.Assert.areSame(data.post.two, "two");
        },

        existingUrlSuccessHandler: function(o, args)
        {
            this.resume(function() {
                Y.Assert.areSame(1, o);
                Y.Assert.isObject(args);
                Y.Assert.areSame(args.three, "three");
                Y.Assert.areSame(args.four, "four");
            });
        }
    }));

    rightnowAjaxTests.add(new Y.Test.Case(
    {
        name : "addRequestDataTest",

        setUp: function()
        {
            RightNow.Ajax.addRequestData('always', 'here', true);
        },

        testDynamicAdditions: function()
        {
            RightNow.Ajax.addRequestData('dynamic', 'values');
            RightNow.Ajax.addRequestData('just', 'once', false);

            var context = this;
            RightNow.Event.fire = function(){ context.singleRequestAdditionsHandler.apply(context, arguments);};
            RightNow.Ajax.makeRequest("/ci/AjaxRequest/submitAnswerRating", {one: "one", two: "two"});
            RightNow.Event.fire = function(){ context.permanentRequestOnlyAdditionsHandler.apply(context, arguments);};
            RightNow.Ajax.makeRequest("/ci/AjaxRequest/submitAnswerRating", {one: "one", two: "two"});
        },

        singleRequestAdditionsHandler: function(type, data)
        {
            Y.Assert.isObject(data.post);
            Y.Assert.areSame(data.post.one, "one");
            Y.Assert.areSame(data.post.two, "two");
            Y.Assert.areSame(data.post.dynamic, "values");
            Y.Assert.areSame(data.post.just, "once");
            Y.Assert.areSame(data.post.always, "here");
        },

        permanentRequestOnlyAdditionsHandler: function(type, data)
        {
            Y.Assert.isObject(data.post);
            Y.Assert.areSame(data.post.one, "one");
            Y.Assert.areSame(data.post.two, "two");
            Y.Assert.areSame(data.post.always, "here");
        }
    }));

    rightnowAjaxTests.add(new Y.Test.Case(
    {
        name : "abortRequestTest",

        setUp: function()
        {
            RightNow.UI.Dialog = RightNow.UI.Dialog || {messageDialog: (function(scope) {
                                                                            return function(message, icon){
                                                                                scope.verifyDialogResult(message, icon);
                                                                            };
                                                                        }(this))};
        },

        testAbortedRequest: function()
        {
            RightNow.Event.fire = function(){};
            var requestObject = RightNow.Ajax.makeRequest("/ci/unitTest/javascript", {one: "one", two: "two"}, {type: "GET", successHandler: this.existingUrlSuccessHandler, scope: this, data:{three: "three", four: "four"}});
            Y.Assert.isTrue(RightNow.Ajax.abortRequest(requestObject));
        },

        verifyDialogResult: function(message, icon)
        {
            Y.Assert.isString(message);
            Y.Assert.isObject(icon);
        }
    }));

    rightnowAjaxTests.add(new Y.Test.Case(
    {
        name : "CTTests",

        testDefines: function()
        {
            Y.Assert.areSame(RightNow.Ajax.CT.CLICKSTREAM, 1);
            Y.Assert.areSame(RightNow.Ajax.CT.SOLVED_COUNT, 2);
            Y.Assert.areSame(RightNow.Ajax.CT.LINKS, 3);
            Y.Assert.areSame(RightNow.Ajax.CT.ANS_STATS, 4);
            Y.Assert.areSame(RightNow.Ajax.CT.STATS, 5);
            Y.Assert.areSame(RightNow.Ajax.CT.KEYWORD_SEARCHES, 8);
            Y.Assert.areSame(RightNow.Ajax.CT.WIDGET_STATS, 9);
            Y.Assert.areSame(RightNow.Ajax.CT.GA_SESSIONS, 11);
            Y.Assert.areSame(RightNow.Ajax.CT.GA_SESSION_DETAILS, 12);
            Y.Assert.areSame(RightNow.Ajax.CT.DQA_SERVICE, '/ci/dqa/publish');
        },

        testFunctions: function()
        {
            Y.Assert.isFunction(RightNow.Ajax.CT.addAction);
            Y.Assert.isFunction(RightNow.Ajax.CT.commitActions);
            Y.Assert.isFunction(RightNow.Ajax.CT.submitAction);
        }
    }));

    rightnowAjaxTests.add(new Y.Test.Case(
    {
        name : "nonTestableMethodExistanceTest",

        testForExistance: function()
        {
            Y.Assert.isFunction(RightNow.Ajax.genericSuccess);
            Y.Assert.isFunction(RightNow.Ajax.genericFailure);
            Y.Assert.isFunction(RightNow.Ajax.setIsNavigatingAwayFromPage);
            Y.Assert.areSame(RightNow.Ajax.MAX_URL_LENGTH, 2048);
        }
    }));

    rightnowAjaxTests.add(new Y.Test.Case(
    {
        name : "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, RightNow.Ajax);
        }
    }));

    rightnowAjaxTests.add(new Y.Test.Case(
    {
        name: "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, RightNow.Ajax);
        }
    }));

    return rightnowAjaxTests;
});
UnitTest.run();
