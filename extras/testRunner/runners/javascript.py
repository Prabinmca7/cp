#! /usr/bin/python

import os
import re
import codecs
import json
import threading
import Queue
import datetime

from lib import reporters
from lib.helpers import Helpers
from lib.commandLine import CommandLine
from runners.runner import TestRunner, TestException, TestResult


class JavascriptRunner(TestRunner):
    def __init__(self, siteUrl, optionalSegment, options):
        self.url = siteUrl
        self.options = options
        self.urls = []
        self.optionalSegment = optionalSegment

        sourcePath = options['source']
        if options['subtype'] == 'core':
            if not optionalSegment:
                CommandLine.say("Running all tests. Note an optional second parameter can be used to filter tests relative to `cp/webfiles/core/debug-js` [e.g. tests/RightNow.UI.test.js]")

            self.urls = self.__getCoreUrls(sourcePath, optionalSegment)
        elif options['subtype'] == 'widget':
            if not optionalSegment:
                CommandLine.say("Running all tests. Note an optional second parameter can be used to filter tests relative to `core/widgets/standard`")

            self.urls = self.__getWidgetUrls(sourcePath, optionalSegment)

        if len(self.urls) is 0:
            raise TestException("No test URLs found for the provided test type. Is the optional segment correct?")

        if options['browser'] == 'phantom':
            if options.get('module') is None:
                module = 'All'
            else:
                module = options['module']
            self.browserRunner = PhantomJSRunner(self.urls, module)
        else:
            self.browserRunner = SeleniumJSRunner(self.urls, options['browser'])

    def run(self):
        if self.options['subtype'] == 'widget' and not self.options['skipGenerate']:
            self.__generateWidgetTestPages(not self.options['deploy'], self.optionalSegment)

        results = self.browserRunner.run()

        cliReporter = reporters.newReporter(self.options.get('cliReporter'))
        self.__addResultsToReporter(results, cliReporter)
        CommandLine.say("Start test output")
        cliReporter.output(not self.options.get('includePasses'))

        # TSL - Pull out into a method and emulate for XML results
        artifactFile = self.options.get('htmlArtifact')
        if artifactFile:
            artifactReporter = reporters.newReporter('Artifact')
            self.__addResultsToReporter(results, artifactReporter)
            Helpers.safeMakedirs(os.path.dirname(artifactFile))
            Helpers.safeRemove(artifactFile)
            codecs.open(artifactFile, 'w', 'utf-8').write(artifactReporter.output())
            CommandLine.say("HTML Artifact written to %s" % artifactFile)

        CommandLine.say("End test output")

        # Let CruiseControl know that we had a test failure
        return 1 if cliReporter.totalFailures is not 0 else 0

    def __addResultsToReporter(self, results, reporter):
        for output, url in results:
            testResult = self.browserRunner.getResult(output, url)
            reporter.addTestResult(testResult)

    def __generateWidgetTestPages(self, skipDeploy, optionalSegment):
        url = self.url + 'ci/unitTest/rendering/jsFunctional'

        if skipDeploy:
            CommandLine.say('Skipping Deploy.')
            url += '/skipDeploy'
        else:
            CommandLine.say('Deploy was not skipped. Site will be deployed.')

        url += '/widgets/standard'
        if optionalSegment:
            if optionalSegment.endswith('.test') or optionalSegment.endswith('.test.js'):
                url += '/' + os.path.dirname(optionalSegment)
            else:
                url += '/' + optionalSegment

        CommandLine.say('Generating test pages with `%s`' % url)

        (body, headers, requestFailed, errorMessage) = Helpers.retrieveUrl(url, 900)
        if requestFailed or 'JavaScript Widget Interaction Tests generated.' not in body:
            raise TestException('Unable to generate the javascript test pages - %s' % errorMessage)

        CommandLine.say('Test files generated successfully')

    # Find all of the .test.js files and transform them into test URLs
    def __getCoreUrls(self, sourcePath, optionalSegment):
        urls = []
        path = os.path.join(sourcePath, 'webfiles', 'core', 'debug-js', optionalSegment)
        baseUrl = self.url + 'ci/unitTest/javascript/framework/'

        if os.path.isfile(path):
            urls.append(baseUrl + optionalSegment.replace('.test.js', ''))
        else:
            for filePath in Helpers.getFilesRecursively(path, '*.test.js'):
                urlFragment = filePath[filePath.index(path) + len(path):]
                urls.append(baseUrl + urlFragment.replace('\\', '/').replace('.test.js', '').strip('/'))

        return urls

    # Find all of the .test files in the source directory and if it contains a jstestfile line
    def __getWidgetUrls(self, sourcePath, optionalSegment):
        urls = testFiles = []
        basePath = os.path.join(sourcePath, 'core', 'widgets', 'standard')
        path = os.path.join(basePath, optionalSegment)
        baseUrl = self.url + 'ci/unitTest/rendering/getTestPage/widgets/standard/'

        # If a full file path is specified, if it points to a .test.js, find all test files which use that file.
        if os.path.isfile(path):
            if path.endswith('.test.js'):
                testFiles = [x for x in Helpers.getFilesRecursively(os.path.dirname(path), '*.test') if self.__isJSTestFile(x, os.path.basename(path))]
            else:
                return [optionalSegment.replace('.test', '')]
        else:
            testFiles = [x for x in Helpers.getFilesRecursively(path, '*.test') if self.__isJSTestFile(x)]

        for filePath in testFiles:
            urlFragment = filePath[filePath.index(basePath) + len(basePath):]
            urls.append(baseUrl + (urlFragment.replace('\\', '/').replace('.test', '') + self.__getUrlParameters(filePath)).strip('/'))

        return urls

    # Check if the file at the given path contains a jstestfile line
    def __isJSTestFile(self, path, optionalFilter='.*'):
        expression = re.compile(r'^jstestfile:\s*(%s)$' % optionalFilter, re.I)
        for line in open(path):
            if re.match(expression, line):
                return True
        return False

    # Add on any URL parameters that might be specified in the test
    def __getUrlParameters(self, path):
        expression = re.compile(r'^urlparameters:\s*(.*)$', re.I)
        for line in open(path):
            matches = re.match(expression, line)
            if matches:
                return self.__stripFixtures(matches.group(1))
        return ''

    # Disallow fixtures in urlparameters for widgetJS tests
    def __stripFixtures(self, url):
        hasHead = url.startswith('/')
        url = url.strip('/')

        result = []
        for key, value in zip(url.split('/')[0::2], url.split('/')[1::2]):
            if value.startswith('%') and value.endswith('%'):
                continue
            result.append(key)
            result.append(value)

        result = '/'.join(result)
        return result if not hasHead else '/' + result


