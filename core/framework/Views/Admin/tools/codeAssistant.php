<? use \RightNow\Utils\Config; ?>
<div id="container">
    <h2 class="title"><?=Config::getMessage(IMPROVE_YOUR_CUSTOM_CODE_LBL)?></h2>
    <p>
        <?=Config::getMessage(CODE_ASST_INTF_INTENDED_HELP_MSG)?>
        <?=sprintf(Config::getMessage(BACKUPS_CR_CODE_ASST_FND_PCT_S_PG_MSG), '<a href="/ci/admin/assistant/backups" target="_blank">' . Config::getMessage(CODE_ASSISTANT_BACKUPS_LBL) . '</a>');?>
    </p>
</div>

<script id="selectVersions" type="text/x-yui3-template">
    <div class="version">
        <p><%==data.instructions%></p>
        <div class="row">
            <button class="continue"><?=Config::getMessage(NEXT_STEP_LBL)?> →</button>
        </div>
    </div>
</script>

<script id="versionDropdown" type="text/x-yui-template">
    <label class="screenreader" for="<%=data.mode%>"><%=data.modeLabel%></label>
    <select id="<%=data.mode%>">
        <% Y.Array.each(data.versions, function(version) { %>
            <option <%= (data.selectedVersion === version) ? 'selected' : '' %>><%=version%></option>
        <% }); %>
    </select>
</script>

<script id="selectionContent" type="text/x-yui3-template">
    <div class="selection">
        <p><?=Config::getMessage(L_OPS_PERFORMED_SEL_OP_VIEW_DESC_MSG)?></p>
            <fieldset>
                <legend class="screenreader"><?=Config::getMessage(SELECT_AN_OPERATION_LBL)?></legend>
                <ul class="collapsible-list enabled">
                    <%  var first = true;
                        Y.Array.each(data.operations, function(operation) { %>
                        <li class="item" data-id="<%=operation.id%>" data-type="<%=operation.type%>">
                            <div class="header <%= (first) ? '' : 'corner' %>">
                                <input name="operation" id="operation_<%=operation.id%>" type="radio" <%= (first) ? 'checked' : '' %> />
                                <label for="operation_<%=operation.id%>"><%=operation.title%></label>
                            </div>
                            <div class="description <%= (first) ? 'expanded' : '' %>"><%=operation.description%></div>
                        </li>
                    <%  first = false;
                        }); %>
                </ul>
            </fieldset>
        <div class="row"><button class="continue"><?=Config::getMessage(CONTINUE_CMD)?> →</button></div>
    </div>
</script>

<script id="suggestionContent" type="text/x-yui3-template">
<div class="suggestion">
    <p><%==data.instructions%></p>
    <ul class="collapsible-list enabled">
        <% Y.Array.each(data.units, function(unit) { %>
            <li class="item" data-unit="<%=unit%>">
                <div class="header corner">
                    <button class="rescan"><span class="screenreader"><%=messages.rescanFor.replace('%s', unit)%></span><span class="fa fa-refresh"></span> <?=Config::getMessage(RESCAN_LBL)?></button>
                    <button class="expand"><span class="screenreader"><%=messages.toggleDescriptionFor.replace('%s', unit)%></span><span role="presentation" class="iconplusminus fa fa-plus"></span></button>
                    <div class="title"><%=unit%></div>
                </div>
                <div class="description"></div>
            </li>
        <% }); %>
    </ul>
    <div class="row">
        <a href="/ci/admin/"><?=Config::getMessage(HOME_LBL)?></a>
        <button class="reset"><?=Config::getMessage(START_OVER_LBL)?> →</button>
    </div>
</div>
</script>

<script id="conversionContent" type="text/x-yui3-template">
<div class="conversion">
    <p><%=data.instructions%></p>
    <div class="row">
        <span class="all"><input id="selectAll" type="checkbox"/><label for="selectAll"><?=Config::getMessage(RNW_SELECT_ALL_CMD);?></label></span>
    </div>
    <ul class="collapsible-list enabled">
        <% Y.Array.each(data.units, function(unit) { %>
            <li class="item" data-unit="<%=unit%>">
                <div class="header corner">
                    <div class="title">
                        <input id="unit_<%=unit%>" type="checkbox"/>
                        <label for="unit_<%=unit%>"><%=unit%></label>
                    </div>
                    <button class="expand"><span class="screenreader"><%=messages.toggleDescriptionFor.replace('%s', unit)%></span><span role="presentation" class="iconplusminus fa fa-plus"></span></button>
                </div>
                <div class="description"></div>
            </li>
            <% }); %>
        </ul>
        <div class="row"><button class="continue"><?=Config::getMessage(CONFIRM_CHANGES_LBL)?> →</button></div>
    </div>
