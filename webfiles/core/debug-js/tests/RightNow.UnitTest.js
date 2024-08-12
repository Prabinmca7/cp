if(UnitTest) throw new Error('The UnitTest namespace is already defined');

var UnitTest = (function() {
    var _Y,
        /* Duration before deciding that the issue is not something async in ms */
        _testTimeout = 30000,
        /* Polling delay time between namespace and widget instance checks in ms */
        _pollingInterval = 100,
        /* A default YUI sandbox configuration for each test suite */
        _yuiSandboxConfig = {
            timeout: _testTimeout,
            loadErrorFn: function (yui, callback, errorObject, expectedNodes) {
                console.log(errorObject.data);
                console.log(errorObject);
                console.log(expectedNodes);
                throw new Error("Error Loading YUI resource - " + errorObject.msg);
            },
            fetchCSS: true,
            logInclude: {TestRunner: true}
        },
        /* The default list of YUI modules loaded with each test */
        _yuiSandboxModules = [
            'test', 'node-event-simulate', 'node', 'escape', 'array-extras'
        ],
        /* The default set of namespaces that should be defined for all Widget and Framework tests */
        _bootstrapNamespaces = [
            // Widgets
            [
                'RightNow',
                'RightNow.Widgets',
                'RightNow.Event',
                'RightNow.JSON',
                'RightNow.Ajax',
                'RightNow.Url',
                'RightNow.UI',
                'RightNow.UI.AbuseDetection',
                'RightNow.UI.Dialog',
                'RightNow.UI.findParentForm'
            ],
            // Framework
            [
                'RightNow',
                'RightNow.JSON'
            ],
            // Admin
            [/* Nothing required yet */],
            // WidgetNoJS
            [/* Nothing required */]
        ],
        /*
           Corresponds w/ indices in _bootstrapNamespaces. The test specifies which type it is, which will tell us
           what namespaces need to be ready before running the test.
        */
        _typesOfTests = {
            Widget: 0,
            Framework: 1,
            Admin: 2,
            WidgetNoJS: 3
        },
        /* An object containing all of the added test suites */
        _suites = [],
        /* Whether or not the tests have already started running */
        _isRunning = false,
        /* Number of completed suites */
        _completedSuites = 0,
        /* Whether or not the tests have succeeded */
        _hasFailed = false;

        /* Add in a special error container to catch any unexpected exceptions */
        var testResults = document.createElement('pre');
        testResults.setAttribute('id', 'testResults');
        document.body.insertBefore(testResults, document.body.firstChild);

        var testStatus = document.createElement('h1');
        testStatus.setAttribute('id', 'testStatus');
        testStatus.innerHTML = 'Tests Running...';
        document.body.insertBefore(testStatus, document.body.firstChild);

        window.onerror = function(errorMessage, file) {
            _hasFailed = true;
            document.getElementById('testResults').innerHTML = errorMessage + ' in ' + file;
        };

        /**
         * Check if a namespace exists in the global scope.
         */
        function _isNamespace(str) {
            var pieces = str.split('.'),
                scope = window,
                piece;

            while(piece = pieces.shift()) {
                if(!scope[piece])
                    return false;
                scope = scope[piece];
            }
            return true;
        }

        function _displayTestResults(results) {
            if(_hasFailed) {
                 var failCount = _addFailedMessages(_Y.one('#testResults'), results.split('\n'));
                _Y.one('#testStatus').setHTML('Tests Failed: ' + (failCount)).setStyle('color', 'red');
                document.title = 'Failed';
                _Y.one('head').appendChild(_Y.Node.create('<link rel="icon" href="/euf/core/images/error.gif">'));
            }
            else {
                _Y.one('#testStatus').setHTML('Tests Passed').setStyle('color', 'green');
                document.title = 'Passed';
                _Y.one('head').appendChild(_Y.Node.create('<link rel="icon" href="/euf/core/images/pixel.gif">'));
            }
        }

        function _addFailedMessages(container, lines) {
            var testCount = 0, failCount = 0, currentSuite, currentCase;

            var addLine = function(message, indent) {
                var separator = indent ? (new Array(indent + 1).join(' ')) : '';
                container.set('text', container.get('text') + separator + message + '\n');
            };

            _Y.Array.each(lines, function(line) {
                if(line.indexOf('1..') === 0) {
                    testCount = parseInt(line.substring(3, line.length), 10);
                }
                else if(line.indexOf('#') === 0) {
                    if(line.indexOf('testsuite') !== -1) {
                        currentSuite = line.substring(line.indexOf('testsuite') + 'testsuite'.length + 1, line.length);
                    }
                    else if(line.indexOf('testcase') !== -1) {
                        currentCase = line.substring(line.indexOf('testcase') + 'testcase'.length + 1, line.length);
                    }
                }
                else if(line.indexOf('not ok') === 0) {
                    failCount++;
                    if(currentSuite) {
                        addLine('Test Suite - ' + currentSuite);
                    }
                    addLine('Test Case - ' + currentCase, currentSuite ? 2 : 0);
                    addLine(line.substring(line.indexOf('- ') + 2, line.length), currentSuite ? 6 : 4);
                }
                else if(line.indexOf('ok') !== 0) {
                    addLine(line, currentSuite ? 6 : 4);
                }
            });

            container.setStyle('color', '#8F0000');

            return failCount;
        }

        function executeNextCallback(callbackChain) {
            var args = Array.prototype.slice.call(arguments).slice(1);
            args.push(callbackChain.slice(1));
            callbackChain[0].apply(this, args);
        }

        function enqueueTestSuites(completeCallback) {
            _Y.Array.each(_suites, function(suite) {
                validateSuite(suite);

                getSuiteDependencies(suite, function() {
                    _completedSuites++;
                    if(_completedSuites === _suites.length) {
                        //Once all of the suites have loaded their dependencies, call all the callbacks and add the suites
                        _Y.Array.each(_suites, function(completedSuite) {
                            var yuiSuite = completedSuite.callback.apply(this, completedSuite.callbackArguments);

                            if(!yuiSuite) {
                                throw new Error('The addSuite callback did not return a YUI Test Suite');
                            }

                            _Y.Test.Runner.add(yuiSuite);
                        });

                        completeCallback();
                    }
                });
            });

        }

        /**
         * Resolve all of the dependencies for each individual suite working through a callback chain
         * before ultimately calling the `completeCallback` provided by the initial caller.
         * @param  {Object} suite              The suite object
         * @param  {Function} completeCallback The callback to be executed once all suites have been added to the test runner
         */
        function getSuiteDependencies(suite, completeCallback) {
            var orderedDependencies = [
                getPreloadJSFiles,
                getYUISandbox,
                waitForJSFiles,
                waitForNamespaces
            ];

            if(suite.type === UnitTest.Type.Widget) {
                orderedDependencies.push(waitForWidgetInstance);
                orderedDependencies.push(enqueueWidgetSuiteCallback);
            }
            else {
                orderedDependencies.push(enqueueSuiteCallback);
            }

            orderedDependencies.push(completeCallback);
            executeNextCallback(orderedDependencies, suite);
        }

        function getPreloadJSFiles(suite, callbackChain) {
            if(suite.preloadFiles) {
                _Y.Get.load(suite.preloadFiles, function(error) {
                    if (error) {
                        throw new Error('An error occurred while loading the JS files - ' + error[0].error);
                    }

                    executeNextCallback(callbackChain, suite);
                });
            }
            else {
                executeNextCallback(callbackChain, suite);
            }
        }

        function getYUISandbox(suite, callbackChain) {
            YUI(_yuiSandboxConfig).use(suite.yuiModules.concat(_yuiSandboxModules), function(sandboxY) {
                executeNextCallback(callbackChain, suite, sandboxY);
            });
        }

        function waitForJSFiles(suite, sandboxY, callbackChain) {
            //Generate the sandbox, load in the JS dependencies if any, and run the suite.
            if(suite.jsFiles && suite.jsFiles.length) {
                sandboxY.Get.js(replaceCfgInPaths(suite.jsFiles), function(err) {
                    if(err) {
                        throw new Error('An error occurred while loading the JS files - ' + err[0].error);
                    }

                    executeNextCallback(callbackChain, suite, sandboxY);
                });
            }
            else {
                executeNextCallback(callbackChain, suite, sandboxY);
            }
        }

        function waitForNamespaces(suite, sandboxY, callbackChain) {
            var allNamespaces = _bootstrapNamespaces[suite.type] || [],
                scope = this;

            if(suite.namespaces) {
                allNamespaces = allNamespaces.concat(suite.namespaces);
            }

            for(var i = 0, namespace; i < allNamespaces.length; i++) {
                namespace = allNamespaces[i];

                if(!_isNamespace(namespace)) {
                    if(suite.timeActive > _testTimeout) {
                        throw new Error('An error occurred. The following namespace does not exist "' + namespace + '".');
                    }

                    setTimeout(function() {
                        suite.timeActive += _pollingInterval;
                        waitForNamespaces.call(scope, suite, sandboxY, callbackChain);
                    }, _pollingInterval);

                    return;
                }
            }

            executeNextCallback(callbackChain, suite, sandboxY);
        }

        function waitForWidgetInstance(suite, sandboxY, callbackChain) {
            //Some tests don't need an instance ID, despite them being widget tests
            if(!suite.instanceID) {
                executeNextCallback(callbackChain, suite, sandboxY, null);
                return;
            }

            var instanceIDs = [suite.instanceID].concat(suite.subInstanceIDs || []),
                scope = this;

            for(var i = 0, instanceID; i < instanceIDs.length; i++) {
                instanceID = instanceIDs[i];

                if(!RightNow.Widgets.getWidgetInstance(instanceID)) {
                    if(suite.timeActive > _testTimeout) {
                        throw new Error('An error occurred, the widget instance "' + instanceID + '" could not be found.');
                    }

                    setTimeout(function() {
                        suite.timeActive += _pollingInterval;
                        waitForWidgetInstance.call(scope, suite, sandboxY, callbackChain);
                    }, _pollingInterval);

                    return;
                }
            }

            executeNextCallback(callbackChain, suite, sandboxY, RightNow.Widgets.getWidgetInstance(suite.instanceID));
        }

        function enqueueWidgetSuiteCallback(suite, sandboxY, widgetInstance, callbackChain) {
            suite.callbackArguments = [sandboxY, widgetInstance, widgetInstance ? widgetInstance.baseSelector : null];
            executeNextCallback(callbackChain);
        }

        function enqueueSuiteCallback(suite, sandboxY, callbackChain) {
            suite.callbackArguments = [sandboxY];
            executeNextCallback(callbackChain);
        }

        /**
         * For any file paths within files:
         * {cfg} is replaced by the first part of the host name
         * (expected to be the interface name) + .cfg
         * @param  {Array} files Files to look at
         * @return {Array}       Processed files
         */
        function replaceCfgInPaths(files) {
            var cfg = window.location.host.split('.')[0] + '.cfg';

            return _Y.Array.map(files, function(file) {
                return file.replace('{cfg}', cfg);
            });
        }

        function validateSuite(suite) {
            if(!_Y.Object.hasValue(_typesOfTests, suite.type)) {
                throw new Error('Attempting to add a suite with an invalid type. Valid types are UnitTest.Type.(' + _Y.Object.keys(_typesOfTests).join('|') + ')');
            }

            if(!suite.yuiModules) {
                suite.yuiModules = [];
            }

            if(!_Y.Lang.isArray(suite.yuiModules)) {
                throw new Error('`yuiModules` is specified, but the value is not an array. Either remove the unused value or convert it to an array.');
            }
        }

    return {
        /**
         * Add a single test suite to the system. Tests are only executed when run() is invoked.
         * @param {object} config - A configuration object containing the following: {
         *          type: <A required testType of either UnitTest.Type.Widget, UnitTest.Type.Framework or UnitTest.Type.Admin>,
         *          instanceID: <optional - if the test type isn't Widget then don't supply this.
         *          If the test type is Widget and the widget will be auto-instantianted and passed to callback,
         *          then supply the expected instanceID for the widget (e.g. "Multiline_0".
         *          If the test type is Widget and no instanceID is supplied, then it's not expected that the widget's
         *          JS will be included and that the widget will be auto-instantiated>,
         *          jsFiles: <An optional array of js dependencies e.g. ['/euf/core/webfiles/debug-js/RightNow.Event.js'].
         *          if a file's path contains "{cfg}" then it's replaced by the current site's .cfg dir.
         *          e.g. '/cgi-bin/{cfg}/php/cp/core/widgets/standard/..logic.js' => '/cgi-bin/trunk-slow-testsite2/php/cp/core/widgets/standard/..logic.js'>,
         *          preloadFiles: <An optional array of js / css dependencies that should be loaded onto the page _before_ `yuiModules` are
         *          attempted to be loaded; used when depending on a non-standard YUI module that YUI requires to be on the page
         *          before knowing about it>
         *          yuiModules: <An optional array of yui modules e.g. ['history']>,
         *          namespaces: <An optional array of required namespaces e.g. ['RightNow.UI.Dialog']>,
         *      }
         *
         * @param {function} callback - A callback function to execute when all the dependencies have been
         *          resolved. Depending on the testType we will expect one of the following signatures:
         *          UnitTest.Type.Widget: function(Y, widgetInstance, baseSelector),
         *          UnitTest.Type.Framework: function(Y)
         */
        addSuite: function(suite, callback) {
            if(_isRunning) {
                throw new Error('The tests are already running. All suites must be added before running the tests.');
            }

            if(!callback) {
                throw new Error('Attempting to add a suite without a valid callback.');
            }

            suite.timeActive = 0;
            suite.callback = callback;
            _suites.push(suite);

            return this;
        },

        /**
         * Expose the test types
         */
        Type: _typesOfTests,
        /**
         * Used in overrideMakeRequest as a way to skip validation on the value of a key:value pair
         */
        NO_VALUE: '313ph@nt_b4n@n4',

        /**
         * Do it. Run through all of the added test suites.
         */
        run: function() {
            if(_isRunning) {
                throw new Error('The tests are already running. All suites must be added before running the tests.');
            }
            _isRunning = true;

            YUI({
                timeout: _testTimeout,
                loadErrorFn: function (yui, callback, errorObject, expectedNodes) {
                    console.log(errorObject.data);
                    console.log(errorObject);
                    console.log(expectedNodes);
                    throw new Error("Error Loading YUI resource - " + errorObject.msg);
                },
                fetchCSS: true
            }).use(['node', 'console', 'console-filters', 'array-extras', 'test'], function(Y) {
                _Y = Y;

                //Add in a global logger instance and configure it to catch messages from all of the test suites.
                if(!_Y.one('#testLogger')) {
                    _Y.one(document.body).append('<div id="testLogger" class="yui3-skin-sam"></div>');

                    new _Y.Console({
                        plugins: [_Y.Plugin.ConsoleFilters],
                        logSource: _Y.Global,
                        newestOnTop: false,
                        style: 'block',
                        width: '900px',
                        height: '600px'
                    }).render('#testLogger')
                      .filter.set('category.info', false);
                }

                enqueueTestSuites(function() {
                    _Y.Test.Runner.on('complete', function() {
                        var tapResults = _Y.Test.Runner.getResults(_Y.Test.Format.TAP);
                        _displayTestResults(tapResults);

                        window.tapResults = tapResults;

                    });

                    _Y.Test.Runner.on('fail', function() {
                        _hasFailed = true;
                    }, this);

                    //And run the tests
                    _Y.Test.Runner.run();
                });
            });
        },

        /**
         * Common unit test function to iterate over item and ensure that none of its members
         * begin with a leading '_', ensuring that all private members are hidden.
         * @param {Object} item The object to check
         */
        recursiveMemberCheck: function(Y, item) {
            if(typeof item === "object" && item !== null && !item.tagName) {
                for(var index in item) {
                    if(item.hasOwnProperty(index)) {
                        Y.Assert.areNotSame(index[0], "_", "Why is " + index + " publicly accessible?");
                        if(typeof item[index] === "object" && item[index] !== null && !item[index].tagName) {
                            this.recursiveMemberCheck(Y, item[index]);
                        }
                    }
                }
            }
        },

        /**
         * Override the next call to the makeRequest function and optionally do assertions on the parameters normally passed to makeRequest by the widget.
         * Also, optionally mock return data back to the widget for further response testing.  This function should be used whenever possible
         * instead of manually overriding makeRequest, using wait/resumes, or simulating clicks, as it is significantly quicker and less prone
         * to failures of external services. Minimally, this function will prevent makeRequest from being called once, which could be useful
         * if all you want to do is prevent an AJAX request.
         * @param  {String=}   expectedEndpoint Expected AJAX endpoint from the makeRequest call.
         * @param  {Object=}   expectedPostData Object containing key:value pairs of the data object passed in to the makeRequest function.
         *                                      This function only checks for surface-level associations, so if nested objects
         *                                      are used, they would need to be identical in structure and content.  The
         *                                      expectedPostData list does not have to be exhaustive, so if there are complex
         *                                      data objects to be tested, it may be better to test those manually, rather then
         *                                      with this utility function.  If you want to check that a key exists without checking the value,
         *                                      use the UnitTest.NO_VALUE variable.
         * @param  {String=}   callback         Name of the callback function for the AJAX request.
         * @param  {Object=}   widgetContext    Widget context for the tested widget.  Required if callback is set.
         * @param  {Mixed=}    responseData     Mocked data to pass to the callback function. Used only if callback is set.
         * @param  {Object=}   origEventObj     Depending on the widget, the original event object may be used.  Provide it here. Used only if callback is set.
         * @return {Object}                     Dummy object representing the result of the Y.io call
         */
        overrideMakeRequest: function(expectedEndpoint, expectedPostData, callback, widgetContext, responseData, origEventObj) {
            var originalMakeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function() {
                RightNow.Ajax.makeRequest = originalMakeRequest;
                if(!_Y) {
                    return;
                }

                var args = Array.prototype.slice.call(arguments);

                // Log the request to assist with debugging.
                if(args) {
                    var logString = "\nDEBUG: An AJAX request was made";
                    if(args[0]) {
                        logString += " to the " + args[0] + " endpoint.";
                    }
                    console.log(logString);
                    console.log(args);
                    console.log('\n');
                }

                if(expectedEndpoint) {
                    _Y.Assert.areSame(expectedEndpoint, args[0], 'When calling makeRequest, ' + expectedEndpoint + ' was the expected endpoint, but ' + args[0] + ' was found.');
                }

                if(expectedPostData) {
                    _Y.Assert.isNotNull(args[1], 'No post data was passed into the makeRequest.  Post data was expected.');
                    _Y.Object.each(expectedPostData, function(expectedVal, expectedKey) {
                        _Y.Assert.isTrue(expectedKey in args[1], 'Expected post data key ' + expectedKey + ' was not found in the postData object.');
                        if(expectedVal !== UnitTest.NO_VALUE) {
                            _Y.Assert.areSame(expectedVal, args[1][expectedKey], 'Expected to find value ' + expectedVal +
                                ' paired with key ' + args[1] + ' but instead found ' + args[1][expectedKey] + '.');
                        }
                    }, this);
                }

                if(callback && widgetContext) {
                    widgetContext[callback](responseData, origEventObj);
                }

                // Since makeRequest returns the result of Y.io, and we aren't actually making a request, just return an empty object.
                return {};
            };
        }
    };
}());