class PhantomJSRunner(object):
    phantomWrapperScript = Helpers.getScriptDirectory() + '/phantomWrapper.js'
    maxThreads = 1
    timeout = 30

    def __init__(self, urls, module):
        okcsWidgetPath = 'widgets/standard/okcs'
        self.workQueue = Queue.Queue()
        for url in urls:
            if module == 'cp' and okcsWidgetPath in url:
                continue
            elif module == 'okcs' and okcsWidgetPath not in url:
                continue
            self.workQueue.put(url)

        self.phantomPath = Helpers.which('phantomjs')
        if not self.phantomPath:
            self.phantomPath = '/nfs/project/cp/bin/phantomjs'

        self.numberOfThreads = min(self.maxThreads, self.workQueue.qsize())

    def run(self):
        resultQueue = Queue.Queue()
        for i in range(self.numberOfThreads):
            thread = threading.Thread(target=PhantomJSRunner.workerThread, args=(self.workQueue, self.phantomPath, resultQueue))
            thread.daemon = True
            thread.start()

        CommandLine.say("All PhantomJS threads started")
        self.workQueue.join()
        CommandLine.say("All PhantomJS threads completed")

        return list(resultQueue.queue)

    def getResult(self, result, url):
        additionalDetails = []
        if result.get('consoleInfo'):
            additionalDetails.append('Console Log:')
            for info in result.get('consoleInfo'):
                additionalDetails.append('Type: %s' % info['type'])
                for argument in info['arguments']:
                    additionalDetails.append('Argument: %s' % argument)

        if result.get('trace'):
            additionalDetails.append('Stack Trace:')
            additionalDetails += result.get('trace').split('\n')[:7]

        tap, error = result.get('tap'), result.get('error')
        if tap and not error:
            try:
                tap = YUITapConverter(tap, url).convert()
            except TestException as e:
                error, tap = 'Unexpected exception when converting TAP: %s' % e, None

        return TestResult(
            url,
            tap,
            error,
            additionalDetails if len(additionalDetails) > 0 else None
        )

    @staticmethod
    def workerThread(urlQueue, processPath, resultQueue):
        while True:
            url = urlQueue.get()

            lines = []
            startTime = datetime.datetime.now()
            CommandLine.say('Running command: %s --ignore-ssl-errors=true --ssl-protocol=any %s %s' % (processPath, PhantomJSRunner.phantomWrapperScript, url))
            for line in Helpers.runCommand([processPath, '--ignore-ssl-errors=true', '--ssl-protocol=any', PhantomJSRunner.phantomWrapperScript, url]):
                if line != '':
                    lines.append(line)

            try:
                result = json.loads(''.join(lines))
            except ValueError:
                result = {'error': 'Unable to parse JSON response from PhantomJS\n' + ''.join(lines)}

            resultQueue.put((result, url))
            CommandLine.say(url + ': ' + str(datetime.datetime.now() - startTime) + '\n')

            urlQueue.task_done()