</script>

<script id="messageList" type="text/x-yui3-template">
    <div class="list-highlight">
        <h4 class="title"><%=data.header%></h4>
        <ul class="message-list">
            <% Y.Array.each(data.messages, function(message) { %>
                <li><i class="<%=data.iconType%>"></i><div class="list-text"><%==message%></div></li>
            <% }); %>
        </ul>
    </div>
</script>

<script id="confirmation" type="text/x-yui3-template">
    <div class="confirmation">
        <% if(!Y.Object.isEmpty(data.successfulUnits)) { %>
            <p><%=data.successMessage || messages.passedConversion%></p>
            <ul class="collapsible-list enabled">
                <% Y.Object.each(data.successfulUnits, function(instructions, key) { %>
                    <li class="item">
                        <div class="header corner">
                            <a href="javascript:void(0)"><%= key %></a>
                            <span class="screenreader"><%=messages.toggleConfirmationFor.replace('%s', key)%></span><span role="presentation" class="iconplusminus fa fa-plus"></span>
                        </div>
                        <div class="description"></div>
                    </li>
                <% }); %>
            </ul>
        <% } %>

        <% if(data.postExecuteMessage) { %>
            <p class="note"><%==data.postExecuteMessage%></p>
        <% } %>

        <% if(!Y.Object.isEmpty(data.failedUnits)) { %>
            <p><%=data.failureMessage || messages.failedConversion%></p>
            <ul class="collapsible-list enabled">
                <% Y.Object.each(data.failedUnits, function(instructions, key) { %>
                    <li class="item">
                        <div class="header corner">
                            <a href="javascript:void(0)"><%= key %><i class="fa fa-exclamation-circle"></i></a>
                            <span class="screenreader"><%=messages.toggleConfirmationFor.replace('%s', key)%></span><span role="presentation" class="iconplusminus fa fa-plus"></span>
                        </div>
                        <div class="description"></div>
                    </li>
                <% }); %>
            </ul>
        <% } %>

        <div class="row">
            <a href="/ci/admin/overview"><?=Config::getMessage(HOME_LBL)?></a>
            <button class="reset"><?=Config::getMessage(START_OVER_LBL)?> →</button>
        </div>
    </div>
</script>

<script id="snippetTemplate" type="text/x-yui3-template">
    <div class="pretty">
        <div class="numbers" aria-hidden="true">
            <% Y.Array.each(data, function(object) { %>
                <div class="number"><%= object.lineNumber %></div>
            <% }); %>
        </div>
        <div class="source">
            <% Y.Array.each(data, function(object) { %>
                <% if (object.marked) { %>
                    <div class="line marked"><span class="screenreader"><%= messages.lineNumber + ' ' + object.lineNumber + ' ' + messages.markedAsNeedingChanges %></span><%= object.line %><a class="tooltipLink" href="<%= object.suggestionLink %>" target="_blank"> <sup class="fa fa-question-circle" title="<%= object.suggestionText %>"> <span class="screenreader" role="tooltip"><%= object.suggestionText %></span></sup></a></div>
                <% } else { %>
                    <div class="line"><span class="screenreader"><%= messages.lineNumber + ' ' + object.lineNumber %></span><%= object.line %></div>
                <% } %>
            <% }); %>
        </div>
    </div>
</script>

