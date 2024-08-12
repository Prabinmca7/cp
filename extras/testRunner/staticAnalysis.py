#! /usr/bin/python

import optparse
import sys
import os
import re
import codecs

from lib import reporters
from lib.commandLine import CommandLine
from lib.helpers import Helpers
from runners.runner import TestException
from runners.runner import JsonTestResult
from runners.runner import TestResult


class StaticAnalyzer(object):
    def __init__(self, sourcePath, subPath, options):
        self.sourcePath = sourcePath
        self.subPath = subPath
        self.options = options

    def runCommand(self, command):
        lines = []
        for line in Helpers.runCommand(command):
            if line != '':
                lines.append(line)
        return lines


class PHPCodeSniffer(StaticAnalyzer):
    codeSnifferRunnerScript = '/nfs/project/cp/PHPCodeSniffer/current/scripts/phpcs'
    phpBinary = '/nfs/local/linux/phpDocumentor/current/bin/php'

    def run(self):
        processCommand = self.__constructCommand()
        lines = self.runCommand(processCommand)

        self.__writeArtifact()

        return self._outputCodeSnifferResultsAndGetStatusCode(lines)

    def __writeArtifact(self):
        """Takes the JSON that PHPCodeSniffer wrote to the HTML artifact file and converts it to HTML"""
        htmlArtifactFile = self.options.get('htmlArtifact')
        if htmlArtifactFile:
            reporter = reporters.newReporter('CodeSniffer')
            try:
                result = JsonTestResult(htmlArtifactFile)
                reporter.addTestResult(result)
                htmlResults = reporter.output()
                Helpers.safeRemove(htmlArtifactFile)
                artifactHandle = codecs.open(htmlArtifactFile, 'a', 'utf-8')
                artifactHandle.write(htmlResults)
                CommandLine.say("HTML Artifact written to %s" % htmlArtifactFile)
            except Exception as e:
                CommandLine.say(e)

    def __constructCommand(self):
        processCommand = [PHPCodeSniffer.phpBinary, PHPCodeSniffer.codeSnifferRunnerScript, '--report-full', '-s', '--standard=' + self.sourcePath + 'extras/codeSniffer/CPStandard']
        htmlArtifactFile = self.options.get('htmlArtifact')

        if self.options.get('verbose'):
            processCommand.append('-v')
        if htmlArtifactFile:
            processCommand.append('--report-json=' + htmlArtifactFile)
        if self.options.get('xmlArtifact'):
            processCommand.append('--report-xml=' + self.options.get('xmlArtifact'))

        processCommand.append(self.sourcePath + self.subPath)

        return processCommand

    def _extractTotalFailures(self, outputLines):
        failureMatch = re.compile(r'\d \| (ERROR|WARNING) \|')
        return len(filter(lambda line:failureMatch.search(line) is not None, outputLines))

    def _outputCodeSnifferResultsAndGetStatusCode(self, outputLines):
        returnCode = 0
        if len(outputLines) > 0:
            if self.options.get('verbose'):
                for line in outputLines:
                    print(line)
                    if returnCode is 0 and line.startswith('Processing ') and not '0 errors, 0 warnings' in line:
                        returnCode = 1
            else:
                print(''.join(outputLines))
                returnCode = 1

        print('Passes: {0}, Failures: {1}'.format(1 if len(outputLines) == 0 else 0, self._extractTotalFailures(outputLines) if len(outputLines) > 0 else 0))

        return returnCode


class SCSSLinter(StaticAnalyzer):
    def run(self):
        command = self.__constructCommand()
        output = self.runCommand(command)

        reporter = reporters.newReporter(self.options.get('cliReporter'))
        result = TestResult(None, output)
        reporter.addTestResult(result)
        reporter.output()

        self.__writeArtifact(result)

        return 0 if reporter.failures() is 0 else 2

    def runCommand(self, command):
        errors = []
        for line in Helpers.runCommand(command, True):
            if line != '':
                errors.append(self.__convertSCSSLinterLineToTAP(line, len(errors) + 1))

        return self.__convertSCSSOutputToTAP(errors)

    def __writeArtifact(self, result):
        artifactHandle = Helpers.createArtifactFile(self.options.get('htmlArtifact'))
        if artifactHandle:
            artifactReporter = reporters.newReporter('Artifact')
            artifactReporter.addTestResult(result)
            artifactHandle.write(artifactReporter.output())
            CommandLine.say("HTML Artifact written to %s" % self.options.get('htmlArtifact'))

    def __constructCommand(self):
        command = [
            'export JAVA_HOME=/nfs/local/linux/jdk/1.7/current;',
            'export GEM_PATH=/nfs/project/cp/gems/1.9.3p392;',
            '/nfs/project/cp/bin/jruby',
            '-S',
            '/nfs/project/cp/bin/scss-lint',
            u'{0}webfiles/assets/themes/standard/**/*.scss'.format(self.sourcePath),
            u'{0}webfiles/assets/themes/mobile/**/*.scss'.format(self.sourcePath),
        ]
        return ' '.join(command)

    def __convertSCSSLinterLineToTAP(self, line, count):
        return u'not ok {0} - {1}'.format(count, line)

    def __convertSCSSOutputToTAP(self, output):
        if len(output) > 0:
            output.insert(0, 'TAP Version 13\n')
            output.append('1..{0}'.format(len(output)))
        else:
            output = ["TAP Version 13\n", "ok 1 All tests passed\n", "1..1"]

        return ''.join(output)


