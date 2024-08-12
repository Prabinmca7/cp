<? use RightNow\Utils\Config, RightNow\Utils\Url; ?>
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<!-- Begin Development Header HTML. It does not appear on production pages.  -->
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<script>
    var baseURL = "<?= $assetBasePath ?>", primaryConnectObjects = "";//This is for hte CodeMirror
    var isReference = "<?= IS_REFERENCE ?>";
</script>
<link rel="stylesheet" type="text/css" href="<?= $assetBasePath ?>thirdParty/codemirror/lib/codemirror.css"/>
<script src="<?= $assetBasePath ?>thirdParty/codemirror/lib/codemirror-min.js"></script>
<script src="<?= $assetBasePath ?>debug-js/RightNow.UI.WidgetInspector.js"></script>
<script src="<?= $assetBasePath ?>debug-js/RightNow.UI.DevelopmentHeader.js"></script>
<script type="text/template" id="widgetAttributeData" >
<div><a target="_blank" href="<?= Url::getShortEufBaseUrl(true) ?>/ci/admin/versions/manage/#widget=<%= widgetPath %>" title="<?= Config::getMessage(CLICK_FOR_WIDGET_DOCUMENTATION_LBL); ?>"><%= widgetPath %></a></div>
<div id="<%= widgetID %>_widgetInspectorErrors"></div>
<table id="widgetAttrDialog_<%= widgetID %>" class="rn_WidgetAttrsList" data-dialog-widget-path="<%= widgetPath %>">
    <thead>
        <tr>
            <th class="rn_PanelTitle">Attribute Name</th>
            <th class="rn_PanelTitle">Current Value</th>
        </tr>
    </thead>
    <% var attr, attrType, isMultiLine; %>
    <tbody>
        <% for (var attribute_name in widgetAttrs) {
                if(attribute_name === "rn_container_id" || attribute_name === "rn_container")
                    continue;
                attr = attrInfo[attribute_name];
                if(!attr && attribute_name.substring(0, 4) === "sub:") {
                    var widgetSubId = attribute_name.substring(attribute_name.indexOf(":") + 1, attribute_name.lastIndexOf(":"));
                    var subWidgetNode = document.querySelectorAll('[data-subid="' + widgetSubId + '"]');
                    if(subWidgetNode[0]){
                        var subWidgetPath = subWidgetNode[0].getAttribute("data-widget-path");
                        attr = widgetsInfo[subWidgetPath].attributes[attribute_name.substring(attribute_name.lastIndexOf(":") + 1)];
                    }
                }
                isMultiLine = attr && /^(STRING)$/i.test(attr.type) && /(\n)+/.test(widgetAttrs[attribute_name]);
        %>
            <tr>
                <td width="700px"><div class="left"><label class="rn_WidgetAttrInlineLabel" for="<%= widgetID + '_' + attribute_name %>"><b><%= attribute_name %></b></label>
                <% if(attr) {
                    if(attr.required) { %>
                        <span class="rn_WidgetRequiredAttr">*</span>
                    <% } %>
                    </div>
                        <span class="fa fa-info-circle rn_WidgetAttributeHelpTip" aria-hidden="true" title="<%= yui.Escape.html(attr.description) %>">
                        </span>
                <% } else { %>
                    <span class="fa fa-info-circle rn_WidgetAttributeHelpTip" aria-hidden="true" title= "<?= Config::getMessage(SUB_DOESNT_CANNOT_EDITED_VI_INSPECTOR_MSG) ?>" >
                    </span>
                <% } %>
                </td>
                <td class="rn_WidgetAttrsValue">
                    <% if(!attr || isHidden || isReference) { %>
                     <% if(widgetAttrs[attribute_name]) { %>
                           <% if(typeof widgetAttrs[attribute_name] === 'object') { %>
                                <% var buttonOrderingObj = []; %>
                                <% for (var obj in widgetAttrs[attribute_name]) { %>
                                   <% buttonOrderingObj.push(widgetAttrs[attribute_name][obj].name); %>
                                <% } %>
                                <%= buttonOrderingObj.join(',') %>
                           <% } else { %>
				<%= widgetAttrs[attribute_name] %>
                        <% } }  %>
                    <% } else if(/^(BOOLEAN|BOOL)$/i.test(attr.type)) { %>
                        <input id="<%= widgetID + '_' + attribute_name %>" data-attr="true" type="checkbox" name="<%= attribute_name %>" value="<%= widgetAttrs[attribute_name] %>" <%= widgetAttrs[attribute_name] ? 'checked' :'' %> />
                    <% } else if(isMultiLine) { %>
                        <textarea id="<%= widgetID + '_' + attribute_name %>" data-attr="true" name="<%= attribute_name %>">
                            <%= widgetAttrs[attribute_name] %>
                        </textarea>
                    <% } else if(/OPTION$/i.test(attr.type)) { %>
                        <select id="<%= widgetID + '_' + attribute_name %>" data-attr="true" name="<%= attribute_name %>" <%= (/^MULTIOPTION$/i.test(attr.type) ? "multiple" : "") %> value="<%= widgetAttrs[attribute_name] %>">
                        <% var isMultiOption = Y.Lang.isArray(widgetAttrs[attribute_name]); %>
                            <%
                                for (var value in attr.options) {
                                    var selected = isMultiOption ? widgetAttrs[attribute_name].indexOf(attr.options[value]) !== -1 : widgetAttrs[attribute_name] === attr.options[value];
                            %>
                                <option value="<%= attr.options[value] %>" <%= selected ? "selected" : "" %>><%= attr.options[value] %></option>
                            <% } %>
                        </select>
                    <% } else { %>
                       <% if(typeof widgetAttrs[attribute_name] === 'object') { %>
                          <% var buttonOrderingObj = []; %>
                          <% for (var obj in widgetAttrs[attribute_name]) { %>
                             <% buttonOrderingObj.push(widgetAttrs[attribute_name][obj].name); %>
                          <% } %>
                          <input id="<%= widgetID + '_' + attribute_name %>" data-attr="true" type="text" name="<%= attribute_name %>" value="<%= buttonOrderingObj.join(',') %>" />
                       <% } else { %>
                          <input id="<%= widgetID + '_' + attribute_name %>" data-attr="true" type="text" name="<%= attribute_name %>" value="<%= widgetAttrs[attribute_name] %>" />
                       <% } %>
                     <% }  %>
                </td>
            </tr>
        <% } %>
    </tbody>
