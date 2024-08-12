#!/usr/bin/python

import glob, os, sys, glob, subprocess

""" Create the widget html test files used by Selenium in our Cruise Control environment:
      - test/rnw/scripts/euf/js/widgets/suite.html
      - test/rnw/scripts/euf/js/widgets/standard/*/*/<widget>.html

    This assumes you have a 'trunk' and a 'test' CVS checkout under HOME_DIR below.

    Note: Widget html files will NOT be modified or updated by default, so either remove
    the one that needs to be updated and let this script regenerate it, or, set 'clobber' to true below.

    Initial 'test' checkout:
      - cd <HOME_DIR>
      - TRUNK: cvs co -d test test
      - BRANCH: cvs co -r rnw-12-11-fixes -d test12.11 test
"""

# GLOBALS
HOME_DIR = '/nfs/users/rnkl/spage/src/'
WIDGET_DIR = HOME_DIR + 'trunk/rnw/scripts/cp/core/widgets/standard/'
OUTPUT_DIR = HOME_DIR + 'test/rnw/scripts/euf/js/widgets/'
OUTPUT_FILE = OUTPUT_DIR + 'suite.html'

SUITE_HEADER = """<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
  <meta content="text/html; charset=UTF-8" http-equiv="content-type" />
  <title>Test Suite</title>
</head>
<body>
<table id="suiteTable" cellpadding="1" cellspacing="1" border="1" class="selenium"><tbody>
<tr><td><b>Test Suite</b></td></tr>
"""

SUITE_FOOTER = """
</tbody></table>
</body>
</html>
"""

HEADER = """<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head profile="http://selenium-ide.openqa.org/profiles/test-case">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="selenium.base" href="" />
<title>base</title>
</head>
<body>
"""

TABLE = """<table cellpadding="1" cellspacing="1" border="1">
<thead>
<tr><td rowspan="1" colspan="3">%s</td></tr>
</thead><tbody>
<tr>
    <td>open</td>
    <td>ci/unitTest/rendering/getTestPage/widgets/standard/%s/tests/%s%s</td>
    <td></td>
</tr>
<tr>
    <td>waitForElementPresent</td>
    <td>testResults</td>
    <td></td>
</tr>
<tr>
    <td>assertText</td>
    <td>testResults</td>
    <td>Tests Failed: 0</td>
</tr>
</tbody>
</table>
"""

FOOTER = """
</body>
</html>
"""

class Widget:
    def __init__(self, path):
        self.path = path
        self.full_path = WIDGET_DIR + path
        if not os.path.isdir(self.full_path):
            raise Exception('Invalid path: ' + self.full_path)
        test_dir = self.full_path + '/tests/'
        self.html_path = OUTPUT_DIR + 'standard/' + path
        parts = path.split('/')
        self.category = parts[0]
        self.name = '/'.join(parts[1:])


def get_widgets_with_js_tests():
    widgets = []
    for root, dirs, files in os.walk(WIDGET_DIR):
        if 'info.yml' in files and 'tests' in dirs and glob.glob(root + '/tests/*.test.js'):
            widgets.append(Widget(root.replace(WIDGET_DIR, '')))

    return widgets


def write_to_file(path, contents):
    print('Writing to: ' + path)
    fob = open(path, 'w')
    fob.write(str(contents))
    fob.close()


def get_tests_from_widget(widget):
    cmd = '/bin/egrep -l "^jstestfile:" ' + widget.full_path + '/tests/*.test'
    paths = run_command(cmd).split('\n')
    tests = {}
    for path in paths:
        if not path: break
        urlParameters = run_command('/bin/egrep "^urlparameters:" ' + path).replace('urlparameters:', '').strip()
        tests[path.replace(widget.full_path + '/tests/', '').replace('.test', '')] = urlParameters

    return tests


def run_command(cmd):
    p = subprocess.Popen(cmd, stdout=subprocess.PIPE, shell=True)
    return p.communicate()[0]


def get_widget_html(widget):
    html = [HEADER]
    tests = get_tests_from_widget(widget)
    for test in sorted(tests.keys()):
        html.append(TABLE % (test, widget.path, test, tests[test]))

    html.append(FOOTER)
    return '\n'.join(html)


def create_widget_html_file(widget, clobber = False):
    base_dir = os.path.dirname(widget.html_path)
    if not os.path.isdir(base_dir):
        os.path.mkdir(base_dir)

    file_path = widget.html_path + '.html'
    if clobber or not os.path.isfile(file_path):
        write_to_file(file_path, get_widget_html(widget))


def sort_and_space_rows(rows):
    sorted_rows = []
    last_category = None
    for key in sorted(rows.keys()):
        category = key.split('/')[0]
        if last_category and last_category != category:
            sorted_rows.append('')
        last_category = category
        sorted_rows.append(rows[key])

    return '\n'.join(sorted_rows)


def main():
    widgets = get_widgets_with_js_tests()
    row = '<tr><td><a href="standard/%s.html">%s</a></td></tr>'
    rows = {}
    for widget in widgets:
        rows[widget.path] = row % (widget.path, widget.category + '/' + widget.name)
        create_widget_html_file(widget, False)
    html = SUITE_HEADER + sort_and_space_rows(rows) + SUITE_FOOTER
    write_to_file(OUTPUT_FILE, html)


if __name__ == '__main__':
    main()
