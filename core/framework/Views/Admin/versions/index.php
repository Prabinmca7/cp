<h2><?=\RightNow\Utils\Config::getMessage(VERSION_OVERVIEW_LBL);?></h2>

<h3><a href="/ci/admin/versions/manage"><?=\RightNow\Utils\Config::getMessage(WIDGET_VERSIONS_LBL);?></a></h3>
<p>
<?=\RightNow\Utils\Config::getMessage(MANAGE_VERSIONS_WIDGETS_SITE_MSG);?>
</p>

<h3><a href="/ci/admin/versions/manage/#tab=1"><?=\RightNow\Utils\Config::getMessage(FRAMEWORK_VERSIONS_LBL);?></a></h3>
<p>
<?=\RightNow\Utils\Config::getMessage(MANAGE_VERSION_FRAMEWORK_YOURE_MSG);?>
</p>

<? if (!IS_HOSTED): ?>
<?
require_once(CPCORE . "Internal/Libraries/Widget/DependencyInfo.php");
?>
<h3><?=\RightNow\Utils\Config::getMessage(SET_VERSION_TEST_MODE_CMD);?></h3>
<p>
<?=\RightNow\Utils\Config::getMessage(SET_TEST_MODE_FRAMEWORK_YOURE_CMD);?>
</p>
<ul>
<? foreach (\RightNow\Internal\Libraries\Widget\DependencyInfo::getAllFixtures() as $name => $description): ?>
<li>
    <a href="/ci/admin/versions/setTestMode/<?= $name ?>"><?= $name ?></a> - <?= $description ?>
</li>
<? endforeach; ?>
</ul>
<p>
<?=\RightNow\Utils\Config::getMessage(CURRENT_TEST_FILE_LBL);?>
<?
echo "&nbsp;";
echo \RightNow\Internal\Libraries\Widget\DependencyInfo::getTestFile() ?: \RightNow\Utils\Config::getMessage(NO_TEST_FILE_SET_LBL);
?>
</p>
<em><a href="/ci/admin/versions/removeTests"><?=\RightNow\Utils\Config::getMessage(REMOVE_TESTS_CMD);?></a></em>
<? endif; ?>