</table>
</script>
<div id="saveWidgetAttrs" class="rn_DevelopmentHeaderHidden">
    <button id="saveWidgetAttrsBtn"><?= Config::getMessage(SAVE_CHANGE_CMD); ?></button>
    <button id="cancelWidgetChangesBtn"><?= Config::getMessage(CANCEL_LBL); ?></button>
</div>
<div id="rn_DevelopmentHeader">
    <div id="rn_DevelopmentHeaderPanel">
        <div class="yui3-widget-hd">
        <span class="rn_PanelTitle" data-alternate="<?= Config::getMessage(CUSTOMER_PORTAL_ADMIN_PAGE_LBL) ?>" data-default="<?= $title ?> - <?= $frameworkVersion ?>"><?= $title ?> - <?= $frameworkVersion ?></span> &nbsp;&nbsp;
            <img src='<?= $assetBasePath ?>images/error.gif' title='<?= $errorLabel ?>' alt='<?= $errorLabel ?>' class='rn_PanelTitleImage' id='rn_PanelTitleErrorImage' style='display:<?= ($errors) ? "inline" : "none" ?>'/>
            <img src='<?= $assetBasePath ?>images/warn.png' title='<?= $warningLabel ?>' alt='<?= $warningLabel ?>' class='rn_PanelTitleImage' id='rn_PanelTitleWarningImage' style='display:<?= ($warnings) ? "inline" : "none" ?>'/>
            <img src='<?= $assetBasePath ?>images/info.png' title='<? $notificationLabel ?>' alt='<? $notificationLabel ?>' class='rn_PanelTitleImage' style='display:<?= ($notifications) ? "inline" : "none" ?>'/>
        </div>
        <div class="yui3-widget-bd">
            <div id="rn_ExpandedDevelopmentHeader" style="display:none">
                <div class="rn_SectionContainer">
                    <h3 data-toggle='rn_ErrorsAndWarnings' data-toggle-icon='rn_ErrorAndWarningExpander'>
                        <?= Config::getMessage(ERRORS_AND_WARNINGS_LBL) ?>
                        <span id="rn_ErrorAndWarningExpander"><?= ($expandErrorWarningSection) ? '-' : '+'; ?></span>
                    </h3>
                    <div class="rn_SectionSubContainer" id="rn_ErrorsAndWarnings" style="display: <?= ($expandErrorWarningSection) ? 'block' : 'none' ?>">
                        <? if ($errors): ?>
                        <div id="rn_DevHeaderErrors" class="rn_ErrorHighlight">
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
                        <div id="rn_DevHeaderWarnings" class="rn_WarningHighlight">
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
                        <div id="rn_DevHeaderInfo" class="rn_NotificationHighlight">
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
                <div class="rn_SectionContainer">
                    <h3 data-toggle='rn_WidgetInfo' data-toggle-icon='rn_WidgetInfoExpander'>
                        <?= Config::getMessage(PAGE_WIDGET_INFORMATION_LBL) ?>
                        <span title='<?= Config::getMessage(OPEN_OR_CLOSE_SECTION_CMD) ?>' id="rn_WidgetInfoExpander">+</span>
                    </h3>
                    <div class="rn_SectionSubContainer" id="rn_WidgetInfo" style="display:none">
                        <a id="rn_DevelopmentHeaderWidgetListLink" href="javascript:void(0);" data-toggle="rn_DevelopmentHeaderWidgetList"  data-toggle-text="<?= Config::getMessage(HIDE_LIST_OF_WIDGETS_ON_THIS_PAGE_CMD) ?>"><?= Config::getMessage(SHOW_LIST_OF_WIDGETS_ON_THIS_PAGE_CMD) ?></a>
                        <?= Config::getMessage(PRESS_ALT_PLUS_I_INSPECT_ALL_WIDGETS_LBL)?>
                        <div id="rn_DevelopmentHeaderWidgetList" style="display:none;">
                            <? if ($widgets): ?>
                            <table style="width:100%">
                                <caption class="rn_ScreenReaderOnly"><?= Config::getMessage(LIST_WIDGETS_VERSION_INFORMATION_LBL); ?></caption>
                                <thead>
                                    <tr>
                                        <th class="rn_PanelTitle" ><?= Config::getMessage(WIDGET_PATH_LBL); ?></th>
                                        <th style="text-align:center" class="rn_PanelTitle tooltip"><?= Config::getMessage(INSPECT_LBL); ?><span class="tooltiptext"><?= Config::getMessage(INSPECT_ALL_WIDGETS_LBL); ?></span><a class="rn_InspectAll fa fa-square-o" href="javascript:void(0)"></a></th>
                                        <th style="text-align:center" class="rn_PanelTitle"><?= Config::getMessage(VERSION_LBL); ?></th>
                                        <th style="text-align:center" class="rn_PanelTitle"><?= Config::getMessage(UP_TO_DTE_LBL); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <? $count = 0; ?>
                                    <? $widgetList = array(); ?>
                                    <? foreach($widgets as $path => $info): ?>
                                    <tr class="<?=(($count++) % 2) ? 'alternate' : ''; ?>">
                                        <? $widgetPath = explode('/', $path);?>
                                        <td><a target="_blank" href="<?= $info['url']; ?>"><?= $path ?></a></td>
                                        <? $widgetName = $widgetPath[count($widgetPath) - 1]; array_push($widgetList, $widgetName); $widgetId = "Inspect" . $widgetName; ?>
                                        <td class="rn_InspectionArea"><a class="rn_widgetList fa fa-square-o" id="<?= $widgetId;?>" data-widget-name="<?= $widgetName;?>" data-inspected="notInspected" href="javascript:void(0)"></a></td>
                                        <td style="text-align:center"><?= $info['currentVersion']; ?></td>
                                        <? if($info['isLatestVersion']): ?>
                                            <td style="font-weight:bold;color:green;text-align:center">✓</td>
                                        <? else: ?>
                                            <td style="font-weight:bold;color:red;text-align:center">✗</td>
                                        <? endif; ?>
                                    </tr>
                                    <? endforeach; ?>
                                </tbody>
                            </table>
                            <? else: ?>
                            <?= Config::getMessage(THIS_PAGE_HAS_NO_WIDGETS_LBL) ?>
                            <? endif; ?>
                        </div><br>
                        <a id="rn_DevelopmentHeaderUrlParameterListLink" href="javascript:void(0);" data-toggle="rn_DevelopmentHeaderUrlParameterList" data-toggle-text="<?= Config::getMessage(HIDE_URL_PARAMETERS_USED_PAGE_CMD) ?>"><?= Config::getMessage(SHOW_URL_PARAMETERS_USED_PAGE_CMD) ?></a>
                        <div id="rn_DevelopmentHeaderUrlParameterList" style="display:none; text-align:left">
                            <table>
                            <caption class='rn_ScreenReaderOnly'><?= Config::getMessage(URL_PARAMETER_LBL) ?></caption>
                            <thead>
                                <tr>
                                    <th class="rn_PanelTitle"><?= Config::getMessage(URL_PARAMETER_LBL) ?></th>
                                    <th class="rn_PanelTitle"><?= Config::getMessage(VALUE_LBL) ?></th>
                                    <th class="rn_PanelTitle"><?= Config::getMessage(REQUIRED_LBL) ?></th>
                                    <th class="rn_PanelTitle"><?= Config::getMessage(DESCRIPTION_LBL) ?></th>
                                    <th class="rn_PanelTitle">&nbsp;</th>
                                </tr>
                            </thead>
                            <tbody>
                                <? $count = 0; ?>
                                <? if (count($urlParams)): ?>
                                <? foreach ($urlParams as $param): ?>
                                    <tr class="<?=(($count++) % 2) ? 'alternate' : ''; ?>">
                                        <td><?= $param->key ?></td>
                                        <td><div><input size='5' name='<?= $param->key ?>' type='text' value="<?= (isset($param->value)) ? $param->value : null ?>"/></div></td>
                                        <td style="text-align:center; <?= (isset($param->required) && !Url::getParameter($param->key)) ? 'color:red; font-weight:bold' : ''; ?>"><?= isset($param->required) ? Config::getMessage(TRUE_LBL) : Config::getMessage(FALSE_LBL) ?></td>
                                        <td><?= (count($param->widgetsUsedBy) > 1) ? $param->name : $param->description ?></td>
                                        <td style="text-align:center;"><a href='javascript:void(0);' data-expand-next-row="true"><?= Config::getMessage(WIDGETS_LBL) ?></a></td>
                                    </tr>
                                    <tr class="widgets" style="display:none">
                                        <td colspan="5">
                                        <? if (count($param->widgetsUsedBy) === 1): ?>
                                            <a href="<?=Url::getShortEufBaseUrl(true, '/ci/admin/versions/manage/#docs=true&amp;widget=' . $param->widgetsUsedBy[0]);?>" target="_blank"><?= $param->widgetsUsedBy[0] ?></a>
                                        <? else: ?>
                                            <ul>
                                            <? foreach ($param->widgetsUsedBy as $path): ?>
                                                <li><a href="<?=Url::getShortEufBaseUrl(true, '/ci/admin/versions/manage/#docs=true&amp;widget=' . $path);?>" target="_blank"><?= $path ?></a></li>
                                            <? endforeach; ?>
                                            </ul>
                                        <? endif; ?>
                                        </td>
                                    </tr>
                                <? endforeach; ?>
                                <? endif; ?>
                            <tr><td colspan="5"><button onclick='RightNow.UI.DevelopmentHeader.updateUrlParameters();'><?= Config::getMessage(APPLY_CHANGES_CMD) ?></button></td></tr>
                            </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="rn_SectionContainer">
                    <h3 data-toggle='rn_LinksAndResources' data-toggle-icon='rn_LinksAndResourceExpander'>
                        <?= Config::getMessage(LINKS_AND_RESOURCES_LBL) ?>
                        <span id="rn_LinksAndResourceExpander">+</span>
                    </h3>
                    <div class="rn_SectionSubContainer" id="rn_LinksAndResources" style="display:none">
                        <? if (Config::getConfig(MOD_CP_ENABLED)): ?>
                        <a href='<?= Url::getShortEufBaseUrl(true, "/ci/admin/overview/productionRedirect/{$pageUrlFragmentWithUrlParameters}") ?>'><?= Config::getMessage(GO_TO_PRODUCTION_AREA_CMD) ?></a><br>
                        <? endif; ?>
                        <a href="/ci/admin/overview/"><?= Config::getMessage(CUSTOMER_PORTAL_ADMIN_PAGE_LBL) ?></a><br>
                        <a href="<?= $otherModeUrl ?>"><?= $otherModeLabel ?></a><br>
                        <? if (!Config::getConfig(MOD_RNANS_ENABLED) && Config::getConfig(OKCS_ENABLED)): ?>
                            <? $okcsReferenceUrl = Url::getShortEufBaseUrl(true, "/ci/admin/overview/okcsReferenceRedirect/{$pageUrlFragmentWithUrlParameters}"); ?>
                            <? if(IS_OKCS_REFERENCE): ?>
                                <a href='<?= Url::getShortEufBaseUrl(true, "/ci/admin/overview/referenceRedirect/{$pageUrlFragmentWithUrlParameters}") ?>'><?= Config::getMessage(GO_TO_REFERENCE_IMPLEMENTATION_CMD) ?></a><br>
                                <? $thisModeLabel = Config::getMessage(DIRECT_URL_KA_REFERENCE_IMPLEMENTATION_LBL) ?>
                                <? $thisModeUrl = $okcsReferenceUrl; ?>
                            <? else :?>
                                <a href='<?= $okcsReferenceUrl ?>'><?= Config::getMessage(GO_TO_KA_REFERENCE_IMPLEMENTATION_LBL) ?></a><br>
                            <? endif; ?>
                        <? endif; ?>
                        <?= $toggleAbuseDetectionLink ?>
                        <?= $thisModeLabel ?>
                        <div id="rn_ModeContainer">
                            <input readonly="readonly" type="text" value="<?= $thisModeUrl ?>" onclick="this.select()"/>
                        </div>
                        <a href="http://community.rightnow.com/developer/"><?= Config::getMessage(GO_TO_RIGHTNOW_DEVELOPER_COMMUNITY_CMD) ?></a><br>
                    </div>
                </div>
                <div class="rn_SectionContainer">
                    <h3 data-toggle='rn_LinksAndPhpVersion' data-toggle-icon='rn_LinksAndPhpVersionExpander'>
                    <?= Config::getMessage(PHP_VERSION_LBL) ?> - <?= $phpVersion ?>
                        <span id="rn_LinksAndPhpVersionExpander">+</span>
                    </h3>
                    <div class="rn_SectionSubContainer" id="rn_LinksAndPhpVersion" style="display:none">
                    <?= Config::getMessage(TH_CURRENTLY_BEING_LOADED_PHP_VERSION_LBL) ?> <?= $phpVersion ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div id="codeDiv">
    <textarea id="code_bodyContent"><?= htmlspecialchars($pageDetails["pageContent"]) ?></textarea>
    <textarea id="code_templateContent"><?= htmlspecialchars($pageDetails["templateContent"]) ?></textarea>
    <textarea id="bodyContent"></textarea>
    <textarea id="templateContent"></textarea>
</div>

<script>YUI().use('event-base', function(Y){Y.on('domready', function(){RightNow.UI.DevelopmentHeader.initializePanel("<?= $originalUrl ?>", <?= count($errors) ?>, <?= count($warnings) ?>, "<?= Url::getShortEufBaseUrl(true) ?>");}, window);}); var allWidgets = <?= json_encode($widgetList) ?>; var widgetsInfo = <?= json_encode($widgets) ?>; var pagePath="<?= $pageDetails["pagePath"] ?>"; var templatePath="<?= $pageDetails["templatePath"] ?>";</script>
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
<!-- End Development Header HTML. It does not appear on production pages.    -->
<!-- +++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++ -->
