#! /usr/bin/python

DESCRIPTION = """
Runs WCAG (Web Content Accessibility Guidelines) validation against specified
pages using HTML_CodeSniffer (https://github.com/squizlabs/HTML_CodeSniffer)
"""

# standard Python modules
import os
import optparse
import sys
import subprocess
import threading
import Queue
import multiprocessing
import time
import pprint
import socket

# custom modules
from lib.helpers import Helpers
from lib import reporters
from runners.runner import TestResult


def parseArgs():
    """Parse command line arguments and return a tuple of options and args."""

    parser = optparse.OptionParser(
        usage="usage: %prog [options] interface-name",
        description=DESCRIPTION,
    )
    parser.add_option('--includePasses',
                      type='choice',
                      choices=['true', 'false'],
                      help='Whether or not to display passes in the command line output. Defaults to false')
    parser.add_option('--server',
                      help='A hostname to run the tests against. Defaults to the local hostname.')
    parser.add_option('--standard',
                      help='The WCAG standard to validate against (WCAG2A, WCAG2AA, WCAG2AAA). Defaults to WCAG2AA.')
    parser.add_option('--artifact',
                      help='An artifact file to store the test results. Defaults to a file named cp-results.xml in an artifact directory in the current working directory.')
    parser.add_option('--pages',
                      help='A comma separated list of pages to validate (e.g. "app, app/answers/list, ci/admin/overview")')
    parser.add_option('--selector',
                      help='A DOM selector to limit testing against (e.g. HEADER > NAV). Tag names should be uppercase.')
    parser.add_option('--filter',
                      help='A WCAG standard to limit testing against (e.g. WCAG2AA.Principle1.Guideline1_4.1_4_3.G18.Fail will limit testing to only check color contrast ratios)')
    (options, args) = parser.parse_args()
    if not len(args) or not args[0]:
        print "Error: An interface name is required."
        sys.exit(-1)

    options = addDefaultOptions(options.__dict__)
    return (options, args)


def addDefaultOptions(options):
    """Add default options"""

    scriptDir = Helpers.getScriptDirectory()
    defaults = {
        'artifact': os.path.join(scriptDir, 'artifacts', 'cp-wcag-results.xml'),
        'type': 'enduser',
        'server': socket.gethostname(),
        'standard': 'WCAG2AA',
        'includePasses': False,
    }

    for key, value in options.items():
        if value is not None:
            defaults[key] = value
    return defaults


def runProcess(args, cwd=None):
    """Run the process defined by args"""

    child = subprocess.Popen(args, cwd=cwd, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
    while True:
        retcode = child.poll()
        line = child.stdout.readline()
        yield line
        if retcode is not None:
            break


def urlWorkerThread(queuedUrls, phantomPath, options, results):
    """Process queued threads"""

    cwd = '/nfs/project/cp/HTML_CodeSniffer_TAP/PhantomJS'
    while True:
        url = queuedUrls.get()
        lines = []
        for line in runProcess([phantomPath, '--ignore-ssl-errors=yes', 'HTMLCS_Run.js', url, options.get('standard')], cwd):
            if line:
                lines.append(line)

        results.put((lines, url))
        queuedUrls.task_done()


def validatePages(baseUrl, pages, options):
    """Validate pages and return a dictionary of results."""

    phantomPath = Helpers.which('phantomjs')
    if not phantomPath:
        phantomPath = '/nfs/project/cp/bin/phantomjs'

    #Queue up all of the work
    workQueue = Queue.Queue()
    for url in pages:
        workQueue.put(baseUrl + url)

    maxThreads = workQueue.qsize()
    resultQueue = Queue.Queue()
    for i in range(maxThreads):
        t = threading.Thread(target=urlWorkerThread, args=(workQueue, phantomPath, options, resultQueue))
        t.daemon = True
        t.start()

    workQueue.join()

    reporter = reporters.WCAGReporter(filter(None, [
        options.get('selector'),
        options.get('filter'),
    ]))

    for (lines, url) in list(resultQueue.queue):
        reporter.addTestResult(TestResult(url, ''.join(lines)))

    reporter.output(not options.get('includePasses'))
    return reporter.errors() is 0


def elapsed(startTime, precision=2):
    """Return time elapsed between startTime and now"""
    return str(round(time.time() - startTime, precision))


def normalizePageInput(pages):
    """Split comma separated pages string and return in a list"""
    return [x.strip() for x in pages.split(',')] if pages is not None else ['']


def main():
    """Commence validation"""

    (options, args) = parseArgs()

    startTime = time.time()
    baseUrl = 'http://%s.%s/' % (args[0], options['server'])
    statusCode = 0
    pages = validatePages(options['pages']) if 'pages' in options else '/'
    #TODO: allow specifying pages and other options via a YML file
    #TODO: add ability to bypass login required pages
    #TODO: allow for dev-mode testing
    #TODO: get artifacts working with filters and save artifacts

    print('\nExamining specified pages for site %(baseUrl)s\n' % locals())
    passed = validatePages(baseUrl, pages, options)
    print 'TOTAL TIME: ' + elapsed(startTime)
    sys.exit(0 if passed is True else -1)

if __name__ == '__main__':
    main()
