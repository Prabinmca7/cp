#! /usr/bin/python

from lib.helpers import Helpers
from lib.commandLine import CommandLine
from runners.runner import TestRunner


class FunctionalRunner(TestRunner):
    def __init__(self, siteUrl, optionalSegment, options):
        self.url = siteUrl
        self.options = options
        self.optionalSegment = optionalSegment

    def run(self):
        subtype = self.options.get('subtype')
        if subtype == 'standard':
            testUrl = self.url + 'ci/unitTest/phpFunctional/test'
        elif subtype == 'widget':
            testUrl = self.url + 'ci/unitTest/widgetFunctional/test'
        elif subtype == 'slow':
            testUrl = self.url + 'ci/unitTest/phpFunctional/testSlow'

        if self.optionalSegment and not self.optionalSegment.startswith('/'):
            self.optionalSegment = '/' + self.optionalSegment

        testUrl += self.optionalSegment + '/reporter/TAP'

        CommandLine.say("Start test output")
        statusCode = Helpers.runTests(testUrl, self.options)
        CommandLine.say("End test output")

        return statusCode
