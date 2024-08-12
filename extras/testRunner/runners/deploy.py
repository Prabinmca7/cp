#! /usr/bin/python

from lib.helpers import Helpers
from lib.commandLine import CommandLine
from runners.runner import TestRunner


class DeployRunner(TestRunner):
    def __init__(self, siteUrl, optionalSegment, options):
        self.url = siteUrl
        self.options = options
        self.optionalSegment = optionalSegment

    def run(self):
        testUrl = self.url + 'ci/unitTest/rendering/deploy/' + self.optionalSegment.strip('/')

        if not self.optionalSegment:
            CommandLine.say("Deploying all tests. Note an optional second parameter can be used to filter tests e.g. `widgets/standard/path/to/widget` or `views/tests/path/to/test`")
        else:
            CommandLine.say("Deploying unit tests")

        statusCode = Helpers.runPage(testUrl, 'DEPLOY FAILED', self.options.get('htmlArtifact'))
        CommandLine.say("Done deploying unit tests")
        CommandLine.say('Passes: {0}, Failures: {1}'.format(1 if statusCode == 0 else 0, 0 if statusCode == 0 else 1))
        return statusCode
