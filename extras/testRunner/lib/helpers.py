import os
import errno
import urllib2
import base64
import cgi
import StringIO
import gzip
import httplib
import fnmatch
import codecs
import subprocess
import socket

from lib.commandLine import CommandLine
from lib import reporters
from runners.runner import TestResult


class Helpers:
    # The number of seconds to wait for individual test URLs
    TEST_TIMEOUT = 30

    # The maximum request duration for any request. Requests which exceed this duration will run into PHP timeout problems.
    MAX_TIMEOUT = 1200

    # Recursively find all the files in a directory and return those that match the supplied pattern
    @staticmethod
    def getFilesRecursively(path, pattern):
        files = []
        for root, dirnames, filenames in os.walk(path):
            for filename in fnmatch.filter(filenames, pattern):
                files.append(os.path.join(root, filename))
        return files

    #Remove a file. If the file does not exist, continue without exception
    @staticmethod
    def safeRemove(path):
        try:
            os.remove(path)
        except OSError as exception:
            if exception.errno != errno.ENOENT:
                raise

    #Recursively add a directory, if the directory already exists continue without exception
    @staticmethod
    def safeMakedirs(path):
        try:
            os.makedirs(path)
        except OSError as exception:
            if exception.errno != errno.EEXIST:
                raise

    #Check for an executable in the path
    @staticmethod
    def which(program):
        def is_exe(fpath):
            return os.path.isfile(fpath) and os.access(fpath, os.X_OK)

        fpath, fname = os.path.split(program)
        if fpath:
            if is_exe(program):
                return program
        else:
            for path in os.environ["PATH"].split(os.pathsep):
                path = path.strip('"')
                exe_file = os.path.join(path, program)
                if is_exe(exe_file):
                    return exe_file
        return None

    @staticmethod
    def getScriptDirectory():
        return os.path.dirname(os.path.realpath(__file__))

    @staticmethod
    def getResponse(response):
        if response.info().get('Content-Encoding') == 'gzip':
            ungzippedContent = gzip.GzipFile(fileobj=StringIO.StringIO(response.read()))
            return unicode(ungzippedContent.read(), 'utf-8')
        else:
            try:
                return unicode(response.read(), 'utf-8')
            except httplib.IncompleteRead, e:
                # The socket that was being communicated on was abruptly closed by the server.
                # This typically happens when PHP reaches a 15 minute timeout. If this happens, grab whatever content
                # was received before that limit. The tests will still fail, but output will be produced.
                return unicode(e.partial, 'utf-8')

    #Hit the URL and get the response or wait `timeout` seconds before throwing an error
    @staticmethod
    def retrieveUrl(url, timeout=None):
        timeout = Helpers.TEST_TIMEOUT if timeout is None else timeout
        CommandLine.say("Timeout = '%s'" % timeout)
        request = urllib2.Request(url)

        #Without these headers (and without PHP specifying an exact content-length) Apache falls back to using
        #chunked transfer encoding. That would work fine if urllib2 supported a persistent connection, but it doesn't,
        #so the first chunk is transmitted and the connection is closed, leaving us stuck without all of the information.
        #Adding this header causes the response to be compressed, giving a correct content length and avoiding chunked encoding.
        request.add_header('Accept-encoding', 'gzip')
        request.add_header('Accept-Language', '*')
        request.add_header('Authorization', ('Basic %s' % base64.encodestring('admin:')).rstrip('\n'))

        requestFailed = True
        headers = body = errorMessage = None
        try:
            response = urllib2.urlopen(request, timeout=timeout)
            requestFailed = not (response.getcode() == 200)
            headers = response.info()
            body = Helpers.getResponse(response)
            if not body:
                requestFailed = True
                errorMessage = 'The URL did not produce a response. HTTP Status Code: %s' % (response.getcode())
        except urllib2.HTTPError as e:
            headers = e.info()
            body = Helpers.getResponse(e)
            errorMessage = 'There was an error with the request. HTTP Status Code: %s' % (e.code)
        except urllib2.URLError as e:
            errorMessage = 'The tests timed out after %s seconds. Exception message: %s ' % (timeout, e.reason)
        return (body if body else 'No Body', headers, requestFailed, errorMessage)

    #Executes the given command and returns results
    @staticmethod
    def runCommand(command, shell=False):
        child = subprocess.Popen(command, shell=shell, stdout=subprocess.PIPE, stderr=subprocess.STDOUT)
        while True:
            returnValue = child.poll()
            yield child.stdout.readline()
            if returnValue is not None:
                break

    @staticmethod
    def getDefaultOutput():
        return u"""
    <html>
        <body>
            <h1>Unit Tests Failed. The request did not complete successfully.</h1>
            <h2>URL:</h2>
            <p>{0}</p>
            <h2>Headers:</h2>
            <pre>{1}</pre>
            <h2>Body:</h2>
            <pre>{2}</pre>
            <h2>Error Message:</h2>
            <p>{3}</p>
        </body>
    </html>
    """

    @staticmethod
    def createArtifactFile(artifact):
        if artifact:
            Helpers.safeMakedirs(os.path.dirname(artifact))
            #Helpers.safeRemove(artifact)
            return codecs.open(artifact, 'a', 'utf-8')

    #Run a non-tap endpoint and fail if the endpoint contains the `failureString`
    @staticmethod
    def runPage(url, failureString, artifactPath=None):
        artifactHandle = Helpers.createArtifactFile(artifactPath)

        CommandLine.say("Running URL `%s`" % url)

        (body, headers, hasFailed, errorMessage) = Helpers.retrieveUrl(url, Helpers.MAX_TIMEOUT)

        if not hasFailed:
            CommandLine.say(body)

            if artifactHandle:
                artifactHandle.write(body)
                CommandLine.say("HTML Artifact written to %s" % artifactPath)

            hasFailed = body.find(failureString) != -1
        else:
            CommandLine.say("The request failed.")
            Helpers.writeErrorOutput(artifactHandle, url, headers, body, errorMessage)

        return 1 if hasFailed else 0

    #Run a URL that generates TAP output and display the results
    @staticmethod
    def runTests(url, options):
        artifactHandle = Helpers.createArtifactFile(options.get('htmlArtifact'))
        module = options.get('module')
        if module:
            url += '?module=' + module

        CommandLine.say("Running tests at `%s`" % url)

        (body, headers, hasFailed, errorMessage) = Helpers.retrieveUrl(url, Helpers.MAX_TIMEOUT)

        if not hasFailed:
            reporter = reporters.newReporter(options.get('cliReporter'))

            try:
                result = TestResult(url, body)
                reporter.addTestResult(result)
                reporter.output(not options.get('includePasses'))
                hasFailed = reporter.failures() is not 0

                if artifactHandle:
                    artifactReporter = reporters.newReporter('Artifact')
                    artifactReporter.addTestResult(result)
                    artifactHandle.write(artifactReporter.output())
                    CommandLine.say("HTML Artifact written to %s" % options.get('htmlArtifact'))

            except Exception as e:
                hasFailed = True
                CommandLine.say(e)
                Helpers.writeErrorOutput(artifactHandle, url, headers, body, errorMessage)

        else:
            CommandLine.say("The request failed.")
            Helpers.writeErrorOutput(artifactHandle, url, headers, body, errorMessage)

        return 1 if hasFailed else 0

    @staticmethod
    def writeErrorOutput(artifactFile, url, headers, body, errorMessage):
        if artifactFile:
            body = cgi.escape(body)
            artifactFile.write(Helpers.getDefaultOutput().format(url, headers, body, errorMessage))
            CommandLine.say("Check the artifact file")
        else:
            CommandLine.say("Run the command again with the `htmlArtifact` flag to help with debugging")

    @staticmethod
    def adjustArtifactPath(artifactPath, artifactType, testType, testSubtype=None):
        artifactPath = os.path.abspath(artifactPath)
        if os.path.isdir(artifactPath):
            artifactName = '%s_artifact.%s' % (testType + ('' if not testSubtype else '_' + testSubtype), artifactType)
            artifactPath = os.path.join(artifactPath, artifactName)

        CommandLine.say("The artifact %s will be created" % artifactPath)

        if os.path.isfile(artifactPath):
            CommandLine.say("The artifact already exists.")
            response = CommandLine.ask("Do you wish to override it?")
            if response.lower() != 'y' and response.lower() != 'yes':
                CommandLine.say("Skipping artifact.")
                return None

        return artifactPath
