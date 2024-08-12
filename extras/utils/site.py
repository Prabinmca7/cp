#!/usr/local/rnt/bin/python

import os, sys, re, glob, shutil, pprint, argparse, subprocess
from datetime import datetime

"""
A utility to create, view and delete dev sites.
"""

class Site(object):
    def __init__(self, sitename, branch, cpBranch):
        self.sitename = sitename
        self.branch = branch
        self.cpBranch = cpBranch
        self.home = os.path.expanduser('~')
        self.basedir = self.home + '/src/' + sitename
        self.cpdir = self.basedir + '/scripts/cp'
        self.oraHub = 'git@orahub.oci.oraclecorp.com:appdev-cloud-rnpd'
        self.start = None


    def view(self):
        if not os.path.isdir(self.cpdir):
            raise Exception('CP directory does not exist: ' + self.cpdir)

        self._log('\n' + self.cpdir, True, '')
        self._gitInfo(self.cpdir)
        rv, host = self._run('uname -n', os.getcwd(), False)
        self._log('\nhttp://{0}.{1}/ci/admin\n'.format(self.sitename, host.strip()), True, '')


    def create(self):
        self._logDuration('start')
        self.delete()
        self._createBaseDirs()
        self._make()
        self._setLibraryPath()
        self._createTestSite()
        self._createTestDB()
        self._compileSiteCss()
        self.view()
        self._logDuration('end')


    def delete(self):
        dbExists = False
        rv, output = self._run("mysql -e 'show databases'|egrep -w '^{0}$'".format(self.sitename), os.getcwd(), False);
        if rv is 0 and output:
            dbExists = True

        dirs = [
            '/home/httpd/cgi-bin/{0}',
            '/home/httpd/cgi-bin/{0}.cfg',
            '/home/httpd/cgi-bin/{0}.db',
            '/home/httpd/html/per_site_html_root/{0}',
        ]

        toDelete = []
        for dir in dirs:
            path = dir.format(self.sitename)
            if os.path.exists(path) or os.path.islink(path):
                self._log(path, False, '')
                toDelete.append(path)

        if not dbExists and not toDelete:
            return

        response = raw_input("\n  !! Proceed Removing Existing Database and Directories for Site '{0}' !! (y/n) ?\n".format(self.sitename))
        if response.lower().strip() != 'y':
            sys.exit('Bye..')

        if dbExists:
            self._run('mysqladmin -f drop ' + self.sitename, os.getcwd())

        for path in toDelete:
            if os.path.islink(path) or os.path.isfile(path):
                self._log('rm ' + path)
                os.unlink(path)
            elif os.path.isdir(path):
                self._log('rm -rf ' + path)
                shutil.rmtree(path)


    def _compileSiteCss(self):
        os.chdir(self.cpdir)
        paths = glob.glob('webfiles/assets/themes/*/all.scss')
        for path in paths:
            self._run('/nfs/project/cp/bin/sass --sourcemap {0} {1}'.format(path, path.replace('all.scss', 'site.css')), self.cpdir)


    def _setLibraryPath(self):
        self._run("export LD_LIBRARY_PATH=/home/httpd/cgi-bin/{0}.cfg/dll;$LD_LIBRARY_PATH".format(self.sitename))


    def _logDuration(self, mode):
        format = '%Y-%m-%d %H:%M:%S'
        if mode == 'start':
            self.start = datetime.now()
            self._log('-' * 80, True, '')
            self._log("{0} - Creating {1} site '{2}'".format(self.start.strftime(format), self.cpBranch, self.sitename), True, '')
        else:
            end = datetime.now()
            self._log("{0} - Completed in '{1}'\n".format(end.strftime(format), str(end - self.start)[:-4].split('.')[0]), True, '')


    def _createTestSite(self):
        flags = '-recreate -papisite -cp-path ' + self.cpdir
        if self.branch == 'socs':
            flags += ' -search-enduser'
        branch = self.branch if isRecognizedBranchName(self.branch) else 'trunk'
        rv, output = self._run('server/src/bin/create_test_site {0} {1}/server/src {2} {3}'.format(flags, self.basedir, self.sitename, branch))
        self._log(output, False, '  ')
        if rv != 0:
            raise Exception('Command failed')


    def _createTestDB(self):
        flags = '-cannedSPM' if self.branch == 'socs' else ''
        rv, output = self._run('server/src/bin/create_test_db {0} {1}'.format(flags, self.sitename))
        self._log(output, False, '  ')
        if rv != 0:
            raise Exception('Command failed')


    def _make(self):
        commands = [
            "nice -20 make -s -j 8 -C {0}/server/src/rnw PAPI=Y PAPI_MODULES=ConnectPHP ASSERTS='' all",
            "nice -20 make -j 8 RNT_BASE='{0}/server/src' -C {0}/scripts/cp mod_info.phph",
        ]

        for command in commands:
            rv, output = self._run(command.format(self.basedir))
            self._log(output, False, '  ')
            if rv != 0:
                raise Exception('Error: command failed')


    def _log(self, msg, toFile=True, prepend=">> "):
        msg = prepend + str(msg)
        if toFile:
            f = open(self.home + '/createsite.out', 'a')
            f.write(msg + '\n')
            f.close()

        print(msg)


    def _createBaseDirs(self):
        if not os.path.isdir(self.basedir):
            os.makedirs(self.basedir)

        self._log('cd ' + self.basedir)

        for repo in ['common', 'server']:
            if not os.path.isdir(self.basedir + '/' + repo):
                self._gitClone(repo, self.branch)
            else:
                self._gitPull(self.basedir + '/' + repo)

        scripts = self.basedir + '/scripts'
        if not os.path.isdir(scripts):
            os.makedirs(scripts)

        if not os.path.isdir(self.cpdir):
            self._log('cd ' + scripts)
            self._gitClone('cp', self.cpBranch, scripts)


    def _gitInfo(self, cwd=None):
        commands = [
            'git config --get remote.origin.url',
            'git branch',
        ]
        for command in commands:
            rv, output = self._run(command, cwd, False)
            self._log(output.strip(), True, '')


    def _gitPull(self, cwd=None):
        cwd = cwd if cwd else self.basedir
        self._log(cwd, False, '')
        self._gitInfo(cwd)
        rv, output = self._run('git pull', cwd)
        if rv != 0:
            self._log(output, False, '  ')


    def _gitClone(self, repoName, branch=None, cwd=None):
        cmd = 'git clone {0} {1}/{2}.git'.format(('' if branch is 'trunk' else '-b ' + branch), self.oraHub, repoName)
        rv, output = self._run(cmd, cwd)
        if rv is not 0:
            raise Exception('ERROR: ' + output)

        self._log("Cloning into '{0}' ...".format(repoName), False, '')

        self._run('git config core.fileMode false', cwd + '/' + repoName if cwd is not None else cwd)


    def _run(self, command, cwd=None, verbose=True):
        cwd = cwd if cwd else self.basedir

        if verbose:
            self._log(command)

        proc = subprocess.Popen(command, cwd=cwd,
            shell=(False if command.__class__ == list else True),
            stdout=subprocess.PIPE, stderr=subprocess.STDOUT)

        output = proc.communicate()[0]
        returnCode = proc.returncode or 0
        return (returnCode, output)


