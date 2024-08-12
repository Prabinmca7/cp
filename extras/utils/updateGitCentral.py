#!/nfs/project/aarnone/python26/install/bin/python

import time, hashlib, re, pprint, os
from base import Base

os.putenv('CVS_RSH', 'ssh')

USAGE = """
Pulls in CVS updates from cvs-central to git-central
"""

class UpdateGitCentral(Base):
    def __init__(self):
        Base.__init__(self, USAGE)
        self.gitPath = '/nfs/local/linux/git/current/bin/git'
        self.cvsCentralPath = self.startPath = '/nfs/data/git-sync/cp/cvs-central'
        self.cvsWebUrl = 'http://ruby/cgi-bin/cvs/cvsweb/rnt/'
        self.standardRecipients.append('patrick.walsh')
        self.standardRecipients.append('steven.cottrell')
        self.allRecipients = self.standardRecipients


    def getHashFromRevision(self, revision):
        m = hashlib.md5()
        date = revision['date'].split()[0] if revision['date'] else 'unknown'
        m.update(revision['author'] + revision['commit message'] + date)
        return m.hexdigest()


    def sendChangeSummaryEmails(self, output):
        revisions = {}
        pattern = re.compile('^\s*#\s+([a-z ]+):\s+(.+)$')
        for line in [x for x in output.split('\n') if pattern.match(x)]:
            fileStatus, filePath = pattern.match(line).groups()
            if filePath.startswith('rnw/scripts/euf/'):
                # print 'LINE:' + line
                # print '\tfileStatus: ' + fileStatus
                # print '\tfilePath: ' + filePath
                command = self.cvsPath + ' log -b -N ' + filePath
                #print '\tcommand: ' + command
                rv, output = self.runCommand(command)
                revision = self.getLatestRevision(output)
                #pprint.pprint(revision)
                key = self.getHashFromRevision(revision)
                fileEntry = filePath + ' ' + revision['revision']
                del revision['revision']
                if key in revisions:
                    revisions[key]['files'].append(fileEntry)
                else:
                    revisions[key] = revision
                    revisions[key]['files'] = [fileEntry]

        emailBody, toAddresses = self.getEmailBodyAndAddressesFromRevisions(revisions)

        if emailBody:
            self.notify('Customer Portal CVS Updates Summary',
                        'Please ensure your changes are also checked into git if appropriate' + '\n\n' + emailBody,
                        toAddresses)


    def getEmailBodyAndAddressesFromRevisions(self, revisions):
        body = []
        toAddresses = self.getRecipients('all')
        for revision in revisions.values():
            author = revision['author']
            if not self.debugMode and author:
                toAddresses.append(getEmailAddress(author))
            body.append(self.divider)
            body.append('author: ' + (author or 'unknown'))
            body.append('date: ' + (revision['date'] or 'unknown'))
            body.append('commit message: ' + (revision['commit message'] or 'unknown'))
            for file in revision['files']:
                body.append('\t' + self.cvsWebUrl + file)
            body.append('\n')

        return '\n'.join(body), list(set(toAddresses))


    def getLatestRevision(self, output):
        revisionMatchObject = re.compile('^revision ([\d\.]+)$')
        dateAndAuthorMatchObject = re.compile('^date: ([\d/: ]+);  author: ([\d a-z A-Z]+);')
        endMatchObject = re.compile('^----------------------------$')
        revision = {'commit message': '', 'revision': '', 'date': '', 'author': '', 'commit message': ''}
        inRevision = False
        for line in output.split('\n'):
            if revisionMatchObject.match(line):
                revision['revision'] = revisionMatchObject.match(line).groups()[0]
                inRevision = True
            elif dateAndAuthorMatchObject.match(line):
                groups = dateAndAuthorMatchObject.match(line).groups()
                revision['date'] = groups[0]
                revision['author'] =  groups[1]
            elif inRevision:
                if endMatchObject.match(line):
                    break
                revision['commit message'] += line

        return revision

    ## Examine output of 'git commit' and 'git status' to determine
    ## if any changes are present.
    def changesExist(self, command, output):
        if command == 'commit':
            rv = not re.search('no(thing| changes added) to commit', output)
        elif command == 'status':
            rv = re.search('modified:|added:', output)
        else:
            raise Exception('Invalid command: ' + command)

        self.log(('' if rv else 'no ') + 'changes exist')
        return rv


    def main(self, commandLineArgs, additionalArgs = {}):
        git = self.gitPath
        cvs = self.cvsPath
        now = time.strftime('%Y-%m-%d %H:%M', time.localtime())
        checkoutCvsBranch = '%(git)s checkout cvs-branch' % locals()
        gitCommit = '%(git)s commit -m "sync with cvs - %(now)s"' % locals()
        gitStatus = '%(git)s status' % locals()

        commands = [
          checkoutCvsBranch,
          '%(git)s pull origin cvs-branch',
          '%(cvs)s -q update -Pd',
          gitStatus,
          '%(git)s add .',
          gitCommit, # returns 1 if no updates present
          '%(git)s checkout master',
          '%(git)s pull origin master',
          '%(git)s merge cvs-branch',
          '%(git)s push',
          checkoutCvsBranch,
          '%(git)s push',
        ]

        for command in [(x % locals()) for x in commands]:
            self.log('%s\n%s' % (self.divider, command))
            rv, output = self.runCommand(command)
            if rv != 0 and not (command == gitCommit and not self.changesExist('commit', output)):
                self.runCommand(checkoutCvsBranch)
                raise Exception('COMMAND FAILED: %(command)s\noutput: %(output)s' % locals())

            ## Not sending these for now ##
            # if command == gitStatus and self.changesExist('status', output) and not commandLineArgs.suppressEmails:
            #     try:
            #         self.sendChangeSummaryEmails(output)
            #     except Exception, why:
            #         self.log(str(why))
            #         notify('sendChangeSummaryEmails failed', str(why))

            self.log(output)


if __name__ == '__main__':
    UpdateGitCentral().runMain()
