#!/usr/bin/python

import os, sys, re, optparse, pprint, filecmp, subprocess


def getIgnoreDirs():
    return [
        '\/\.git',
        '\/CVS',
        '\/cp\/generated\/',
        '\/optimized',
        '\/generated\/',
        '\/core\/js\/',
    ]


def getIgnoreFiles():
    return [
        'optimized_includes.php',
        '.gitignore',
        '.cvsignore',
        'configbase.txt',
        'msgbase.txt',
    ]


def ignore(patterns, path):
    for pattern in patterns:
        if re.search(pattern, path):
            return True

    return False


def diff(filepath1, filepath2):
    return subprocess.Popen(['/usr/bin/diff', '-B', '-u', filepath1, filepath2], 
        stdout=subprocess.PIPE, 
        stderr=subprocess.STDOUT).communicate()[0]


def getDiffs(source, target, ignoreDirs=[], ignoreFiles=[], includeDiffs = False):
    validateDirectory(source)
    validateDirectory(target)

    missing_dirs = []
    diffs = []
    pattern = re.compile('^%s' % source.replace('/','\\/'))
    for dirpath, dirnames, filenames in [x for x in os.walk(source) if not ignore(ignoreDirs, x[0])]:
        for filepath1 in [dirpath+'/'+x for x in filenames if x not in ignoreFiles]:
            filepath2 = pattern.sub(target, filepath1)
            dirpath1 = os.path.dirname(filepath1)
            dirpath2 = os.path.dirname(filepath2)
            if not os.path.isdir(dirpath2):
                if dirpath2 not in missing_dirs:
                    diffs.append((dirpath1 + '/', '', ''))
                    missing_dirs.append(dirpath2)
            elif not os.path.exists(filepath2):
                diffs.append((filepath1, '', ''))
            elif not filecmp.cmp(filepath1, filepath2):
                diffs.append((filepath1, filepath2, diff(filepath1, filepath2) if includeDiffs else ''))

    return diffs


def validateDirectory(dir):
    if not os.path.isdir(dir):
        print "Error: directory not found: '{0}'".format(dir)
        sys.exit()


def main():
    parser = optparse.OptionParser(
        usage="%prog {source_path} {target_path}",
        description="""Checks for file and directory differences between two directories.\n
        Example: ./%prog ~/src/git/trunk/scripts/cp ~/src/cvs/trunk/rnw/scripts/cp""")
    parser.add_option('-d', '--include-diffs', dest='includeDiffs', action='store_true',
        default=False, help='Include file differences in output.')
    parser.add_option('-f', '--format', dest='format', action='store_true',
        default=False, help='Display each difference on a separate line.')
    options, args = parser.parse_args()

    if len(args) is not 2:
        parser.print_usage()
        sys.exit()

    diffs = getDiffs(args[0], args[1], getIgnoreDirs(), getIgnoreFiles(), options.includeDiffs)

    if options.format:
        for sourcePath, targetPath, output in diffs:
            if sourcePath and targetPath:
                print ">>> diff {0} {1} {2} {3}".format(sourcePath, targetPath, '\n' if output else '', output)
            elif sourcePath:
                print ">>> {0} MISSING_FROM_TARGET".format(sourcePath)
            else:
                print ">>> MISSING_FROM_SOURCE {0}".format(targetPath)
    else:
        return pprint.pprint(diffs)


if __name__ == '__main__':
    main()