<? use RightNow\Utils\Config, RightNow\Utils\Url; ?>
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<!-- Begin Development Header HTML. It does not appear on production pages.  -->
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<noscript>
    <div style="border-bottom: 1px solid black; text-align:center">
        <span>
            <b><?= $title ?></b>
            <?if($errors):?>
                <img src='<?= $assetBasePath ?>images/error.gif' title='<?= $errorLabel ?>' alt='<?= $errorLabel ?>'/>
            <?endif;?>
            <?if($warnings):?>
                <img src='<?= $assetBasePath ?>images/warn.png' title='<?= $warningLabel ?>' alt='<?= $warningLabel ?>'/>
            <?endif;?>
            <?if($notifications):?>
                <img src='<?= $assetBasePath ?>images/info.png' title='<?= $notificationLabel ?>' alt='<?= $notificationLabel ?>'/>
            <?endif;?>
        </span>
    </div>
</noscript>
<div id="rn_DevelopmentHeader" style="display:none;">
    <a href="javascript:void(0);" id="rn_HeaderDisplayIcon" onclick="RightNow.UI.DevelopmentHeader.toggleDevelopmentHeaderArea('rn_DevelopmentHeader');">×</a>
    <div id="rn_DevelopmentHeaderTitleBar" onclick="RightNow.UI.DevelopmentHeader.toggleDevelopmentHeaderArea('rn_ExpandedDevelopmentHeader');">
        <span class="rn_PanelTitle">
            <?= $title ?> - <?= $frameworkVersion ?>
            <img src='<?= $assetBasePath ?>images/error.gif' title='<?= $errorLabel ?>' alt='<?= $errorLabel ?>' class='rn_PanelTitleImage' id='rn_PanelTitleErrorImage' style='display:<?= ($errors) ? "inline" : "none" ?>'/>
            <img src='<?= $assetBasePath ?>images/warn.png' title='<?= $warningLabel ?>' alt='<?= $warningLabel ?>' class='rn_PanelTitleImage' id='rn_PanelTitleWarningImage' style='display:<?= ($warnings) ? "inline" : "none" ?>'/>
            <img src='<?= $assetBasePath ?>images/info.png' title='<?= $notificationLabel ?>' alt='<?= $notificationLabel ?>' class='rn_PanelTitleImage' style='display:<?= ($notifications) ? "inline" : "none" ?>'/>
        </span>
    </div>
    <div id="rn_ExpandedDevelopmentHeader" style="display:none">
        <div class="rn_Opaque"></div>
        <div class="rn_ExpandedContent">
            <div class="rn_SectionContainer">
                <span class="rn_SectionTitle" onclick="RightNow.UI.DevelopmentHeader.toggleDevelopmentHeaderArea('rn_ErrorsAndWarnings');"><?= Config::getMessage(ERRORS_AND_WARNINGS_LBL) ?></span>
                <div class="rn_SectionSubContainer" id="rn_ErrorsAndWarnings" style="display: <?= ($expandErrorWarningSection) ? 'block' : 'none' ?>">
                    <? if ($errors): ?>
                    <div id="rn_DevHeaderErrors" class="rn_Highlight">
                        <ul id="rn_ErrorInformationList">
                        <? foreach ($errors as $message): ?>
                            <li><?= $message ?></li>
                        <? endforeach; ?>
                        </ul>
                    </div>
                    <? else: ?>
                    <div id="rn_DevHeaderErrors"><span id='rn_ErrorCountLabel'><?= $errorLabel ?></span></div>
                    <? endif; ?>

                    <? if ($warnings): ?>
                    <div id="rn_DevHeaderWarnings" class="rn_Highlight">
                        <span id='rn_WarningCountLabel'><?= $warningLabel ?></span>
                        <ul id="rn_WarningInformationList">
                        <? foreach ($warnings as $message): ?>
                            <li><?= $message ?></li>
                        <? endforeach; ?>
                        </ul>
                    </div>
                    <? else: ?>
                    <div id="rn_DevHeaderWarnings"><span id='rn_WarningCountLabel'><?= $warningLabel ?></span></div>
                    <? endif; ?>

                    <? if ($notifications): ?>
                    <div id="rn_DevHeaderInfo" class="rn_Highlight">
                        <span id='rn_NotificationCountLabel'><?= $notificationLabel ?></span>
                        <ul id="rn_ErrorInformationList">
                        <? foreach ($notifications as $message): ?>
                            <li><?= $message ?></li>
                        <? endforeach; ?>
                        </ul>
                    </div>
                    <? else: ?>
                    <div id="rn_DevHeaderInfo"><span id='rn_NotificationCountLabel'><?= $notificationLabel ?></span></div>
                    <? endif; ?>
                </div>
            </div>
            <? if (Config::getConfig(MOD_CP_ENABLED)): ?>
            <div class="rn_SectionContainer">
                <span class="rn_SectionTitle"><a href='<?= Url::getShortEufBaseUrl(true, "/ci/admin/overview/productionRedirect/{$pageUrlFragmentWithUrlParameters}") ?>'><?= Config::getMessage(GO_TO_PRODUCTION_AREA_CMD) ?></a></span>
            </div>
            <? endif; ?>
            <div class="rn_SectionContainer">
                <span class="rn_SectionTitle"><?= $toggleAbuseDetectionLink ?></span>
            </div>
            <div class="rn_SectionContainer">
                <span class="rn_SectionTitle"><a href="<?= Url::getShortEufBaseUrl(true, '/ci/admin/overview/');?>"><?= Config::getMessage(CUSTOMER_PORTAL_ADMIN_PAGE_LBL) ?></a></span>
            </div>
        </div>
    </div>
</div>
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<!-- End Development Header HTML. It does not appear on production pages.    -->
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
