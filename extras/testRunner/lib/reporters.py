# coding: utf-8

import platform
import sys
from lib.commandLine import CommandLine
import os
from string import Template
import codecs
import tapHarness
import codeSnifferHarness
import cgi
from runners.runner import TestResult

Reporters = ['TAP', 'Spec']
DefaultReporter = 'Spec'


def newReporter(type):
    """Instantiates the specified reporter"""

    if type is None:
        type = DefaultReporter

    return getattr(sys.modules[__name__], type + 'Reporter', DefaultReporter + 'Reporter')()


class TestReporter(object):
    """
    Minimal reporter from which other reporters can subclass to get prettier output.
    """
    def __init__(self, testName=None):
        self.results = []
        self.errors = []
        self.totalPasses = 0
        self.totalFailures = 0
        self.testName = testName

    def addTestResult(self, testResult):
        if testResult.isFailure():
            # Bad http response from the server.
            self.errors.append(testResult)
        else:
            self.results.append(testResult)

    def output(self, omitPasses=False):
        """
        Determines the number of pass / fails and outputs the results.
        """
        self.__processResults(omitPasses)
        self.__processErrors()
        return self.outputSummary(self.totalPasses, self.totalFailures)

    def failures(self):
        return self.totalFailures

    def outputPass(self, case, url):
        CommandLine.puts('pass! - ' + case['suite'] + ' : ' + case['test'])

    def outputFail(self, case, url):
        CommandLine.puts('fail! - ' + case['suite'] + ' : ' + case['test'])
        CommandLine.puts(case['message'])

    def outputAdditionalFailureInfo(self, info):
        CommandLine.puts(info.pop(0))
        for line in info:
            CommandLine.puts(line)

    def outputSummary(self, passes, failures):
        CommandLine.puts('{0} / {1}'.format(passes, failures + passes))

    def __processResults(self, omitPasses):
        """
        Evaluates the TAP test results and outputs passes / failures as
        they occur in the results.
        """
        for testResult in self.results:
            harness = tapHarness.TapHarness(testResult.tap)
            cases = harness.test_cases()
            for case in cases():
                if case is None:
                    continue
                if case['type'] == 'pass' and omitPasses is not True:
                    self.outputPass(case, testResult.url)
                elif case['type'] == 'fail':
                    self.outputFail(case, testResult.url)

            self.totalPasses += harness.passes()
            self.totalFailures += harness.failures()

            if harness.failures() is not 0 and testResult.additionalInformation:
                self.outputAdditionalFailureInfo(testResult.additionalInformation)

            for errorMessage in harness.errors():
                self.errors.append(TestResult(testResult.url, None, errorMessage))

    def __processErrors(self):
        """
        Outputs any errors that occurred prior to or during the tests' execution.
        """
        for erroredTest in self.errors:
            self.outputFail({
                'suite': erroredTest.url,
                'test': 'Unexpected Error',
                'message': erroredTest.error
            }, erroredTest.url)

            if erroredTest.additionalInformation:
                self.outputAdditionalFailureInfo(erroredTest.additionalInformation)

            self.totalFailures += 1


class TAPReporter(TestReporter):
    """Lays some color on TAP results"""

    def __init__(self):
        TestReporter.__init__(self)

    def outputPass(self, case, url):
        CommandLine.green('ok {0} : {1}'.format(case['suite'], case['test']))

    def outputFail(self, case, url):
        CommandLine.red('not ok {0} : {1}'.format(case['suite'], case['test']))
        CommandLine.red('  ---')
        CommandLine.red(case['message'])
        CommandLine.red('  ...')

    def outputSummary(self, passes, failures):
        CommandLine.puts('Passes: {0}, Failures: {1}'.format(passes, failures))


