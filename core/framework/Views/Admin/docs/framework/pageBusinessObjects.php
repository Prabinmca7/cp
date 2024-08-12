<? use RightNow\Utils\Config; ?>
<h2><?=Config::getMessage(BUSINESS_OBJECTS_LBL);?></h2>
<?if(!Config::getConfig(MOD_RNANS_ENABLED)): unset($metaData['Answer']); endif;?>
<p>
    <?printf(Config::getMessage(FOLLOWING_OBJECTS_FLDS_DEFINED_RN_MSG), $documentationVersion)?>
</p>
    <ul id="anchors">
        <?foreach (array_keys($metaData) as $objectName):?>
        <li><a href="javascript:void(0);" data-object-name="<?=strtolower(str_replace(".", "-", $objectName))?>"><?=$objectName?></a></li>
        <?endforeach?>
    </ul>
<?foreach($metaData as $objectName => $fields):?>
<h3 id="<?=strtolower(str_replace(".", "-", $objectName))?>"><?=$objectName?></h3>
<div class="box">
    <p><strong><?=Config::getMessage(DISPLAY_LBL)?>:</strong><br />
    &lt;rn:field name="<?=$objectName?>.<i><?=Config::getMessage(FIELD_LBL)?></i>" /&gt;</p>
    <?if($objectName !== 'Answer'):?>
    <p><strong><?=Config::getMessage(INPUT_LBL)?>:</strong><br />
    &lt;rn:widget path="input/FormInput" name="<?=$objectName?>.<i><?=Config::getMessage(FIELD_LBL)?></i>" /&gt;</p>
    <?endif;?>
</div>

<table class="businessObject">
    <thead>
        <tr>
            <th><?=Config::getMessage(FIELD_LBL)?></th>
            <th><?=Config::getMessage(DATA_TYPE_LBL)?></th>
        </tr>
    </thead>
    <?foreach ($fields as $field => $fieldInfo):?>
    <tr>
        <td title="<?=\RightNow\Utils\Text::escapeHtml($fieldInfo['metaData']->description)?>">
            <a href="javascript:void(0);"
               data-meta-data="<?=strtr(json_encode($fieldInfo['metaData']), array("'" => '&apos;', '"' => '&quot;'))?>"
               data-object-name="<?=$objectName;?>"
               data-named-values="<?=strtr(json_encode($fieldInfo['namedValues']), array("'" => '&apos;', '"' => '&quot;'))?>">
               <?printf("%s.%s", $objectName, $field)?></a>
        </td>
        <td><?=$fieldInfo['metaData']->COM_type?></td>
    </tr>
    <?endforeach?>
</table>
<div class="right textright"><a onclick="window.scrollTo(0,0);" href="javascript:void(0);">&#9650; <?=Config::getMessage(BACK_TO_TOP_CMD)?></a></div><br />
<?endforeach?>

<div id="businessObjectDetailsDialog" class="yui-pe-content">
    <div class="hd"></div>
    <div class="bd"></div>
</div>

<script type="text/javascript">
var messages = <?= json_encode(array(
    'true_lbl'      => Config::getMessage(TRUE_LBL),
    'false_lbl'     => Config::getMessage(FALSE_LBL),
    'close_lbl'     => Config::getMessage(CLOSE_LBL),
    'on_create_lbl' => Config::getMessage(ON_CREATE_LBL),
    'on_update_lbl' => Config::getMessage(ON_UPDATE_LBL),
)) ?>;
</script>

<script type="text/template" id="fieldDetails" data-constraint-labels="<?=strtr(json_encode($constraints), array("'" => '&apos;', '"' => '&quot;'))?>">
<table id="businessObjectDetails">
    <% if (metaData.label !== metaData.name) { %>
    <tr>
        <td><?=Config::getMessage(LABEL_LBL)?></td>
        <td><%= metaData.label %></td>
    </tr>
    <% } %>
    <tr>
        <td><?=Config::getMessage(DATA_TYPE_LBL)?></td>
        <td><%= metaData.COM_type %></td>
    </tr>
    <tr>
        <td><?=Config::getMessage(DESCRIPTION_LBL)?></td>
        <td><%= escapeHtml(metaData.description) %></td>
    </tr>
    <tr>
        <td><?=Config::getMessage(DEFAULT_VALUE_LBL)?></td>
        <td>
            <? //IE 8 throws an error in JS if you attempt to use metaData.default ?>
            <%= metaData["default"] %>
        </td>
    </tr>
    <% if(objectType !== 'Answer'){%>
        <tr>
            <td><?=Config::getMessage(READ_ONLY_LBL)?></td>
            <td><%= readOnly %></td>
        </tr>
        <tr>
            <td><?=Config::getMessage(REQUIRED_LBL)?></td>
            <td><%= required %></td>
        </tr>
        <tr>
            <td><?=Config::getMessage(WRITE_ONLY_LBL)?></td>
            <td><%= (metaData.is_write_only === true) ? '<?=Config::getMessage(TRUE_LBL)?>' : '<?=Config::getMessage(FALSE_LBL)?>' %></td>
        </tr>
        <tr>
            <td><?=Config::getMessage(CONSTRAINTS_LBL)?></td>
            <td>
                <ul>
                <% for (var i in metaData.constraints) { %>
                    <% if (constraintLabels[metaData.constraints[i].kind]) { %>
                    <li><%= constraintLabels[metaData.constraints[i].kind] %> <%= metaData.constraints[i].value %></li>
                    <% } %>
                <% } %>
                </ul>
            </td>
        </tr>
    <% } %>

    <% if (namedValues && namedValues.length > 0) { %>
    <tr id="namedValues">
        <td><?=Config::getMessage(NAMED_VALUES_LBL)?></td>
        <td>
            <div id="namedValuesOuterContainer">
                <? /* intentially no translations for ID and LookupName as they are API properties */ ?>
                <div class="namedValueHeaderRow">
                    <div class="namedValueHeaderId">ID</div>
                    <div class="namedValueHeaderLookupName">LookupName</div>
                </div><br />
                <div id="namedValuesInnerContainer">
                    <table id="namedValuesTable">
                    <tbody>
                    <% for (var i in namedValues) { %>
                        <tr>
                            <td><%= namedValues[i].ID %></td>
                            <td><%= escapeHtml(namedValues[i].LookupName) %></td>
                        </tr>
                    <% } %>
                    </tbody>
                    </table>
                </div>
            </div>
        </td>
    </tr>
    <% } %>

</table>
</script>
