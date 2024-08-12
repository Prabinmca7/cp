#! /usr/bin/python

from lib.helpers import Helpers
from lib.commandLine import CommandLine
from runners.runner import TestRunner


class RenderingRunner(TestRunner):
    def __init__(self, siteUrl, optionalSegment, options):
        self.url = siteUrl
        self.options = options
        self.optionalSegment = optionalSegment.strip('/')

    def run(self):
        if self.options['deploy']:
            CommandLine.say("Deploying unit tests")
            statusCode = Helpers.runPage(self.url + 'ci/unitTest/rendering/deploy/' + self.optionalSegment, 'DEPLOY FAILED')
            if statusCode is not 0:
                CommandLine.say("Deploy failed")
                return statusCode

            CommandLine.say("Done deploying unit tests")
        else:
            CommandLine.say("Skipping Deploy. The Last deployed pages will be used")

        statusCodes = []
        subTestPathGroup1 = ['subTestPath1', 'subTestPath2', 'subTestPath3', 'subTestPath4', 'subTestPath5', 'subTestPath6', 'subTestPath7']
        subTestPathGroup2 = ['subTestPath9', 'subTestPath10', 'subTestPath11', 'subTestPath12', 'subTestPath13', 'subTestPath14', 'subTestPath15']
        if self.options.get('module') is not None:
            if self.options['module'] == 'cp':
                subTestPaths = subTestPathGroup1 + subTestPathGroup2
            elif self.options['module'] == 'okcs':
                subTestPaths = ['subTestPath8']
        else:
            subTestPaths = subTestPathGroup1
            subTestPaths.append('subTestPath8')
            subTestPaths += subTestPathGroup2

        for subTestPath in subTestPaths:
            testUrl = self.url + 'ci/unitTest/rendering/test/subTestPaths/' + subTestPath + '/saveWidgetOutput/reporter/TAP/' + self.optionalSegment.strip('/')
            CommandLine.say('Start test output (' + subTestPath + ')')
            statusCodes.append(Helpers.runTests(testUrl, self.options))
            CommandLine.say('End test output (' + subTestPath + ')')

        testUrl = self.url + 'ci/unitTest/rendering/test/saveWidgetOutput/reporter/TAP/' + self.optionalSegment.strip('/')
        CommandLine.say("Start test output (normal)")
        statusCodeNormal = Helpers.runTests(testUrl, self.options)
        CommandLine.say("End test output (normal)")

        for statusCode in statusCodes:
            if statusCode == 1:
                return statusCode

        return statusCodeNormal