class SpecReporter(TestReporter):
    """Spec-type output with checkmarks and xs"""

    def __init__(self):
        TestReporter.__init__(self)

        self.currentSuite = None
        self.encoding = sys.stdout.encoding

    def outputPass(self, case, url):
        self.__outputTestCase(case, self.__getPassMark(), 'green')

    def outputFail(self, case, url):
        self.__outputTestCase(case, self.__getFailMark(), 'red')
        CommandLine.red('  ' + case['message'])

    def outputAdditionalFailureInfo(self, info):
        CommandLine.red('  ' + info.pop(0))
        for line in info:
            CommandLine.red('    ' + line)

    def __outputTestCase(self, case, prefix, color):
        self.__outputSuite(case['suite'])
        func = getattr(CommandLine, color)
        if case.get('test'):
            func('  ' + prefix + ' ' + case['test'])
        else:
            func('  ' + prefix)

    def __outputSuite(self, name):
        if self.currentSuite != name:
            CommandLine.puts('')
            CommandLine.puts(name)
            self.currentSuite = name

    def outputSummary(self, passes, failures):
        CommandLine.puts('')
        CommandLine.puts('====================================')
        CommandLine.puts('Tests ran: {0}'.format(passes + failures))
        CommandLine.puts(u'{0} Failures:  {1}'.format(self.__getFailMark(), failures))
        CommandLine.puts(u'{0} Passes:    {1}'.format(self.__getPassMark(), passes))
        if failures == 0:
            CommandLine.green(u'Success! %s' % self.__getPassEmoji())
        else:
            CommandLine.red(u'Sad. %s' % self.__getFailEmoji())
        CommandLine.puts('====================================')
        CommandLine.puts('')

    def __getFailMark(self):
        return u'✘'if self.encoding is not None and self.encoding == 'UTF-8' and platform.system() != 'Windows' else 'x'

    def __getPassMark(self):
        return u'✔' if self.encoding is not None and self.encoding == 'UTF-8' and platform.system() != 'Windows' else '+'

    def __getFailEmoji(self):
        return u'💩' if self.encoding is not None and self.encoding == 'UTF-8' and platform.system() != 'Windows' else ':('

    def __getPassEmoji(self):
        return u'🐳' if self.encoding is not None and self.encoding == 'UTF-8' and platform.system() != 'Windows' else ':)'


class ArtifactReporter(TestReporter):
    def __init__(self):
        TestReporter.__init__(self)
        self.suites = []
        self.cases = []
        self.currentSuite = None
        self.currentSuiteHtml = None
        self.currentSuiteFailed = False

        self.templateDirectory = os.path.join(os.path.dirname(os.path.realpath(__file__)),  '..', 'templates') + os.path.sep
        self.caseTemplate = Template(codecs.open(self.templateDirectory + 'case.html', 'r', 'utf-8').read())
        self.suiteTemplate = Template(codecs.open(self.templateDirectory + 'suite.html', 'r', 'utf-8').read())
        self.template = Template(codecs.open(self.templateDirectory + 'output.html', 'r', 'utf-8').read())

    def outputPass(self, case, url):
        self.__outputTestCase(case, 'Passed')

    def outputFail(self, case, url):
        self.__outputTestCase(case, 'Failed')

    def __outputTestCase(self, case, status):
        self.__outputSuite(case['suite'], status)
        self.cases.append(self.caseTemplate.substitute({
            'case': case['test'],
            'status': status.lower(),
            'yml': cgi.escape(case['message']),
        }))

    def __processSuite(self):
        if self.currentSuite is not None:
            self.suites.append(self.suiteTemplate.substitute({
                'suite': self.currentSuite,
                'status': 'passed' if not self.currentSuiteFailed else 'failed',
                'cases': ''.join(self.cases)
            }))
            self.currentSuite = None

    def __outputSuite(self, name, status):
        if self.currentSuite != name:
            #The suite is changing, save off the last one
            if self.currentSuite is not None:
                self.__processSuite()

            self.currentSuiteFailed = False
            self.currentSuite = name
            self.cases = []

        if not self.currentSuiteFailed:
            self.currentSuiteFailed = (status == 'Failed')

    def outputAdditionalFailureInfo(self, info):
        return

    def __getContent(self, fileName):
        content = codecs.open(self.templateDirectory + fileName, 'r', 'utf-8').read()
        fileType = fileName[fileName.rfind('.') + 1:]
        if fileType == 'js':
            return u"<script>%s</script>" % content
        elif fileType == 'css':
            return u"<style>%s</style>" % content

    def outputSummary(self, passes, failures):
        self.__processSuite()

        status = 'Passed' if self.totalFailures is 0 else 'Failed'
        return self.template.substitute({
            'css': self.__getContent('output.css'),
            'js': self.__getContent('output.js'),
            'testName': self.testName,
            'passes': passes,
            'total': passes + failures,
            'failedTests': failures if failures is not 0 else '',
            'status': status,
            'cssStatus': status.lower(),
            'suites': ''.join(self.suites),
        })


