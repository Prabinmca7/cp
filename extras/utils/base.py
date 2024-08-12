#!/nfs/project/aarnone/python26/install/bin/python

import os, sys, subprocess, smtplib, pprint, optparse
import helpers
from email.mime.text import MIMEText

"""
A Base class used to run various Customer Portal scripts and email the specified
recipients with a summary and status of the operations performed.
"""

class Base(object):
    def __init__(self, usage=None):
        self.usage = usage
        self.cvsPath = '/nfs/local/linux/bin/cvs'
        self.gitPath = '/nfs/local/linux/git/current/bin/git'
        self.status = None # SUCCESS|FAIL
        self.divider = '-'*80
        self.output = [self.divider, 'S C R I P T  O U T P U T', self.divider]
        self.domain = 'oracle.com'
        self.scriptMaintainer = 'nick.moore'
        self.fromAddress = self.scriptMaintainer + '@' + self.domain
        self.standardRecipients =  [self.scriptMaintainer]
        self.allRecipients = self.standardRecipients + [
            'steven.cottrell',
            'andrew.odonnell',
            'varun.asok',
            'raj.chandran',
            'palash.kotgirwar',
            'hussain.nayak',
            'sheetal.yadav'
        ]

        # Map legacy account names to Oracle accounts
        self.accountMappings = {
            'pkolpin':             'pkolpin',               # 'penni.kolpin@oracle.com'
            'pranab.g.garad':      'pranab.g.garad',        # 'pranab.g.garad@oracle.com'
            'rodger':              'rodger.rio@oracle.com', # 'rodger.rio@oracle.com'
            'Sridhar':             'Sridhar',               # 'sridhar.chodavarapu@oracle.com'
            'weichuan.dong':       'weichuan.dong',         # 'weichuan.dong@oracle.com'
        }

        self.emailMappings = {
            'wdyer':           'william.dyer',
            'aschubert':       'aaron.schubert',
            'rcunningham':     'randi.cunningham',
            'mkauffman':       'matthew.kauffman',
            'dmadsen':         'david.b.madsen',
        }


    def getArgumentParser(self, usage=None):
        parser = optparse.OptionParser(usage = usage if usage else self.usage)
        parser.add_option('-s', '--suppress-emails', dest='suppressEmails', action='store_true',
                          default=False, help='Do not send the usual status/summary email(s).')
        return parser


    def getEmailAddress(self, user):
        if user in self.emailMappings:
            user = self.emailMappings[user]
        return user + '@' + self.domain


    def runCommand(self, command, cwd=None):
        return helpers.runCommand(command, cwd)


    def runCommands(self, commands, dryRun=False, logOutput=True):
        results = [(x[0], x[1], 0, '') for x in commands] if dryRun else helpers.runCommands(commands)
        for command, cwd, returnCode, output in results:
            msg = ('[' + cwd + ']: ' if cwd else '') + str(command)
            if dryRun:
                self.log((dryRun if isinstance(dryRun, str) else '') + msg)
            else:
                self.log(msg)
                if returnCode != 0:
                    raise Exception("Command failed: '{command}'\n{output}".format(**locals()))
                if logOutput:
                    self.log(output)


    def log(self, message):
        msg = str(message) + '\n'
        print msg
        self.output.append(msg)


    def getRecipients(self, scope = 'standard'):
        recipients = self.standardRecipients if scope == 'standard' else self.allRecipients
        return [self.getEmailAddress(x) for x in recipients]


    def notify(self, subject, body, toAddresses=None):
        if toAddresses == None:
            toAddresses = self.getEmailAddresstRecipients()
        msg = MIMEText(body)
        msg['Subject'] = subject
        msg['From'] = self.fromAddress
        msg['To'] = ', '.join(toAddresses)

        server = smtplib.SMTP('localhost')
        server.sendmail(msg['From'], toAddresses, msg.as_string())
        server.quit()


    def runMain(self, commandLineArgs=None, additionalArgs={}):
        if commandLineArgs == None:
            commandLineArgs = self.getArgumentParser().parse_args()[0]

        try:
            cwd = os.getcwd()
            os.chdir(self.startPath)
            self.log('PWD: %s' % os.getcwd())
            self.main(commandLineArgs, additionalArgs)
            os.chdir(cwd)
            self.status = 'SUCCESS'
            toAddresses = self.getRecipients()
            errorMessage = ''
        except Exception as e:
            ## Uncomment when debugging
            # import traceback
            # traceback.print_exc()
            errorMessage = str(e)
            self.log(errorMessage)
            self.status = 'FAIL'
            toAddresses = self.getRecipients('all')

        self.log(self.status)
        summaryMessage = 'Sending summary email to: ' + pprint.pformat(toAddresses)
        if commandLineArgs.suppressEmails:
            print('NOT ' + summaryMessage)
        else:
            print(summaryMessage)
            self.notify('%s: %s' % (self.status, os.path.basename(sys.argv[0])), '%s\n%s' % (errorMessage, '\n'.join(self.output)), toAddresses)


    def main(self):
        raise Exception('main must be over-ridden by child class')

