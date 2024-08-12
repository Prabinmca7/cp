<?use RightNow\Utils\Config;?>

<div class="explorerContainer" id="explorer">
    <h2><?= Config::getMessage(CONNECT_OBJECT_EXPLORER_LBL) ?></h2>
    <div class="controls">
        <button id="execute" title="<?=Config::getMessage(RCT_EXECUTE_CMD);?>"><i class="fa fa-bolt fa-lg buttonIcon"></i> <?=Config::getMessage(RCT_EXECUTE_CMD);?></button>
        <button id="clear" title="<?=Config::getMessage(CLEAR_CMD)?>"><i class="fa fa-eraser fa-lg buttonIcon"></i> <?=Config::getMessage(CLEAR_CMD);?></button>
        <button id="export" title="<?=Config::getMessage(EXPORT_AS_CSV_CMD)?>"><i class="fa fa-share-square-o fa-lg buttonIcon"></i> <?=Config::getMessage(EXPORT_CMD);?></button>
        <div class="queryHistory">
            <button aria-haspopup="true" title="<?=Config::getMessage(HISTORY_LBL);?>"><i class="fa fa-clock-o fa-lg buttonIcon"></i> <?=Config::getMessage(HISTORY_LBL);?></button>
            <div>
                <ul></ul>
            </div>
        </div>
        <button id="objects" title="<?=Config::getMessage(VIEW_PRIMARY_OBJECTS_CMD)?>"><i class="fa fa-list-alt fa-lg buttonIcon"></i> <?=Config::getMessage(OBJECTS_LBL);?></button>
        <button id="settings" title="<?=Config::getMessage(SETTINGS_LBL)?>"><i class="fa fa-cogs fa-lg buttonIcon"></i> <span class="screenreader"><?=Config::getMessage(SETTINGS_LBL)?></span></button>
        <button id="help" title="<?=Config::getMessage(HELP_HDG)?>"><i class="fa fa-question-circle fa-lg buttonIcon"></i> <span class="screenreader"><?=Config::getMessage(HELP_HDG)?></span></button>
    </div>
    <span class="screenreader"><?=sprintf(Config::getMessage(PRESS_PCT_S_KEY_REM_FOCUS_EDITOR_LBL), "Escape")?></span>
    <div id="queryTabs"></div>
</div>

<script type="text/template" id="editorView">
    <div id="query<%= this.index %>">
        <label id="editorLabel<%= this.index %>" class="screenreader"><?=Config::getMessage(QUERY_EDITOR_LBL)?></label>
        <div id="editor<%= this.index %>" class="editor"></div>
        <div class="descWarning hide error">
            <?=Config::getMessage(NOTE_DATA_DATA_RET_NATIVE_DESCRIBE_MSG);?>
        </div>
        <div class="showWarning hide error">
            <?=Config::getMessage(NOTE_QUERY_NATIVELY_SUPP_ROQL_CONN_MSG);?>
        </div>
        <div class="actionContainer">
            <div class="paginationButtons">
                <button id="previousPage<%= this.index %>" class="hidden"><i class="fa fa-arrow-left buttonIcon"></i><?=Config::getMessage(PREVIOUS_LBL);?></button>
                <button id="nextPage<%= this.index %>" class="hidden"><?=Config::getMessage(NEXT_CMD);?><i class="fa fa-arrow-right buttonIcon"></i></button>
            </div>
            <span id="caption<%= this.index %>"></span>
        </div>
        <div class="resultsContainer">
            <div id="resultsLoading<%= this.index %>" class="resultsLoading"></div>
            <div id="results<%= this.index %>" class="results"></div>
        </div>
    </div>
</script>

<script id="helpDialogTemplate" type="text/x-yui3-template">
    <div id="helpDialog" class="yui-pe-content">
        <div class="helpContainer">
            <p>
                <?=sprintf(Config::getMessage(FLLOWING_QUERY_OPS_SUPP_QUERIES_MSG), "Ctrl + Enter", "Command + Enter", "Escape");?>
            </p>

            <h4>SHOW OBJECTS</h4>
            <p>
                <?=sprintf(Config::getMessage(ISSUING_QUERY_PCT_S_RT_L_AVAIL_STD_MSG), "<code>SHOW OBJECTS</code>", '<span class="warning">', '</span>');?>
            </p>

            <h4>DESC <i>&lt;<?=Config::getMessage(OBJCT_NAME_LBL);?>&gt;</i></h4>
            <p>
                <?=sprintf(Config::getMessage(ISSUING_QUERY_PCT_S_RET_L_AVAIL_STD_LBL), "<code>SHOW OBJECTS</code>", '<span class="warning">', '</span>');?>
            </p>

            <h4>SELECT</h4>
            <p>
                <?=Config::getMessage(ROQL_SUPPORTS_TYPES_SELECTION_OPS_MSG);?>
            </p>
            <p>
                <?=sprintf(Config::getMessage(COL_ROW_REC_RES_CONT_LINK_OBJECT_MSG), $documentationVersion);?>
            </p>

        </div>
    </div>