class SeleniumJSRunner(object):
    maxThreads = 5
    timeout = 30

    def __init__(self, urls, browser):
        self.browserName = browser.title()

        if self.browserName == 'Ie':
            self.timeout = 60

        self.workQueue = Queue.Queue()

        for url in urls:
            self.workQueue.put(url)

        #Since some browsers have a slow startup time, use a super scientific algorithm to determine how many to use
        self.numberOfThreads = min(self.maxThreads, (self.workQueue.qsize() / 4))

    def run(self):
        CommandLine.say('BrowserName is ' + self.browserName + ' and using a timeout of ' + str(self.timeout))
        try:
            from selenium import webdriver
            from selenium.common.exceptions import WebDriverException
        except ImportError:
            raise TestException("Unable to include Selenium Python package. Please install the Selenium package.")

        resultQueue = Queue.Queue()
        drivers = []
        try:
            for i in range(self.numberOfThreads):
                if self.browserName == 'Chrome':
                    try:
                        driver = webdriver.Chrome()
                    except WebDriverException as e:
                        CommandLine.say("Encountered error starting Chrome. Retrying")
                        CommandLine.say(e, 'error')
                        #Occasionally an unusual error is generated by the chrome driver when it's being
                        #created -- "Unknown Error: Unable to discover open pages" -- this appears to be a bug
                        #with the underlying Chrome Driver. The problem is tracked in http://bit.ly/19cL3ml. If it happens, retry once
                        driver = webdriver.Chrome()
                elif self.browserName == 'Firefox':
                    # explicitly set proxy type to 'auto-detect proxy settings', since it seems that Selenium
                    # will create a new profile in Firefox and those defaults aren't always the best
                    fp = webdriver.FirefoxProfile()
                    fp.set_preference("network.proxy.type", 4);
                    driver = webdriver.Firefox(fp)
                else:
                    driver = getattr(webdriver, self.browserName)()

                driver.set_page_load_timeout(self.timeout)
                drivers.append(driver)

                thread = threading.Thread(target=SeleniumJSRunner.workerThread, args=(self.workQueue, driver, resultQueue, self.timeout))
                thread.daemon = True
                thread.start()

            CommandLine.say("All SeleniumJS threads started")
            self.workQueue.join()
            CommandLine.say("All SeleniumJS threads completed")

        finally:
            for driver in drivers:
                driver.quit()

        return list(resultQueue.queue)

    def getResult(self, result, url):
        tap, error = result.get('tap'), result.get('error')
        if tap and not error:
            try:
                tap = YUITapConverter(tap, url).convert()
            except TestException as e:
                error, tap = 'Error converting TAP: %s' % e, None

        return TestResult(url, tap, error, None)

    @staticmethod
    def workerThread(urlQueue, driver, resultQueue, timeout):
        while True:
            url = urlQueue.get()
            result = SeleniumJSRunner.processUrl(url, driver, timeout)
            resultQueue.put((result, url))
            urlQueue.task_done()

    @staticmethod
    def processUrl(url, driver, timeout):
        try:
            from selenium.webdriver.support.ui import WebDriverWait
            from selenium.common.exceptions import TimeoutException, WebDriverException
        except ImportError:
            raise TestException("Unable to include Selenium Python package. Please install the Selenium package.")

        try:
            driver.get(url)
        except TimeoutException:
            return {'error': 'Test timed out. The page did not load after %s seconds.' % timeout}

        try:
            driver.maximize_window()

            if driver.page_source.find('Error: Unable to retrieve test file.') != -1:
                return {'error': 'Selenium successfully loaded the test page, but the content was not generated correctly'}

            #TSL - Should this check testStatus and testResults to fail early in selenium (it currently fails early when an exception
            #occurs in phantom. We should probably bring that over. If there is ever an `error` ID element in the `testResults` element
            #then break the test with the given error message, otherwise this will just spin for 30 seconds.
            #TSL - Selenium is not failing on exception.
            tapResults = WebDriverWait(driver, timeout).until(lambda driver: driver.execute_script('return window.tapResults'))

            cookies = driver.get_cookies()

            #Clear out any cookies that may have been set. If the cookies are not cleared, some tests may break from residual logins.
            driver.delete_all_cookies()

        except TimeoutException:
            return {'error': 'The Selenium TAP results never appeared on the test page.'}
        except WebDriverException as e:
            return {'error': 'Unexpected Exception while waiting for TAP results: %s' % e}

        return {'tap': tapResults, 'cookies': cookies}


