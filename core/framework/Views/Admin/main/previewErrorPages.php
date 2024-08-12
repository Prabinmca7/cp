<h2><?=\RightNow\Utils\Config::getMessage(PREVIEW_ERROR_PAGES_CMD);?></h2>

<p>
<?=sprintf(\RightNow\Utils\Config::getMessage(PRV_CP_S_CUST_S_ERR_PGS_MSG));?>
</p>
<ul>
<? foreach ($eufConfigFiles as $eufConfigFile): ?>
    <li><a href="/ci/admin/overview/showEufConfigPage<?=$eufConfigFile?>">/cp/customer/error<?=$eufConfigFile?></a></li>
<? endforeach; ?>
</ul>
