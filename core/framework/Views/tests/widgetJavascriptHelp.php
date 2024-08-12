<!DOCTYPE html>
<html lang="en">
<head profile="http://selenium-ide.openqa.org/profiles/test-case">
    <title>JS Widget Interaction Test Links</title>
</head>
<body>
    <h1>JavaScript Widget Interaction Tests</h1>
    <h2>How it works</h2>
    <p>The JavaScript Widget Interaction tests are run by Selenium test files stored in the CVS root test/rnw/scripts/euf/js/widgets directory.
    You'll need to checkout the CVS 'test' module to have access to these files.
    Under this directory there exists "suite.html" the file which instructs selenium as to which tests to run and another directory.
    This other directory, named "standard" mimics the same directory structure as the actual widgets directory. Each subdirectory of
    standard has within it a WidgetName.html file for each widget to be tested. Selenium uses these file to open the actual url of the
    JavaScript based test and looks for a result of 0 failures. If that result is not found the selenium test fails and selenium moves
    onto the next widget.</p>

    <p>
    Note: There is a script to generate suite.html and the individual widget.html files: <em>cp/docs/utils/generateTestSuiteHtml.py</em>.
          You'll need to make a copy of this script and modify the HOME_DIR variable to use it with your cvs checkout.
    </p>

    <h2>Adding a test to an already existing widget interaction test</h2>
    <p>In order to add a test to an already existing widget test three things must be done:
    <ol>
        <li>Ensure that a static test (.test) file exists, in the widget's tests directory, for the test you wish to run and that that file includes a jstestfile: name.test.js entry in it</li>
        <li>Ensure that the Widget.html file exists in the test directory in CVS and it has an entry in the suite.html file</li>
        <li>Modify the Widget.html file to include an entry for the test you are adding
            <ul>
                <li>The entry should consist of a table with a single row in the table header consisting of the name of the test.</li>
                <li>The table body should be the same as all the other tests with the exception of the url in the cell after open in the first row</li>
                <li>should end with the name of the test you are adding, which is the same name as the static .test file without the .test extension</li>
            </ul>
        </li>
    </ol>

    <h2>Creating a new Widget Interaction Test</h2>
    <p>In order to create a new test for automated interaction testing the following must be done</p>
    <ol>
        <li>Create a JavaScript Test Suite, if one does not exist or does not meet the test requirements</li>
        <li>Ensure that a static test (.test) file exists, in the widget's tests directory, or create one, and add a jstestfile: name.test.js above the output: entry</li>
        <li>Add a WidgetName.html file to the correct directory in the selenium tests directory and add an entry in that file following the same form as the rest of the files</li>
        <li>Add an entry in the suite.html file for this new WidgetName.html file</li>
        <li>Check in all of these changes</li>
    </ol>

    <h2>An error has occurred in during automated testing and you need to find out which actual test caused the problem</h2>
    <p>Follow this <strike>simple</strike> procedure:</p>
    <ol>
        <li>Open the cruise control page to the test report</li>
        <li>View the Chrome, Firefox, and IE Reports to determine in which browser the error occurred</li>
        <li>Find the widget that Failed</li>
        <li>Find the name of the test that selenium had issues with</li>
        <li>Next you will need to run the individual test
            <ol>
                <li>Open the browser that had the problem</li>
                <li>Generate the test pages for the widget (<a href="/ci/unitTest/rendering/jsFunctional/showLinks/widgets/standard/widget/that/failed">/ci/unitTest/rendering/jsFunctional/showLinks/widgets/standard/widget/that/failed</a>).</li>
                <li>Select the appropriate test link to load the test</li>
            </ol>
            <p>Tests should run identically between production and development modes. <br>But you can also generate the production mode tests by hitting the same URL, without the "showLinks" segment, which is what determines whether dev mode is used while requesting the test pages (<a href="/ci/unitTest/rendering/jsFunctional/widgets/standard/widget/that/failed">/ci/unitTest/rendering/jsFunctional/widgets/standard/widget/that/failed</a>). Links to individual widget test pages won't be shown, so you'd then manually hit /ci/unitTest/rendering/getTestPage/widgets/standard/widget/test/that/failed.</p>
            <p>NOTE: You can get really close to the metal by using the site on the CI server. When it isn't already in the process of building, you can generate the test pages on <a href="http://trunk-slow-testsite2.cc-lnx-worker1.rightnowtech.com/ci/unitTest/rendering/jsFunctional/widgets/standard">http://trunk-slow-testsite2.cc-lnx-worker1.rightnowtech.com/ci/unitTest/rendering/jsFunctional/widgets/standard</a>.</p>
        </li>
    </ol>

    <h2>Available Subdirs:</h2>
    <p>For ease of development, the following links generate test pages that aren't deployed and are in development mode.</p>
    <hr>
    <ul>
    <? $linkBase = "/ci/unitTest/rendering/jsFunctional/skipDeploy/showLinks/widgets/standard/" ?>
    <? foreach ($dirs as $category => $widgets): ?>
    <li>
        <a target="_blank" href="<?= $linkBase . $category ?>"><?= $category ?></a>
        <ul>
        <? foreach ($widgets as $widget): ?>
            <li><a target="_blank" href="<?= $linkBase . $category . '/' . $widget ?>"><?= $widget ?></a></li>
        <? endforeach; ?>
        </ul>
    </li>
    <? endforeach; ?>
    </ul>
</body>
</html>