# -------------------------------------------------------------------------------------------------
def parseArgs():
    parser = argparse.ArgumentParser(description='A utility to create, view and delete dev sites.')
    parser.set_defaults(mode='view')
    parser.add_argument('site', help='The site name')
    parser.add_argument('-b', '--branch', default='trunk', help="The branch name. If specifying a rnw-X-Y-fixes branch just use 'X.Y'. Defaults to 'trunk'")
    parser.add_argument('-p', '--cpBranch', help="The CP branch name, if different than the branch specified by -b that is used for 'server' and 'common'.")
    parser.add_argument('-v', '--view', dest='mode', action='store_const', const='view', help='View site details (default)')
    parser.add_argument('-c', '--create', dest='mode', action='store_const', const='create', help='Create site')
    parser.add_argument('-d', '--delete', dest='mode', action='store_const', const='delete', help='Delete site')
    args = parser.parse_args()

    args.branch = expandBranchName(args.branch)
    args.cpBranch = expandBranchName(args.cpBranch) if args.cpBranch else args.branch

    return args

def isRecognizedBranchName(branch):
    return branch == 'trunk' or re.match('^rnw-\d{2}\.\d{1}-fixes$', branch)

def expandBranchName(branch):
    if not isRecognizedBranchName(branch) and re.match('^\d{2}\.\d{1}$', branch):
        return 'rnw-{0}-fixes'.format(branch.replace('.', '-'))

    return branch


def main():
    args = parseArgs()

    try:
        site = Site(args.site, args.branch, args.cpBranch)
        getattr(site, args.mode)()
    except Exception, why:
        print(str(why))


if __name__ == '__main__':
    main()
