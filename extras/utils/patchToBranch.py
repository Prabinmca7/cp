#!/usr/bin/python

import os, sys, re, optparse, pprint, filecmp, subprocess, glob
import helpers

class Settings:
    patchDir = '~/tmp/patches'
    target = '~/src/git/branch/scripts/cp'

try:
    sys.path.append(os.path.expanduser('~'))
    import patchToBranchConfig
    Settings.patchDir = patchToBranchConfig.patchDir
    Settings.target = patchToBranchConfig.target
except Exception:
    pass

Settings.patchDir = os.path.expanduser(Settings.patchDir)
Settings.target = os.path.expanduser(Settings.target)


def parseArgs():
    parser = optparse.OptionParser(description="Creates a patch from a git changeset and optionally applies the patch to the target code tree.")

    parser.add_option('-t', '--target', dest='target',
        help='The target code tree to apply the patch. Default is ' + Settings.target)

    parser.add_option('-r', '--revision', dest='revision', default='HEAD',
        help="The revision or revision range to create the patch from. Usually 'HEAD' (the default) or the SHA1 hash that denotes a specific commit.")

    parser.add_option('-m', '--commit-message', dest='commitMessage',
        help='The commit message. If specified a commit/push will be attempted in the target code tree.')

    parser.add_option('-a', '--apply-patch', dest='applyPatch', action='store_true',
        default=False, help='Apply the patch to the target code tree.')

    options, args = parser.parse_args()

    if options.target:
        Settings.target = os.path.expanduser(options.target)

    if options.commitMessage:
        if not options.applyPatch:
            parser.error('Cannot specify a commit message when --apply-patch is not also specified.')
        if not re.search('^\d{6}-\d{6}.+$', options.commitMessage):
            parser.error('Commit message needs to start with a valid QA refno having format: YYMMDD-XXXXXX')


    return options, args


def createPatch(revision):
    patchDir = Settings.patchDir
    if not os.path.isdir(patchDir):
        os.makedirs(patchDir)

    print('\nCreating patch..')
    patchPath = '{0}/{1}.patch'.format(patchDir, revision)
    cmd = 'git format-patch -1 {0} --stdout'.format(revision)

    print("  {cmd} > {patchPath}".format(**locals()))
    rv, output = helpers.runCommand(cmd)
    if rv != 0:
        raise Exception(output)

    patch, files = modifyTargetPaths(output)
    open(patchPath, 'w').write(patch)

    return patchPath, files


"""
Modify '+++ b/{target file}' lines in the patch to point to the target repo

Note: Currently this only deals with file changes and not additions or deletions.
      May also need to do some tweaking on the file path manipulation below to
      match different repo directory structures.

@returns tuple A tuple containing the modified patch and the list of changed files.
"""
def modifyTargetPaths(patch):
    p = re.compile('^\+\+\+ (b/.*)$')
    modified = ''
    files = []
    for line in patch.split('\n'):
        match = p.match(line)
        if match:
            filepath = (Settings.target + '/' + match.groups()[0].split('b/')[1]).replace('/cp/cp/', '/cp/')
            files.append(filepath)
            line = '+++ ' + filepath
            print('\t' + line)

        modified += line + '\n'

    return modified, files


def applyPatch(patchPath):
    command = 'patch -Np0 {0} < ' + patchPath
    print('\nApplying patch..')
    for arg in ['--dry-run', '']:
        cmd = command.format(arg)
        print('' if arg else ('  ' + cmd))
        rv, output = helpers.runCommand(cmd)
        if rv != 0:
            raise Exception(output)

    print('Patch applied successfully')


def runCommands(commands, cwd=None):
    results = helpers.runCommands([(x, cwd) for x in commands])
    for command, cwd, returnCode, output in results:
        print('  ' + command)
        if returnCode != 0:
            raise Exception("Command failed: '{command}'\n{output}".format(**locals()))

        print(output)


def cvsCommit(target, files, commitMessage):
    cvs='/nfs/local/linux/bin/cvs'
    files = ' '.join(files)
    commands = [
        "{cvs} -q update -Pd",
        "{cvs} commit -m '{commitMessage}' {files}",
    ]
    runCommands([x.format(**locals()) for x in commands], target)


def gitCommit(target, files, commitMessage):
    git='/nfs/local/linux/git/current/bin/git'
    files = ' '.join(files)
    commands = [
        '{git} pull',
        '{git} add {files}',
        '{git} status',
        "{git} commit -m '{commitMessage}'",
        '{git} push',
    ]
    runCommands([x.format(**locals()) for x in commands], target)


def commit(files, commitMessage):
    target = Settings.target
    print('\nAttempting commit at {0}..'.format(target))
    isCvs = glob.glob(target + '/CVS')
    if isCvs:
        cvsCommit(target, files, commitMessage)
    else:
        gitCommit(target, files, commitMessage)


def main():
    try:
        options, args = parseArgs()
        patchPath, files = createPatch(options.revision)
        if options.applyPatch:
            applyPatch(patchPath)

            if options.commitMessage:
                commit(files, options.commitMessage)

    except Exception as e:
        ## Uncomment when debugging
        # import traceback
        # traceback.print_exc()
        print "Error: '{0}'".format(e)

if __name__ == '__main__':
    main()
