/**
 * This wrapper for PhantomJS is a slightly modified copy of the work done by Dav Glass for the YUI project's GroverJS.
 * IMPORTANT NOTE: This software DOES NOT ship with the RightNow CX product
 *
 * Software License Agreement (BSD License)
 *
 * Copyright (c) 2012, Dav Glass <davglass@gmail.com>.
 * All rights reserved.
 *
 * Redistribution and use of this software in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * Redistributions of source code must retain the above
 * copyright notice, this list of conditions and the
 * following disclaimer.
 *
 * Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the
 * following disclaimer in the documentation and/or other
 * materials provided with the distribution.
 *
 * The name of Dav Glass may not be used to endorse or promote products
 * derived from this software without specific prior
 * written permission of Dav Glass.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR
 * TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
 * ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

var waitTimer,
    waitCounter = 0,
    file = phantom.args[0],
    timeout = parseInt(phantom.args[1], 10),
    exited = false,
    debug = false, //Internal debugging of wrapper
    logConsole = true,//(phantom.args[2] === 'true' ? true : false),
    log = function(a, b) {
        b = (typeof b === 'undefined') ? '' : b;
        if (debug) {
            console.log(a, b);
        }
    },
    consoleInfo = [];

var getTestStatus = function(page) {
    return page.evaluate(function() {
        return document.getElementById('testStatus').innerHTML;
    });
};

var getTestResults = function(page) {
    return page.evaluate(function() {
        return document.getElementById('testResults').innerHTML;
    });
};

var getTapResults = function(page) {
    return page.evaluate(function() {
        return window.tapResults;
    });
};

var throwError = function(msg, trace) {
    log('throwError executed');
    var json = {
        error: msg,
        consoleInfo: consoleInfo
    };

    if (trace) {
        trace.forEach(function(item) {
            json.trace += '\n' + item.file +  ':' + item.line;
        });
    }

    if (!exited) {
        console.log(JSON.stringify(json));
    }
    exited = true;
    phantom.exit(1);
};

var waitForResults = function(page, cb) {
    waitTimer = setInterval(function() {
        waitCounter++;
        log('Waiting on Results', waitCounter);

        var tapResults = getTapResults(page);
        if (tapResults) {
            clearInterval(waitTimer);
            log('Found Results');

            var results = {
                tap: tapResults,
                status: getTestStatus(page),
                consoleInfo: consoleInfo
            };

            cb(JSON.stringify(results));
            return;
        } else {
            log('NO RESULTS');
        }
    }, 100);
};

var executeTest = function(file, cb) {
    log('executing tests in ', file);
    var page = require('webpage').create(),
        opened = false;

    page.settings.javascriptEnabled = true;
    page.settings.localToRemoteUrlAccessEnabled = true;
    page.settings.loadImages = true;
    page.settings.loadPlugins = true;
    page.viewportSize = {
      width: 1024,
      height: 768
    };

    if (debug) {
        page.onConsoleMessage = function(msg) {
            console.log('[console.log]', msg);
        };
    }

    if (logConsole) {
        page.onConsoleMessage = function() {
            var args = [], i = 0;
            for (i = 0; i < arguments.length; i++) {
                args.push(arguments[i]);
            }
            consoleInfo.push({
                type: 'console.log',
                'arguments': args
            });
        };
        page.onAlert = function(msg) {
            consoleInfo.push({
                type: 'window.alert',
                'arguments': [msg]
            });
        };
    }
    page.onError = function(msg, trace) {
        throwError(msg, trace);
    };

    if (!opened) {
        log('Opening File', file, opened);
        page.open(file, function(status) {
            log('Opened File', file);
            if (opened) {
                return;
            }

            //Fail fast if the page did not render successfully
            if (page.content.indexOf("Error: Unable to retrieve test file") !== -1 || status === 'fail') {
                throwError('Phantom successfully loaded the test page, but the content was not generated correctly.');
            }

            log('Status: ', status);

            waitForResults(page, function(results) {
                log('TAP Results Returned');
                cb(page, results);
            });

            opened = true;
        });
    }
};

if (!phantom.args.length) {
    console.log('Please provide some test files to execute');
    phantom.exit(1);
}

if (isNaN(timeout)) {
    timeout = 30; //Default to 30 seconds before failing the test
}

timer = setTimeout(function() {
    throwError('Script Timeout');
}, (timeout * 1000));

executeTest(file, function(page, results) {
    log('executeTest callback fired');
    if (!exited) {
        console.log(results);
    }
    exited = true;
    phantom.exit();
});