<script>
    var configuration = <?= json_encode(array(
            'developmentVersions' => $developmentVersions,
            'productionVersions' => $productionVersions
        )); ?>,
        messages = <?= json_encode(array(
            'backup' => Config::getMessage(BACKUP_CMD),
            'changedVersionWarning' => Config::getMessage(YOUVE_SEL_CUST_VERSION_RANGE_CAUSE_MSG),
            'codeSnippets' => Config::getMessage(FOLLOWING_CODE_SNIPPETS_HIGHLIGHTED_MSG),
            'completionInstructionHeader' => Config::getMessage(FOLLOWING_OPERATIONS_PERFORMED_MSG),
            'confirmationTitle' => Config::getMessage(FNISH_UP_CMD),
            'confirmItem' => Config::getMessage(YOU_CONVERT_SELECTED_ITEM_MSG),
            'confirmItems' => Config::getMessage(YOU_CONVERT_SELECTED_ITEMS_MSG),
            'conversionError' => Config::getMessage(PLEASE_SELECT_AN_ITEM_TO_CONVERT_MSG),
            'conversionInstructionHeader' => Config::getMessage(FOLLOWING_OPS_PERFORMED_MSG),
            'conversionTitle' => Config::getMessage(SELECT_THE_ITEMS_TO_CONVERT_LBL),
            'createDirectory' => Config::getMessage(DIRECTORY_PCT_S_WILL_BE_CREATED_MSG),
            'createDirectoryConfirmation' => Config::getMessage(THE_DIRECTORY_PCT_S_WAS_CREATED_MSG),
            'createFile' => Config::getMessage(THE_FILE_PCT_S_WILL_BE_CREATED_MSG),
            'createFileConfirmation' => Config::getMessage(THE_FILE_PCT_S_WAS_CREATED_MSG),
            'deleteFile' => Config::getMessage(THE_FILE_PCT_S_WILL_BE_DELETED_MSG),
            'deleteFileConfirmation' => Config::getMessage(FILE_PCT_S_DEL_BACKUP_CR_PCT_S_MSG),
            'diff' => Config::getMessage(DIFF_LBL),
            'errorHeader' => Config::getMessage(OP_PERFORMED_ITEM_ENC_FOLLOWING_MSG),
            'failedConversion' => Config::getMessage(FOLLOWING_ITEMS_FAILED_CONVERSION_MSG),
            'from' => Config::getMessage(FROM_LBL),
            'lineNumber' => Config::getMessage(LINE_NUMBER_UC_LBL),
            'loading' => Config::getMessage(LOADING_LBL),
            'markedAsNeedingChanges' => Config::getMessage(MARKED_AS_NEEDING_CHANGES_LBL),
            'message' => Config::getMessage(MESSAGES_LBL),
            'messageHeader' => Config::getMessage(ITEM_ENC_FOLLOWING_SPECIAL_REQUIRE_MSG),
            'modifyFile' => Config::getMessage(THE_FILE_PCT_S_WILL_BE_MODIFIED_MSG),
            'modifyFileConfirmation' => Config::getMessage(FILE_PCT_S_MODIFIED_BACKUP_CR_PCT_MSG),
            'moveDirectory' => Config::getMessage(DIRECTORY_PCT_S_MOVED_PCT_S_MSG),
            'moveDirectoryConfirmation' => Config::getMessage(DIRECTORY_PCT_S_MOVED_PCT_S_BACKUP_MSG),
            'moveFile' => Config::getMessage(FILE_PCT_S_WILL_BE_MOVED_PCT_S_MSG),
            'moveFileConfirmation' => Config::getMessage(FILE_PCT_S_MOVED_PCT_S_BACKUP_CR_MSG),
            'noTitle' => Config::getMessage(NO_TTLE_LBL),
            'passedConversion' => Config::getMessage(FOLLOWING_ITEMS_SUCC_CONVERTED_MSG),
            'path' => Config::getMessage(PATH_LBL),
            'preVersionThree' => Config::getMessage(PRE_3_0_LBL),
            'rescanFor' => Config::getMessage(RESCAN_CODE_FOR_PCT_S_LBL),
            'selectError' => Config::getMessage(ITEM_MUST_BE_SELECTED_CONTINUE_MSG),
            'selectionTitle' => Config::getMessage(SELECT_AN_OPERATION_FROM_THE_LIST_LBL),
            'selectVersionMessage' => Config::getMessage(BASED_SITE_CONFIG_PRODUCTION_MODE_MSG),
            'selectVersions' => Config::getMessage(SELECT_YOUR_VERSIONS_LBL),
            'suggestionInstructionHeader' => Config::getMessage(ITEM_HAD_FOLLOWING_SUGGESTIONS_MSG),
            'suggestionTitle' => Config::getMessage(SELECT_OPTION_VIEW_SUGGESTIONS_LBL),
            'to' => Config::getMessage(TO_MSG),
            'toggleConfirmationFor' => Config::getMessage(TOGGLE_CONFIRMATION_FOR_PCT_S_LBL),
            'toggleDescriptionFor' => Config::getMessage(TOGGLE_DESCRIPTION_FOR_PCT_S_LBL),
            'unexpectedError' => Config::getMessage(ERROR_REQUEST_PLEASE_TRY_MSG),
            'unrecognizedInstruction' => Config::getMessage(RECEIVED_INV_INSTRUCTION_SERVER_MSG),
            'versionError' => Config::getMessage(DEVELOPMENT_VERSION_EQ_PRODUCTION_MSG),
        )); ?>;
</script>
