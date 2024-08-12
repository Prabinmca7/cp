<!DOCTYPE html>
<html lang="en">
<head profile="http://selenium-ide.openqa.org/profiles/test-case">
    <title>Widget Functional Links</title>
</head>
<body>
    <h1>Widget Functional Tests</h1>
    <h3>Available Methods</h3>
    <dl>
        <dt>/ci/unitTest/widgetFunctional/<a href='/ci/unitTest/widgetFunctional/index'>index</a></dt><dd>This help message</dd>
        <dt>/ci/unitTest/widgetFunctional/<a href='/ci/unitTest/widgetFunctional/all'>all</a></dt><dd>Run all tests</dd>
        <dt>/ci/unitTest/widgetFunctional/<a href='/ci/unitTest/widgetFunctional/test'>test</a>[/selective/path/to/search/for/tests/in]</dt>
        <dd>Run <code>controller.test.php</code> tests. Selective Path is relative to cp/core/widgets, e.g. <code>widgetFunctional/test/standard/input/ProductCategoryInput</code> <br/>
            To limit which tests are run, you can specify:
            <ul>
                <li>A directory, e.g. standard/input</li>
                <li>A single unit test file, e.g. standard/input/ProductCategoryInput</li>
            </ul>
        </dd>
    </dl>
    <h3>Running a subset of tests</h3>
    <p>
        If you just want to test a few methods within your tests class, you can specific the 'subtests' URL parameter after your file test path and provide it
        with a comma-separated list of methods that you'd like to run. For example:
    </p>
    <ul>
        <li><code>/ci/unitTest/widgetFunctional/test/standard/input/ProductCategoryInput/<b>subtests/testGetDefaultChain</b></code></li>
        <li><code>/ci/unitTest/widgetFunctional/test/standard/input/ProductCategoryInput/<b>subtests/testGetDefaultChain,testGetData</b></code></li>
    </ul>

    <h3>Notes</h3>
    <p>
        <ul>
            <li>Test files should be contained in a widget's <code>tests</code> directory.</li>
            <li>Test files should be named <code>controller.test.php</code></li>
        </ul>
    </p>

    <h3>Widgets containing functional unit tests:</h3>
    <ul>
    <? $linkBase = "/ci/unitTest/widgetFunctional/test/standard/" ?>
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
