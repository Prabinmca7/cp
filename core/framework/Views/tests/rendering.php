<?
    function folder($testPath, $subfolder = 1) {
        $path = explode('/', $testPath);
        for ($i = 0; $i < $subfolder; $i++) {
            array_shift($path);
        }
        return array_shift($path);
    }
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <title>Rendering Unit Tests Help</title>
    <style type="text/css">
    html {
        font-family: Helvetica, Arial, sans-serif;
        background: #0f2233;
    }
    a {
        color: #e9bd42;
        text-decoration: none;
    }
    .test,
    a:visited {
        color: #f0f0f0;
    }
    .heading {
        font-weight: bold;
        color: #487399;
    }
    .group,
    .folder {
        display: block;
        clear: both;
    }
    li, ul, a {
        display: block;
        margin: 0;
        padding: 0;
    }
    .subfolder {
        margin-left: 10px;
    }
    .folder > li {
        background: #000;
        float: left;
        padding: 4px;
        border-radius: 2px;
        min-height: 100px;
        min-width: 150px;
        margin-bottom: 4px;
    }
    .folder > li > .heading {
        color: #e9bd42;
        margin-bottom: 8px;
    }
    .group > ul > li {
        clear: both;
        overflow: hidden;
        display: block;
        margin-bottom: 10px;
        padding: 10px;
    }
    .group > ul > li.odd {
        background: #091927;
    }
    .group > ul > li.even {
        background: #162C40;
    }
    .help {
        float: right;
    }
    </style>
</head>
<body>
    <a class="help" href="http://quartz.us.oracle.com/shelf/docs/Product%20Reference/CP/Unit%20Tests/Rendering%20Tests.html" target="_blank">Rendering test help ☞</a>
    <h1><a href="/ci/unitTest/rendering/test">All rendering tests</a></h1>


    <? foreach ($tests as $groupName => $group): ?>
        <div class="group">
        <h2><a href="/ci/unitTest/rendering/deployAndTest/<?= $groupName ?>" target="_blank"><?= $groupName ?></a></h2>
        <? if ($groupName !== 'widgets') $groupName .= '/tests'; ?>
        <ul>
        <?
            $rowNumber = 0;
            while ($testPath = next($group)):
                $rowNumber += 1;
        ?>
            <li class="<?= ($rowNumber % 2 == 1) ? 'odd' : 'even' ?>">
            <?
                $folder = folder($testPath);
            ?>
            <? if ($folder !== $current): ?>
                <a class="heading" href="/ci/unitTest/rendering/deployAndTest/<?= $groupName ?>/<?= $folder ?>" target="_blank"><?= $folder ?></a>
                <ul class="folder js-masonry" data-masonry-options='{"itemSelector": ".folder-items", "columnWidth": 180}'>
                <? $current = $folder; ?>
            <? endif; ?>

                <? do { ?>
                <li class="folder-items">
                    <? if (count(explode('/', $testPath)) === 5): ?>
                        <? $subfolder = folder($testPath, 2); ?>
                        <a class="heading" href="/ci/unitTest/rendering/deployAndTest/<?= $groupName ?>/standard/<?= $folder ?>/<?= $subfolder ?>" target="_blank"><?= $subfolder ?></a>
                        <ul class="subfolder">
                        <? do { ?>
                            <li class="test">
                                <?= \RightNow\Utils\Text::getSubstringBefore(\RightNow\Utils\Text::getSubstringAfter($testPath, 'tests/'), '.test') ?>
                            </li>
                        <? } while (($testPath = next($group)) && folder($testPath, 2) === $subfolder); ?>
                        <? if ($testPath): ?>
                        <? prev($group); ?>
                        <? endif; ?>
                        </ul>
                    <? else: ?>
                        <span class="heading"><?= \RightNow\Utils\Text::getSubstringBefore(\RightNow\Utils\Text::getSubstringAfter($testPath, 'tests/'), '.test') ?></span>
                    <? endif; ?>
                </li>
                <? } while (($testPath = next($group)) && folder($testPath) === $current); ?>
                <? if ($testPath): ?>
                <? prev($group); ?>
                <? endif; ?>
                </ul>
            </li>
        <? endwhile; ?>
        </ul>
        </div>
    <? endforeach; ?>
    <script src="//cdnjs.cloudflare.com/ajax/libs/masonry/3.1.1/masonry.pkgd.js"></script>

    <h2>General Notes</h2>
    <ul>
        <li>Widget unit test files must have a '.test' extension and must be in a tests directory which is a sibling to a widget view.</li>
        <li>Example call for FormSubmit widget: <a href="/ci/unitTest/rendering/test/widgets/standard/input/FormSubmit">/ci/unitTest/rendering/test/widgets/standard/input/FormSubmit</a></li>
        <li>Example call for all input widgets: <a href="/ci/unitTest/rendering/test/widgets/standard/input">/ci/unitTest/rendering/test/widgets/standard/input</a></li>
        <li>Example call for all widgets: <a href="/ci/unitTest/rendering/test/widgets">/ci/unitTest/rendering/test/widgets</a></li>
        <li>Other unit test files (e.g. views) must have a '.test' extension and must be underneath a tests directory.</li>
        <li>Example call for all condition_tag tests: <a href="/ci/unitTest/rendering/deployAndTest/views/tests/condition_tag">/ci/unitTest/rendering/deployAndTest/views/tests/condition_tag</a></li>
    </ul>
</body>
</html>
