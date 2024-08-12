#! /usr/bin/python

import re
from runners.runner import TestException


class TapHarness(object):
    """
    Analyzes TAP output.
    Assumes that the string supplied in the constructor conforms
    to the TAP 13 spec, as CP uses it.
    see <http://podwiki.hexten.net/TAP/TAP13.html?page=TAP13>.
    1. The plan should appear as the last line.
    2. Failures should contain YAML that contains a `message` key.
    """

    def __init__(self, tapOutput):
        self.tapper = TAPMachine(tapOutput.split('\n'))

    def failures(self):
        return self.tapper.failures

    def passes(self):
        return self.tapper.passes

    def errors(self):
        return self.tapper.errors

    def test_cases(self):
        return self.tapper.run


class TAPMachine(object):
    """State machine for TAP results"""

    START = 0
    TESTS = 1
    END = 2

    def __init__(self, lines):
        self.current = TAPMachine.START
        self.lines = lines
        self.plan = None

        self.errors = []
        self.passes = 0
        self.failures = 0

        self.passRegex = re.compile(r'^ok (\d+) - (.*) : (.*)$', re.MULTILINE)
        self.failRegex = re.compile(r'^not ok (\d+) - (.*) : (.*)$', re.MULTILINE)
        self.genericFallbackRegex = re.compile(r'ok (\d+)? (.*)', re.MULTILINE)

    def run(self):
        while self.current is not TAPMachine.END:
            if self.current is TAPMachine.START:
                self.acceptVersion()
                self.acceptPlan()
            elif self.current is TAPMachine.TESTS:
                yield self.acceptTest()

    def acceptVersion(self):
        self.lines.pop(0)
        self.current = TAPMachine.TESTS

    def acceptPlan(self):
        if not self.plan and self.__atPlan():
            self.plan = self.lines.pop(0)

    def acceptTest(self):
        testCaseLines = self.__eatTestLines()
        currentTest = self.__processTest(testCaseLines)

        if currentTest is None:
            # Invalid TAP found.
            self.errors.append('\n'.join(testCaseLines))

        if self.__atPlan():
            self.acceptPlan()
            self.__endTest()
        elif self.__atEnd():
            self.__endTest()

        return currentTest

    def __endTest(self):
        self.current = TAPMachine.END

    def __eatTestLines(self):
        currentTest = [self.lines.pop(0)]
        while not self.__atNewTestLine() and not (self.__atPlan() or self.__atEnd() or self.__atComment()):
            currentTest.append(self.lines.pop(0))
        return currentTest

    def __processTest(self, test):
        if test[0].find('ok') is 0:
            return self.__processPass(test)
        elif test[0].find('not ok') is 0:
            return self.__processFail(test)

    def __atEnd(self):
        return len(self.lines) == 1

    def __atPlan(self):
        return self.lines[0].find('1..') is 0

    def __atComment(self):
        return self.lines[0].find('#') is 0

    def __atNewTestLine(self):
        try:
            line = self.lines[0]
            return line.find('ok') is 0 or line.find('not ok') is 0
        except IndexError:
            self.failures += 1
            raise TestException("Failed to retrieve valid TAP output")

    def __processPass(self, test):
        self.passes += 1
        return self.__produceTestResult(test, self.passRegex, 'pass')

    def __processFail(self, test):
        self.failures += 1
        return self.__produceTestResult(test, self.failRegex, 'fail')

    def __produceTestResult(self, test, regex, type):
        strVal = "\n".join(test).strip()
        if '  ---' in test:
            yaml = test[test.index('  ---') + 1:test.index('  ...')]
        else:
            yaml = ''
        match = re.search(regex, strVal)
        fallbackMatch = None if match else re.search(self.genericFallbackRegex, strVal)

        return {
            'raw':   strVal,
            'type':  type,
            'count': fallbackMatch.group(1) if not match else match.group(1),
            'suite': '' if not match else match.group(2),
            'test':  fallbackMatch.group(2) if not match else match.group(3),
            'message': "\n".join(yaml),
        }
