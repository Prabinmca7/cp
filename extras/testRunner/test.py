#! /usr/bin/python

import optparse
import sys
import socket
import os

from lib import reporters
from lib.commandLine import CommandLine
from lib.helpers import Helpers
from runners.javascript import JavascriptRunner
from runners.functional import FunctionalRunner
from runners.rendering import RenderingRunner
from runners.deploy import DeployRunner
from runners.build import BuildRunner
from runners.runner import TestException


def getTestTypes(testName=None):
    types = {
        'javascript': {
            'runner': JavascriptRunner,
            'subtypes': ['core', 'widget'],
        },
        'functional': {
            'runner': FunctionalRunner,
            'subtypes': ['standard', 'widget', 'slow'],
        },
        'rendering': {
            'runner': RenderingRunner,
        },
        'deploy': {
            'runner': DeployRunner,
        },
        'build': {
            'runner': BuildRunner,
        },
    }

    return types.get(testName) if testName else types


def parseArgs():
    testTypes = getTestTypes()
    types = testTypes.keys()

    subtypeHelp = 'The subtype of tests to execute. '
    for key, subtypes in [(x, testTypes[x].get('subtypes')) for x in testTypes if testTypes[x].get('subtypes')]:
        subtypeHelp += " for '%s' one of %s," % (key, subtypes)

    parser = optparse.OptionParser(
        usage="usage: %prog [options] interface-name optional/path/to/tests. Use the --help flag for additional information.",
        description="""
A convenient script to automatically execute the Customer Portal Unit tests. This script can be used to run the %s unit tests in CP.
All of the tests expect a complete development CP site to be created at the given interface. The rendering tests require the site to be deployed.
The JavaScript tests require either PhantomJS (a headless browser) or the Selenium Web Driver module (to automate browsers on the machine running the script). When
using Selenium, the appropriate driver executables must be in the PATH.

Example Call: `test.py --type=javascript --subtype=widget liventrunk input/TextInput`
""" % types
    )

    #General testing arguments
    parser.add_option('--server', help='A hostname to run the tests against. Defaults to the local hostname.')
    parser.add_option('--htmlArtifact', help='An optional HTML artifact path to store the test results. If the path points to a directory, the file will default to `cp-<test_type>-<sub_type>-results.html`. If the path points to a file, it will be created at that location.')
    parser.add_option('--xmlArtifact', help='An optional XML artifact path to store the test results. If the path points to a directory, the file will default to `cp-<test_type>-<sub_type>-results.xml`. If the path points to a file, it will be created at that location.')
    parser.add_option('--cliReporter', type='choice', choices=reporters.Reporters, help='Test reporter used to display results to the command line. Defaults to ' + reporters.DefaultReporter)
    parser.add_option('--includePasses', type='choice', choices=['true', 'false'], help='Whether or not to display passes in the command line output. Defaults to false')
    parser.add_option('--type', type='choice', choices=types, help='The type of test to run. One of: %s.' % types)
    parser.add_option('--subtype', help=subtypeHelp)
    parser.add_option('--deploy', type='choice', choices=['true', 'false'], help='This option only applies to rendering and javascript tests. Rendering tests require a deploy, but a prior deploy can be used. Javascript tests can optionally be run without deploy.')
    parser.add_option('--module', type='choice', choices=['cp','okcs'], help='Use this optional flag to include or exclude okcs tests. This flag can be used for PHP, JavaScript, and Rendering tests. The options for this flag are mutually exclusive. If an option is not specified for this flag all the tests in a suite will be executed.')

    jsGroup = optparse.OptionGroup(parser, "'javascript' type options", "Options specific to 'javascript' tests.")
    jsGroup.add_option('--source', help='The root of the source tree where tests are located. For example: ~user.name/code/trunk. By default the script attempts to locate the test files relative to this test script directory.')
    jsGroup.add_option('--browser', type='choice', choices=['ie', 'firefox', 'chrome', 'phantom'], help='The name of the browser to test. Defaults to phantom. Other browsers require Selenium.')
    jsGroup.add_option('--skipGenerate', type='choice', choices=['true', 'false'], help='Use this option to skip the generation of test files. Generates files by default. When set to true, the script will use the last generated test files.')
    parser.add_option_group(jsGroup)

    buildGroup = optparse.OptionGroup(parser, "'build' type options", "Options specific to 'build' tests.")
    buildGroup.add_option('--target', help="The target directory where CP will be built. If the directory does not exist, CP will be cloned followed by 'rake all'. Defaults to ~/tmp/cp", default='~/tmp/cp')
    buildGroup.add_option('--branch', help="The target branch to checkout (e.g. rnw-14-8-fixes). Defaults to master", default='master')
    buildGroup.add_option('--forceBuild', help="Forces running 'rake all', even if TARGET directory already exists", action='store_true', default=False)
    buildGroup.add_option('--buildNum', help='The build number to pass to the rake task', type='int', default=0)
    buildGroup.add_option('--cxBuildNum', help='The CX build number to pass to the rake task', type='int', default=0)
    buildGroup.add_option('--development', help="Runs rake in development mode to manually copy the current code base rather than pulling it from orahub", action='store_true', default=False)
    parser.add_option_group(buildGroup)

    (options, args) = parser.parse_args()

    if not len(args) or not args[0]:
        CommandLine.say("An interface name is required", 'error')
        parser.print_usage()
        sys.exit(2)

    options = addDefaultOptions(options.__dict__)

    try:
        validateOptions(options)
    except TestException as e:
        CommandLine.say(e, 'error')
        sys.exit(2)

    return (options, args)