class CodeSnifferReporter(object):
    def __init__(self):
        self.templateDirectory = os.path.join(os.path.dirname(os.path.realpath(__file__)),  '..', 'templates') + os.path.sep
        self.caseTemplate = Template(codecs.open(self.templateDirectory + 'csCase.html', 'r', 'utf-8').read())
        self.suiteTemplate = Template(codecs.open(self.templateDirectory + 'suite.html', 'r', 'utf-8').read())
        self.template = Template(codecs.open(self.templateDirectory + 'output.html', 'r', 'utf-8').read())

    def addTestResult(self, testResult):
        self.testResult = testResult

    def output(self):
        harness = codeSnifferHarness.CodeSnifferHarness(self.testResult.json)
        testResults = []
        for suite in [x for x in harness.suites if x]:
            testCaseFailures = [self.__processFail(y) for y in suite['case']] if suite['type'] == 'failed' else []
            testResults.append(self.__processSuite(suite, testCaseFailures))

        self.totalPasses = harness.passes
        self.totalFailures = harness.failures
        return self.outputSummary(testResults)

    def __processFail(self, failure):
        return self.caseTemplate.substitute({
            'status':  'fail',
            'message': failure['message'],
            'source':  failure['source'],
            'line':    failure['line'],
            'column':  failure['column']
        })

    def __processSuite(self, suite, failureList):
        return self.suiteTemplate.substitute({
            'suite':  suite['test'],
            'status': suite['type'],
            'cases':  ''.join(failureList)
        })

    def __getContent(self, fileName):
        content = codecs.open(self.templateDirectory + fileName, 'r', 'utf-8').read()
        fileType = fileName[fileName.rfind('.') + 1:]
        if fileType == 'js':
            return u"<script>%s</script>" % content
        elif fileType == 'css':
            return u"<style>%s</style>" % content

    def outputSummary(self, results):
        status = 'Passed' if self.totalFailures is 0 else 'Failed'
        return self.template.substitute({
            'css': self.__getContent('output.css'),
            'js': self.__getContent('output.js'),
            'testName': 'CodeSniffer',
            'passes': self.totalPasses,
            'total': self.totalPasses + self.totalFailures,
            'failedTests': self.totalFailures if self.totalFailures is not 0 else '',
            'status': status,
            'cssStatus': status.lower(),
            'suites': ''.join(results),
        })


class WCAGReporter(SpecReporter):
    """Spec-type output that has the ability to filter test results"""

    def __init__(self, filters):
        """Filters must be a non-empty array."""

        SpecReporter.__init__(self)

        self.filters = filters

    def outputPass(self, case, url):
        if self.__omitCase(case):
            self.totalPasses -= 1
        else:
            case['suite'] = url
            return SpecReporter.outputPass(self, case, url)

    def outputFail(self, case, url):
        if self.__omitCase(case):
            self.totalFailures -= 1
        else:
            case['suite'] = url
            return SpecReporter.outputFail(self, case, url)

    def __omitCase(self, case):
        matched = 0
        for filter in self.filters:
            if filter in case['raw']:
                matched += 1

        return matched != len(self.filters)
