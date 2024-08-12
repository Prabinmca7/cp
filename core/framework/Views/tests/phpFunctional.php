<!DOCTYPE html>
<html>
    <head><title>PHP Functional Unit Tests Help</title>
    </head>
    <body>
        <h1>PHP Functional Unit Tests Help</h1>
        <h3>Available Methods</h3>
        <dl>
            <dt>/ci/unitTest/phpFunctional/<a href='/ci/unitTest/phpFunctional/index'>index</a></dt><dd>This help message</dd>
            <dt>/ci/unitTest/phpFunctional/<a href='/ci/unitTest/phpFunctional/viewTests'>viewTests</a></dt><dd>Display all available tests</dd>
            <dt>/ci/unitTest/phpFunctional/<a href='/ci/unitTest/phpFunctional/all'>all</a></dt><dd>Run all CP tests, each in its own suite</dd>
            <dt>/ci/unitTest/phpFunctional/<a href='/ci/unitTest/phpFunctional/test'>test</a>[/selective/path/to/search/for/tests/in]</dt>
            <dd>Run the tests.  Selective Path is relative to cp/core/framework, e.g. phpFunctional/test/Utils/tests/Framework.test.php <br/>
                To limit which tests are run, you can specify:
                <ul>
                    <li>A directory, e.g. Internal/Libraries/Widget</li>
                    <li>A single unit test file, e.g. Internal/Libraries/Widget/tests/Base.test.php</li>
                </ul>
            </dd>
        </dl>
        <h3>Running a subset of tests</h3>
        <p>
            If you just want to test a few methods within your tests class, you can specific the 'subtests' URL parameter after your file test path and provide it
            with a comma-separated list of methods that you'd like to run. For example:
        </p>
        <ul>
            <li>/ci/unitTest/phpFunctional/test/Utils/tests/Text.test.php/<b>subtests/testBeginsWith</b></li>
            <li>/ci/unitTest/phpFunctional/test/Utils/tests/Framework.test.php/<b>subtests/testSetCache,testInArrayCaseInsensitive,testIsLoggedIn</b></li>
        </ul>
        <h3>Notes</h3>
        <ul>
            <li>Test files should:
            <ul>
                <li>be contained in a <code>tests/</code> subdirectory</li>
                <li>have the same basename as the tested file</li>
                <li>have the extension <code>.test.php</code> (traditional fast running unit tests) OR</li>
                <li>have the extension <code>.slowtest.php</code> (longer running integration or system tests)</li>
            </ul>
            </li>
            <li>For example <code>scripts/text.php</code> should be tested by a file called <code>scripts/tests/text.test.php</code></li>
            <li>Tests are run by <a href='http://www.simpletest.org/'>SimpleTest</a></li>
            <ul>
                <li><a href='http://www.simpletest.org/en/overview.html'>SimpleTest Documentation Overview</a></li>
                <li><a href='http://www.simpletest.org/en/unit_test_documentation.html'>SimpleTest UnitTest documentation, including list of assert methods</a></li>
            </ul>
            </li>
            <li>All test files should derive from UnitTestCase.</li>
            <li>Every unit test file should probably include a call to:<br/>
            <code>
                PhpFunctional::loadTestedFile(__FILE__);
            </code><br/>
            which will ensure that the file to be tested is loaded.</li>
            <li>Look at euf/core/framework/RightNow/Libraries/tests/SEO.test.php or euf/core/framework/RightNow/Utils/tests/url.test.php for an example of using <a href='http://www.simpletest.org/en/mock_objects_documentation.html'>SimpleTest's mock objects</a>.</li>
            </li>
            <li>In order to echo content during the running of a test while still keeping the content hidden when output is read, use the echoContent() function in CPTestCase.
            The output of the tests will include a link to toggle the visibility of the echo'ed content.
            </li>
        </ul>
        <h3>Available Sub-directories</h3>
        <p>By default, hitting <a href="/ci/unitTest/phpFunctional/test">/ci/unitTest/phpFunctional/test</a> runs all tests under <code>cp/core/framework</code>. From there, you can narrow down to a sub-directory or a particular test file within <code>framework</code>.</p>
        <p>To test directories outside of <code>framework</code>, start your path with...
            <ul>
                <li><a href="/ci/unitTest/phpFunctional/test/bootstrap/">bootstrap</a> Tests <code>scripts/bootstrap</code></li>
                <li><a href="/ci/unitTest/phpFunctional/test/core_util/">core_util</a> Tests <code>cp/core/util</code></li>
                <li><a href="/ci/unitTest/phpFunctional/test/cp/core/compatibility">compatibility</a> Tests <code>cp/core/compatibility</code></li>
            </ul>
        </p>
        <h3>Example Unit Test File</h3>
        <p>Look at any existing *.test.php file to see how to create a new test file.</p>
    </body>
</html>