def validateOptions(options):
    # Do a quick smoke test against the CP directory
    if options['source']:
        children = os.listdir(options['source'])
        if not 'customer' in children or not 'core' in children or not 'webfiles' in children:
            raise TestException("The source directory doesn't contain the expected CP directories customers, core and webfiles.")

    # Check the type and subtypes
    types = getTestTypes()
    testName = options.get('type', '')
    testType = types.get(testName)
    if not testType:
        raise TestException("A test type is required. The valid types are %s" % ', '.join(types.keys()))

    subtypes = testType.get('subtypes', [])
    subtype = options.get('subtype')
    if not subtypes and subtype:
        raise TestException('No subtypes available for ' + testName)

    if subtypes and subtype not in subtypes:
        subTypePhrase = 'Valid subtype is' if len(subtypes) == 1 else 'Valid subtypes are'
        subtypes = ', '.join(subtypes) if subtypes else 'None'
        raise TestException("A test subtype is required. %s %s" % (subTypePhrase, subtypes))

    # Create and check the artifact paths
    for artifactType, artifact in (('xml', 'xmlArtifact'), ('html', 'htmlArtifact')):
        if options.get(artifact):
            options[artifact] = Helpers.adjustArtifactPath(options[artifact], artifactType, options.get('type'), options.get('subtype'))


def addDefaultOptions(options):
    scriptDir = Helpers.getScriptDirectory()

    defaults = {
        'server': socket.gethostname(),
        'includePasses': False,
        'source': False if options['type'] != 'javascript' else os.path.join(scriptDir[:scriptDir.index(os.sep + 'cp' + os.sep)], 'cp'),
        'browser': 'phantom',
        #Default deploy to true for rendering tests, false for everything else.
        'deploy': False if options['type'] != 'rendering' else True,
        'skipGenerate': False
    }

    for key, value in options.items():
        if value is not None:
            if value == 'true':
                defaults[key] = True
            elif value == 'false':
                defaults[key] = False
            else:
                defaults[key] = value

    return defaults


def getSiteUrl(site, server):
    server = server.strip('/').strip('.')
    return 'http://%s.%s/' % (site, server)


def main():
    (options, args) = parseArgs()

    siteUrl = getSiteUrl(args[0], options['server'])

    try:
        runner = getTestTypes(options['type']).get('runner')(siteUrl, (args[1] if len(args) > 1 else ''), options)

        statusCode = runner.run()
        if statusCode is 0:
            CommandLine.say("Testing completed successfully")
        else:
            CommandLine.say("Testing Failed")
        sys.exit(statusCode)
    except TestException as e:
        CommandLine.say(e, 'error')
        sys.exit(2)


if __name__ == '__main__':
    main()