</script>

<script id="settingsDialogTemplate" type="text/x-yui3-template">
    <div id="settingsDialog" class="yui-pe-content">
        <div class="settingsContainer">
            <section>
                <h3><label for="defaultQueryLimit"><?=Config::getMessage(DEFAULT_QUERY_LIMIT_LBL);?></label></h3>
                <span><?=Config::getMessage(DEF_RES_RET_QUERY_DOESNT_MSG);?></span>
                <div>
                    <input id="defaultQueryLimit" type="number" min='1' max='10000'/>
                </div>
            </section>
            <section>
                <h3><label for="editorTheme"><?=Config::getMessage(EDITOR_THEME_LBL);?></label></h3>
                <span><?=Config::getMessage(DEFAULT_THEME_USE_EDITOR_MSG);?></span>
                <div>
                    <select id="editorTheme">
                        <optgroup label="<?=Config::getMessage(LIGHT_LBL);?>">
                            <option value='default' selected>default</option>
                            <option value="3024-day">3024-day</option>
                            <option value="base16-light">base16-light</option>
                            <option value='eclipse'>eclipse</option>
                            <option value='neat'>neat</option>
                            <option value='paraiso-light'>paraiso-light</option>
                            <option value='solarized light'>solarized light</option>
                            <option value='xq-light'>xq-light</option>
                        </optgroup>
                        <optgroup label="<?=Config::getMessage(DARK_LBL);?>">
                            <option value="3024-night">3024-night</option>
                            <option value="base16-dark">base16-dark</option>
                            <option value='ambiance'>ambiance</option>
                            <option value='blackboard'>blackboard</option>
                            <option value='cobalt'>cobalt</option>
                            <option value='erlang-dark'>erlang-dark</option>
                            <option value='lesser-dark'>lesser-dark</option>
                            <option value='midnight'>midnight</option>
                            <option value='monokai'>monokai</option>
                            <option value='night'>night</option>
                            <option value='paraiso-dark'>paraiso-dark</option>
                            <option value='rubyblue'>rubyblue</option>
                            <option value='solarized dark'>solarized dark</option>
                            <option value='tomorrow-night-eighties'>tomorrow-night-eighties</option>
                            <option value='the-matrix'>the-matrix</option>
                            <option value='twilight'>twilight</option>
                            <option value='vibrant-ink'>vibrant-ink</option>
                            <option value='xq-dark'>xq-dark</option>
                        </optgroup>
                    </select>
                </div>
            </section>
            <section>
                <h3><?=Config::getMessage(HISTORY_LBL);?></h3>
                <span><?=Config::getMessage(CLEAR_YOUR_QUERY_HISTORY_CMD);?></span>
                <div>
                    <button id="clearQueryHistory"><?=Config::getMessage(CLEAR_HISTORY_CMD);?></button>
                </div>
            </section>
        </div>
    </div>
</script>

<script>
var messages = <?= json_encode(array(
        'clearHistory'      => Config::getMessage(CLEAR_HISTORY_CMD),
        'close'             => Config::getMessage(CLOSE_CMD),
        'error'             => Config::getMessage(ERROR_MSG),
        'genericError'      => Config::getMessage(AN_ERROR_WAS_ENCOUNTERED_MSG),
        'help'              => Config::getMessage(HELP_HDG),
        'invalidFieldError' => Config::getMessage(ERR_INV_CONN_FLD_ID_SPEC_COLON_LBL),
        'metaHeaderName'    => Config::getMessage(META_DATA_FOR_LBL),
        'metaHeaderID'      => Config::getMessage(DASH_ID_LBL),
        'newQuery'          => Config::getMessage(NEW_QUERY_LBL),
        'noQueryHistory'    => Config::getMessage(NO_QUERY_HISTORY_LBL),
        'noQuery'           => Config::getMessage(PLEASE_ENTER_A_QUERY_MSG),
        'OK'                => Config::getMessage(OK_LBL),
        'primaryObjects'    => Config::getMessage(CONNECT_PRIMARY_OBJECTS_LBL),
        'settings'          => Config::getMessage(CONNECT_OBJECT_EXPLORER_SETTINGS_LBL),
        'noResultsToExport' => Config::getMessage(THERE_ARE_NO_RESULTS_TO_EXPORT_MSG),
        'noResults'         => Config::getMessage(RESULTS_QUERY_LBL),
        'add'               => Config::getMessage(ADD_CMD),
        'summary'           => Config::getMessage(QUERY_RESULTS_LBL),
    ));?>,
    fieldData = <?= json_encode(array(
        'fieldName' => isset($fieldName) ? $fieldName : null,
        'objectID' => isset($objectID) ? $objectID : null,
    ));?>;

var primaryConnectObjects = "<?=strtolower(implode(" ", \RightNow\Internal\Libraries\ConnectExplorer::getPrimaryClasses(true)));?>";

</script>
