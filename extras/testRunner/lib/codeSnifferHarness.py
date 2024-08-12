#! /usr/bin/python

import json
import re


class CodeSnifferHarness(object):
    """
    Analyzes CodeSniffer JSON output.
    Assumes that the file supplied is path to file containing CodeSniffer standard JSON result structure
    """

    def __init__(self, jsonFile):
        self.jsonFile = jsonFile
        self.passes = 0
        self.failures = 0

        with open(self.jsonFile, "r") as outputFile:
            self.jsonOutput = self.normalizeJSON(outputFile.read())
        self.parseTestOutput(self.jsonOutput)

    def normalizeJSON(self, json):
        """
        Fix up the loosy-goose JSON that the codeSniffer php script emits.
        """
        json = json.replace('\n', '')
        # Any php namespace slashes in error messages aren't properly escaped.
        json = re.sub(r"\\([A-Za-z])", r"\\\\\1", json)
        return json

    def parseTestOutput(self, jsonOutput):
        testResults = json.loads(jsonOutput)
        self.failures = testResults['totals']['errors'] + testResults['totals']['errors']
        self.suites = []
        for testFile, testResults in testResults['files'].iteritems():
            self.suites.append(self.getTestSuiteDetails(testFile, testResults))

    def getTestSuiteDetails(self, testFile, testResults):
        if testResults['errors'] == 0 and testResults['warnings'] == 0:
            self.passes += 1
            type = 'passed'
        else:
            self.failures += 1
            type = 'failed'

        return {
            'test': testFile,
            'type': type,
            'case': testResults['messages']
        }

    def failures(self):
        return self.failures

    def passes(self):
        return self.passes

    def suites(self):
        return self.suites
