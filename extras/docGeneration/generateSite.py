#!/usr/bin/python

import optparse
import shutil
import os
import subprocess
import glob
import sys

CWD = os.path.dirname(os.path.abspath(__file__))
OUTPUT_DIR = CWD + '/output'
PHP_DOC_LOCATION = '/nfs/local/linux/phpDocumentor/current/bin'
JAVA_LOCATION = '/nfs/local/linux/jdk/1.6/current/bin/java'
JS_DOC_LOCATION = os.path.join(CWD, 'jsdoc')
PHP_DOC_CONFIG = os.path.join(CWD, 'phpdoc/phpdoc.dist.xml')


class GenerateSite(object):
    def __init__(self, options):
        self.options = options

    def generate(self):
        if self.options.js:
            self.js()
        if self.options.php:
            self.php()
        if not self.options.js and not self.options.php:
            self.php()
            self.js()

    def php(self):
        """Generate the PHP Documentation"""

        print("Generating PHP documentation")
        self.__generateDocs([
            PHP_DOC_LOCATION + '/php',
            '-d date.timezone=UTC',  # Appease PHP 5.4.6
            PHP_DOC_LOCATION + '/phpdoc.php',
            '-c',
            PHP_DOC_CONFIG,
        ])
        # Copy the index.html file into the jsdoc template directory so that we can create the combined placeholder
        shutil.copy(CWD + '/phpdoc/output/index.html', JS_DOC_LOCATION + '/templates/bootstrap/landing.tmpl')

    def js(self):
        """Generate the JS Documentation"""

        print("Generating JS documentation")
        self.__generateDocs([
            JAVA_LOCATION,
            '-jar',
            JS_DOC_LOCATION + '/jsrun.jar',
            JS_DOC_LOCATION + '/app/run.js',
            '-r=4',
            os.path.realpath(os.path.join(CWD, '../../webfiles/core/debug-js/RightNow.js')),
            os.path.realpath(os.path.join(CWD, '../../webfiles/core/debug-js/')),
            '-E=tests',
            '-t=' + JS_DOC_LOCATION + '/templates/bootstrap',
        ])

    def merge(self):
        """Merge all of the PHP and JS Doc output into a new output directory"""

        if not os.path.exists(OUTPUT_DIR):
            os.makedirs(OUTPUT_DIR)
        if os.path.exists(CWD + '/phpdoc/output'):
            subprocess.call(['cp', '-r'] + glob.glob(CWD + '/phpdoc/output/*') + [OUTPUT_DIR])
        if os.path.exists(JS_DOC_LOCATION + '/out'):
            subprocess.call(['cp', '-r'] + glob.glob(JS_DOC_LOCATION + '/out/jsdoc/*') + [OUTPUT_DIR])
        print("Generated site is at %s" % OUTPUT_DIR)

    def __generateDocs(self, cmd):
        if self.options.verbose:
            cmd.append('--verbose')

        subprocess.call(cmd)


if __name__ == '__main__':
    parser = optparse.OptionParser(
        usage="usage: %prog [options]",
        description="Generates the Customer Portal documentation website into an output dir."
    )
    parser.add_option("-v", "--verbose", help="Pass verbose flag to documentation tools", dest="verbose", action="store_true")
    parser.add_option("-j", "--js", help="Generate just the JS docs", dest="js", action="store_true")
    parser.add_option("-p", "--php", help="Generate just the PHP docs", dest="php", action="store_true")

    options, _ = parser.parse_args()

    generator = GenerateSite(options)
    generator.generate()
    generator.merge()