class YUITapConverter(object):
    def __init__(self, tap, url):
        self.lines = tap.strip().split('\n')
        self.url = url
        self.currentSuite = None
        self.currentCase = None
        self.tests = []

        self.commentExpression = re.compile(r'^#(Begin|End|Ignored) (test|testcase|testsuite) (.*)(\(\d+ failed of \d+\))?$')
        self.testExpression = re.compile(r'^(ok|not ok) (\d+) - (.*)$')

        self.ymlOutputFormat = '%s %s - %s : %s\n%s'
        self.standardOutputFormat = '%s %s - %s : %s'

    def convert(self):
        self.__processInput()
        return self.__produceOutput()

    def __processInput(self):
        self.plan = self.__getPlan()

        while self.__hasLines():
            if self.__isCommentLine():
                self.__processCommentLine()
            elif self.__isTestLine():
                self.__processTestLine()
            else:
                raise TestException('Unexpected line in YUI TAP output.')

    def __produceOutput(self):
        if len(self.tests) is 0:
            raise TestException('The YUI TAP output did not contain a test point.')

        output = []
        output.append('TAP version 13')
        for test in self.tests:
            output.append(test)
        output.append(self.plan)
        return '\n'.join(output)

    def __hasLines(self):
        return len(self.lines) is not 0

    def __getNextLine(self):
        return self.lines.pop(0)

    def __getPlan(self):
        plan = self.__getNextLine()
        if plan.find('1..') is not 0:
            raise TestException('Unable to locate a plan on the first line of the YUI TAP')
        return plan

    def __isCommentLine(self):
        return self.lines[0].find('#') is 0

    def __isTestLine(self):
        return self.lines[0].find('ok') is 0 or self.lines[0].find('not ok') is 0

    def __processCommentLine(self):
        line = self.__getNextLine()
        matches = re.search(self.commentExpression, line)
        if not matches:
            raise TestException('Comment line `%s` did not match expected format' % line)

        if matches.group(2) == 'testsuite':
            self.currentSuite = matches.group(3) if matches.group(1) == 'Begin' else None
        elif matches.group(2) == 'testcase':
            self.currentCase = matches.group(3) if matches.group(1) == 'Begin' else None

    def __processTestLine(self):
        matches = re.search(self.testExpression, self.__getNextLine())
        if not matches:
            raise TestException('Test line did not match expected format')

        message = ''
        if matches.group(1) == 'not ok':
            while self.__hasLines() and not self.__isTestLine() and not self.__isCommentLine():
                message += self.__getNextLine() + '\n'
            if message != '':
                message = '  ---\nmessage: "' + message + '"\n  ...\n'

        if message != '':
            self.tests.append(self.ymlOutputFormat % (matches.group(1), matches.group(2), self.url, matches.group(3), message))
        else:
            # TSL - ATM I don't do anything with the testsuite or testcase. We could add this somewhere in the output.
            # Maybe as part of the YML message during failures?
            self.tests.append(self.standardOutputFormat % (matches.group(1), matches.group(2), self.url, matches.group(3)))
