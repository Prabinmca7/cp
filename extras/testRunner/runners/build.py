#! /usr/bin/python

import os, shutil, urllib, datetime, subprocess
from lib.helpers import Helpers
from lib.commandLine import CommandLine
from runners.runner import TestRunner


"""
Clones the CP git repository, runs 'rake all' and performs validation on the resulting directory structure.
"""
class BuildRunner(TestRunner):
    def __init__(self, siteUrl, optionalSegment, options):
        self.url = siteUrl
        self.options = options

    """
    Creates 'target' if it does not exist by cloning the CP git repository.
    @returns bool True if 'target' already existed
    """
    def createTargetDir(self, target):
        basedir = os.path.dirname(target)
        existed = True
        if not os.path.isdir(basedir):
            existed = False
            os.makedirs(basedir)
        elif not os.path.isdir(target):
            existed = False

        if not existed:
            # if this is for development, copy the current tree, not pull it from orahub
            if self.options['development']:
                self.runCommands([('\cp -rf ' + os.path.dirname(__file__) + '/../../../../cp .', basedir)])
            else:
                self.runCommands([('git clone -b ' + self.options['branch'] + ' git@orahub.oci.oraclecorp.com:appdev-cloud-rnpd/cp.git ' + os.path.basename(target), basedir)])

        return existed


    """
    Runs 'rake all' in 'target' directory
    """
    def build(self, target, buildNum, cxBuildNum, development):
        CommandLine.say('Creating CP build in ' + target)
        start = datetime.datetime.utcnow()
        cmd = '/nfs/project/cp/ruby/ruby-2.3.0-install/bin/rake --verbose --trace all'
        if buildNum:
            cmd += ' BUILD_NUM=' + str(buildNum)
        if cxBuildNum:
            cmd += ' CX_BUILD_NUM=' + str(cxBuildNum)
        if development:
            cmd += ' DEVELOPMENT=' + os.path.dirname(__file__) + '/../../../../../'
        self.runCommands([(cmd, target)])
        delta = datetime.datetime.utcnow() - start
        CommandLine.say("Elapsed: {0} hours, {1} minutes".format(delta.seconds/3600, delta.seconds/60))


    """
    Runs the list of commands
    @raises Exception if command fails
    """
    def runCommands(self, commands):
        for command, cwd in commands:
            CommandLine.say(command)
            rv, output = self.runCommand(command, cwd)
            if rv != 0:
                raise Exception("Command failed: '{command}\n{output}".format(**locals()))
            CommandLine.say(output)


    """
    Runs the specified command
    @returns tuple A tuple containing the return code and output
    """
    def runCommand(self, cmd, cwd=None):
        proc = subprocess.Popen(cmd, cwd=cwd,
            shell=(False if cmd.__class__ == list else True),
            stdout=subprocess.PIPE, stderr=subprocess.STDOUT)

        output = proc.communicate()[0]
        rv = proc.returncode or 0
        return (rv, output)


    """
    Creates the build and runs associated tests.
    @returns Integer 0 upon success, else an error code.
    """
    def run(self):
        statusCode = 1
        target = os.path.expanduser(self.options['target'])
        try:
            targetExisted = self.createTargetDir(target)
            if not targetExisted or self.options['forceBuild']:
                self.build(target, self.options['buildNum'], self.options['cxBuildNum'], self.options['development'])
        except Exception as e:
            CommandLine.say("Error: '{0}'".format(e))
            return statusCode

        CommandLine.say('Start test output')
        statusCode = Helpers.runTests(
            '{0}ci/unitTest/ValidateBuild/test/reporter/TAP/target/{1}'.format(self.url, urllib.quote(target, '')),
            self.options
        )
        CommandLine.say('End test output')

        return statusCode