def getTestTypes(testName=None):
    types = {
        'codeSniffer': {
            'runner': PHPCodeSniffer,
        },
        'scssLinter': {
            'runner': SCSSLinter,
        }
    }

    return types.get(testName) if testName else types

def parseArgs():
    testTypes = getTestTypes()
    types = testTypes.keys()

    parser = optparse.OptionParser(
        usage="usage: %prog [options] optional/subdir/to/scan. Use the --help flag for additional information.",
        description="""
A convenient script to automatically execute the Customer Portal Static Analysis Unit tests. This script can be used to run the %s unit tests in CP.
By default this will scan your entire code tree, but you can optionally pass in a path to a specific subdirectory or file that is relative to
the /scripts/cp base directory to only scan a portion of your code tree.

Example Call: `staticAnalysis.py --type=codeSniffer core/framework/Utils`
""" % types
    )

    #General testing arguments
    parser.add_option('-v', action="store_true", dest="verbose", help='Print verbose CodeSniffer output')
    parser.add_option('--type', type='choice', choices=types, help='The type of test to run. One of: %s.' % types)
    parser.add_option('--htmlArtifact', help='An optional HTML artifact path to store the test results. If the path points to a directory, the file will default to `cp-<type>-results.html`. If the path points to a file, it will be created at that location.')
    parser.add_option('--xmlArtifact', help='An optional XML artifact path to store the test results. If the path points to a directory, the file will default to `cp-<type>-results.xml`. If the path points to a file, it will be created at that location.')
    parser.add_option('--cliReporter', type='choice', choices=reporters.Reporters, help='Test reporter used to display results to the command line. Defaults to ' + reporters.DefaultReporter)

    (options, args) = parser.parse_args()

    return (options.__dict__, args)


def validatePaths(sourcePath, subPath):
    # Do a quick smoke test against the CP directory
    children = os.listdir(sourcePath)
    if not 'customer' in children or not 'core' in children or not 'webfiles' in children:
        raise TestException("The source directory (%s) doesn't contain the expected CP directories customer, core and webfiles." % sourcePath)

    fullSubPath = sourcePath + subPath
    if subPath and not os.path.exists(fullSubPath):
        raise TestException("The sub directory specified (%s), doesn't seem to exist." % fullSubPath)

def validateOptions(options):
    # Check the type and subtypes
    types = getTestTypes()
    testName = options['type']
    testType = types.get(testName)
    if not testType:
        raise TestException("A test type is required. The valid types are %s" % ', '.join(types.keys()))

    # Create and check the artifact paths
    for artifactType, artifact in (('xml', 'xmlArtifact'), ('html', 'htmlArtifact')):
        if options.get(artifact):
            options[artifact] = Helpers.adjustArtifactPath(options[artifact], artifactType, options['type'])

def main():
    (options, args) = parseArgs()

    scriptDir = Helpers.getScriptDirectory()
    sourcePath = os.path.join(scriptDir[:scriptDir.index(os.sep + 'cp' + os.sep)], 'cp') + os.sep
    subPath = '' if len(args) == 0 else args[0]

    try:
        validatePaths(sourcePath, subPath)
    except TestException as e:
        CommandLine.say(e, 'error')
        sys.exit(2)

    try:
        validateOptions(options)
    except TestException as e:
        CommandLine.say(e, 'error')
        sys.exit(2)

    try:
        analyzer = getTestTypes(options['type']).get('runner')(sourcePath, subPath, options)
        statusCodeFirst = analyzer.run()

        statusCodeSecond = 0

        # codeSniffer tests implicitly run scssLinter tests, too
        if options['type'] == 'codeSniffer':
            options['type'] = 'scssLinter'
            analyzer = getTestTypes(options['type']).get('runner')(sourcePath, subPath, options)
            statusCodeSecond = analyzer.run()

        if statusCodeFirst is 0 and statusCodeSecond is 0:
            statusCode = 0
            CommandLine.say("Testing completed successfully")
        else:
            statusCode = 1
            CommandLine.say("Testing Failed")
        sys.exit(statusCode)
    except TestException as e:
        CommandLine.say(e, 'error')
        sys.exit(2)


if __name__ == '__main__':
    main()
