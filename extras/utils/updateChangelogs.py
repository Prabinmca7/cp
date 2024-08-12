#!/nfs/project/aarnone/python26/install/bin/python

import json, pprint, optparse, shutil, urllib2
from base import Base

USAGE = """
Retrieves recently added or updated changelog entries from the QA site and commits them back to the master/trunk git repository.
"""

def validateInterval(option, opt, value, parser):
    if value not in ('day', 'week', 'month', 'year'):
        raise optparse.OptionValueError('Not a valid interval: %s' % value)
    parser.values.interval = value


def getArguments(instance):
    parser = instance.getArgumentParser()
    parser.add_option('-d', '--dry-run', dest='dryRun', action='store_true',
                      default=False, help='Do not actually make any changes.')
    parser.add_option('-b', '--commit-to-branch', dest='commitToBranch', action='store_true',
                      default=False, help='Copy changelogs to fixes and integration branch and perform git commit there in addition to trunk.')
    parser.add_option('-a', '--ago', type='int', default=1,
                      help="A number specifying how many 'intervals' ago to search for new/modified changelogs. Default is 1")
    parser.add_option('-i', '--interval', type='string', default='day', action='callback', callback=validateInterval,
                      help="One of 'day', 'week', 'month' or 'year'. Default is 'day'")
    return parser.parse_args()[0]


class CommitChangelogs(Base):
    def __init__(self):
        Base.__init__(self, USAGE)
        self.reportUrl = ['http://changelog.reno.us.oracle.com', '/ci/admin/internalTools/addNewChangelogEntries']
        self.trunkPath = '/bulk/httpd/cgi-bin/changelog.cfg/scripts/cp/core'
        self.branchPaths = [
            '/bulk/httpd/cgi-bin/changelogbranch.cfg/scripts/cp/core',
            '/bulk/httpd/cgi-bin/changelogintegration.cfg/scripts/cp/core'
        ]
        self.startPath = self.trunkPath
        self.gitPullCommand = self.gitPath + " pull 2>&1|sed -e '/Oracle/,/employment/d'"
        self.dryRun = False # | [DRY RUN]


    def getCommits(self, args):
        auth_handler = urllib2.HTTPBasicAuthHandler()
        auth_handler.add_password(realm='RightNow CX', uri=self.reportUrl[0], user='admin', passwd='')
        opener = urllib2.build_opener(auth_handler)
        urllib2.install_opener(opener)
        uri =  '%s/ago/%d/interval/%s/commit/%s' % (''.join(self.reportUrl), args.ago, args.interval, ('false' if args.dryRun else 'true'))
        self.log(uri.split('http://')[1]) # strip http:// so this is not a clickable link
        response = urllib2.urlopen(uri).read()
        self.log(response)
        return json.loads(response)


    def commitToBranch(self, refno, account, email, files):
        for branch in self.branchPaths:
            self.runCommands([(self.gitPullCommand, branch)], self.dryRun),
            for source in files:
                target = source.replace(self.trunkPath, branch)
                msg = "Copying %(source)s to '%(target)s'" % locals()
                if self.dryRun:
                    self.log(self.dryRun + msg)
                else:
                    self.log(msg)
                    shutil.copyfile(source, target)

            self.commit(refno, account, email, files, branch)


    def commit(self, refno, account, email, files, cwd=None):
        if cwd == None:
            cwd = self.trunkPath

        if account:
            if account in self.accountMappings:
                account = self.accountMappings[account]
            account = ' Account: {0} <{1}>'.format(account, email)
        else:
            account = ''

        args = {
            'git' : self.gitPath,
            'files': ' '.join([x.split('scripts/cp/core/')[1] for x in files]),
            'refno': refno,
            'account': account,
        }

        commands = [
            "{git} add {files}",
            "{git} commit -m '{refno} - Automated changelog commit.{account}'",
            "{git} push",
        ]

        try:
            self.runCommands([(x.format(**args), cwd) for x in commands], self.dryRun)
        except Exception as e:
            # git commit returns 1 if no actual changes present
            if not 'git commit' in str(e):
                raise


    def logExceptions(self, exceptions):
        raiseException = False
        for error in exceptions:
            self.log(error)
            if error['level'] == 'error':
                raiseException = True

        return raiseException


    def gitPull(self):
        self.runCommands([
            (self.gitPullCommand, self.trunkPath),
            ('find ' + self.trunkPath + " -xdev -name changelog.yml -exec chmod 666 '{}' ';'", None),
        ], self.dryRun)


    def main(self, commandLineArgs, additionalArgs={}):
        self.dryRun = '[DRY RUN] ' if commandLineArgs.dryRun else ''

        self.gitPull()

        results = self.getCommits(commandLineArgs)
        if not results:
            self.log('No changelog entries to process')
            return

        if self.logExceptions(results['exceptions']):
            raise Exception('Errors were encountered')

        for commit in results['commits']:
            files = commit['files']
            if type(files) is not list or not files:
                raise Exception('No files to commit for changelog: ' + str(commit))

            refno = commit['refno'].encode('utf-8')
            account = commit['account']
            email = commit['email']
            files = [x.encode('utf-8') for x in files]
            self.commit(refno, account, email, files)
            if commandLineArgs.commitToBranch:
                self.commitToBranch(refno, account, email, files)


if __name__ == '__main__':
    instance = CommitChangelogs()
    instance.runMain(getArguments(instance))
