<? use RightNow\Utils\Config; ?>
<div class="main left">
    <h2><?= Config::getMessage(BUILD_A_NEW_WIDGET_LBL) ?></h2>
    <section class="step one">
        <h3><span class="stepNumber">1.</span><?= Config::getMessage(WHAT_WOULD_YOU_LIKE_TO_DO_TODAY_MSG) ?></h3>
        <div class="content">
            <div class="link"><a id="allnew" href="javascript:void(0)" data-type="new" role="button"><?= Config::getMessage(CREATE_BRAND_NEW_WIDGET_SCRATCH_CMD) ?></a></div>
            <div class="link"><a href="javascript:void(0)" data-type="extend" role="button"><?= Config::getMessage(EXTEND_FUNCTIONALITY_EXISTING_LBL) ?></a></div>
        </div>
    </section>

    <section class="step two hide">
        <h3><span class="stepNumber">2.</span><?= Config::getMessage(NAMING_LBL) ?></h3>
        <div class="content">
            <div class="extend hide rel">
                <form class="center">
                    <label for="toExtend"><?= Config::getMessage(WHICH_WIDGET_SHALL_IT_EXTEND_FROM_MSG) ?></label>
                    <input type="text" id="toExtend" placeholder="<?= Config::getMessage(CHOOSE_A_WIDGET_ELLIPSIS_MSG) ?>"/>
                    <span class="protip tooltip hide" data-title="<?= Config::getMessage(REMEM_WIDGETS_LOOK_FEEL_MSG) ?>"></span>
                </form>
            </div>
            <div class="step new hide">
                <form class="center">
                    <div class="alignCenter">
                        <label for="name"><?= Config::getMessage(WHAT_IS_ITS_NAME_MSG) ?></label>
                        <input type="text" placeholder="<?= Config::getMessage(WIDGET_NAME_LBL) ?>" id="name"/>
                    </div>
                    <label for="folder"><?= Config::getMessage(AND_ITS_PARENT_FOLDER_MSG) ?></label>
                    <div class="alignCenter">
                        <span class="placeholder pre">custom / </span>
                        <input type="text" placeholder="<?= Config::getMessage(WIDGET_FOLDER_LBL) ?>" id="folder"/>
                        <span id="widgetPlaceholder" class="placeholder post"> / WidgetName </span>
                    </div>
                    <button type="button" class="hide continue"><?= Config::getMessage(CONTINUE_CMD) ?></button>
                </form>
            </div>
        </div>
    </section>

    <section class="step three hide">
        <h3><span class="stepNumber">3.</span><?= Config::getMessage(COMPONENTS_LBL) ?></h3>
        <div class="content">
            <div class="module">
                <h4 class="heading">PHP</h4>
                <form>
                    <div class="row">
                        <div class="legend left" data-when="new">
                            <?= Config::getMessage(DOES_WIDGET_HAVE_CONTROLLER_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(CONTROLLER_INITIALIZES_PROCESSES_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(CONTROLLER_INITIALIZES_PROCESSES_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <div class="legend left hide" data-when="extending">
                            <?= Config::getMessage(WIDGET_CONTROLLER_OWN_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(EXTENDING_WIDGET_ISNT_REQD_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(EXTENDING_WIDGET_ISNT_REQD_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <fieldset class="left" data-for="php">
                            <label for="hasPhp"><?= Config::getMessage(YES_LBL) ?></label>
                            <input data-subchoice="true" type="radio" name="php" id="hasPhp" value="1"/>
                            <label for="withoutPhp"><?= Config::getMessage(NO_LBL) ?></label>
                            <input data-subchoice="true" type="radio" name="php" id="withoutPhp" checked value="0"/>
                        </fieldset>
                    </div>
                    <div class="row subchoice">
                        <div class="legend left" data-when="new">
                            <?= Config::getMessage(DOING_AJAX_HANDLING_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(AJAX_HANDLING_FUNCTIONALITY_BUILT_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(AJAX_HANDLING_FUNCTIONALITY_BUILT_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <div class="legend left hide" data-when="extending">
                            <?= Config::getMessage(DOING_OWN_AJAX_HANDLING_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(WIDGET_AUTO_INHERITS_AJAX_HANDLING_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(WIDGET_AUTO_INHERITS_AJAX_HANDLING_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <fieldset class="left" data-for="ajax">
                            <label for="hasAjax"><?= Config::getMessage(YES_LBL) ?></label>
                            <input type="radio" disabled name="ajax" id="hasAjax" value="1"/>
                            <label for="withoutAjax"><?= Config::getMessage(NO_LBL) ?></label>
                            <input type="radio" disabled name="ajax" id="withoutAjax" checked value="0"/>
                        </fieldset>
                    </div>
                </form>
            </div>
            <div class="module">
                <h4 class="heading"><?= Config::getMessage(VIEW_CMD) ?></h4>
                <form>
                    <div class="row">
                        <div class="legend left" data-when="new">
                            <?= Config::getMessage(DOES_THIS_WIDGET_HAVE_A_VIEW_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(PHP_DATA_CONTROLLER_DISPLAYED_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(PHP_DATA_CONTROLLER_DISPLAYED_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <div class="legend left hide" data-when="extending">
                            <?= Config::getMessage(WIDGET_MODIFY_PARENT_WIDGETS_VIEW_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(BLOCKS_CONTENT_PARENT_VIEW_MODIFIED_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(BLOCKS_CONTENT_PARENT_VIEW_MODIFIED_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <fieldset class="left" data-for="view">
                            <label for="hasView"><?= Config::getMessage(YES_LBL) ?></label>
                            <input type="radio" name="view" id="hasView" value="1"/>
                            <label for="withoutView"><?= Config::getMessage(NO_LBL) ?></label>
                            <input type="radio" name="view" id="withoutView" checked value="0"/>
                        </fieldset>
                    </div>
                    <div class="row subchoice hide" data-when="extending-view">
                        <fieldset data-for="overrideView">
                            <label for="withoutOverride">
                                <input type="radio" disabled name="parentView" id="withoutOverride" checked value="0"/>
                                <?= Config::getMessage(EXTEND_THE_VIEW_LBL) ?>
                                <a class="tooltipLink" href="javascript: void(0)">
                                    <sup title="<?= Config::getMessage(INSERT_BLOCKS_CONTENT_PARENT_VIEW_CMD) ?>">
                                        <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                        <span class="screenreader" role="tooltip"><?= Config::getMessage(INSERT_BLOCKS_CONTENT_PARENT_VIEW_CMD) ?></span>
                                    </sup>
                                </a>
                                <em><?= Config::getMessage(RECOMMENDED_LBL) ?></em>
                            </label>
                            <br>
                            <label for="hasOverride">
                                <input type="radio" disabled name="parentView" id="hasOverride" value="1"/>
                                <?= Config::getMessage(OVERRIDE_THE_VIEW_LBL) ?>
                                <a class="tooltipLink" href="javascript: void(0)">
                                    <sup title="<?= Config::getMessage(VIEW_PARENT_VIEW_PARENT_WIDGET_MSG) ?>">
                                        <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                        <span class="screenreader" role="tooltip"><?= Config::getMessage(VIEW_PARENT_VIEW_PARENT_WIDGET_MSG) ?></span>
                                    </sup>
                                </a>
                            </label>
                        </fieldset>
                    </div>
                    <div class="row hide" data-when="extending">
                        <div class="legend left">
                            <?= Config::getMessage(INCLUDE_THE_PARENT_WIDGETS_CSS_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(WIDGET_AUTO_DEF_LOOK_FEEL_PARENT_LBL) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(WIDGET_AUTO_DEF_LOOK_FEEL_PARENT_LBL) ?></span>
                                </sup>
                            </a>
                        </div>
                        <fieldset class="left" data-for="parentCss">
                            <label for="hasParentCss"><?= Config::getMessage(YES_LBL) ?></label>
                            <input type="radio" name="css" id="hasParentCss" value="1"/>
                            <label for="withoutParentCss"><?= Config::getMessage(NO_LBL) ?></label>
                            <input type="radio" name="css" id="withoutParentCss" checked value="0"/>
                        </fieldset>
                    </div>
                </form>
            </div>
            <div class="module">
                <h4 class="heading">JavaScript</h4>
                <form>
                    <div class="row">
                        <div class="legend left" data-when="new">
                            <?= Config::getMessage(DOES_THIS_WIDGET_HAVE_JAVASCRIPT_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(DYNAMIC_BEHAVIOR_CONTENT_WIDGET_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(DYNAMIC_BEHAVIOR_CONTENT_WIDGET_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <div class="legend left hide" data-when="extending">
                            <?= Config::getMessage(WIDGET_OWN_JAVASCRIPT_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(WIDGET_EXTEND_JAVASCRIPT_BEHAVIOR_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(WIDGET_EXTEND_JAVASCRIPT_BEHAVIOR_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <fieldset class="left" data-for="js">
                            <label for="hasJS"><?= Config::getMessage(YES_LBL) ?></label>
                            <input data-subchoice="true" type="radio" name="js" id="hasJS" value="1"/>
                            <label for="withoutJS"><?= Config::getMessage(NO_LBL) ?></label>
                            <input data-subchoice="true" type="radio" name="js" id="withoutJS" checked value="0"/>
                        </fieldset>
                    </div>
                    <div class="row">
                        <div class="legend">
                            <?= Config::getMessage(YUI_MODULES_UC_LBL) ?>
                        </div>
                        <div data-for="yui">
                            <p class="explain"><? printf(Config::getMessage(ADD_PCT_S_INCLUDED_PG_ATTACHED_YUI_MSG), "<a href='/ci/admin/docs/widgets/info#yuiModules' target='_blank'>" . Config::getMessage(YUI_MODULES_LBL) . "</a>") ?></p>
                            <a id="addYUIModule" class="disabled" href="javascript:void(0);" role="button"><?= Config::getMessage(ADD_MODULE_CMD) ?></a>
                        </div>
                    </div>
                    <div class="row subchoice">
                        <div class="legend left" data-when="new">
                            <?= Config::getMessage(JAVASCRIPT_TEMPLATES_TOO_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(HTML_VIEWS_WIDGET_JAVASCRIPT_RENDER_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(HTML_VIEWS_WIDGET_JAVASCRIPT_RENDER_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <div class="legend left hide" data-when="extending">
                            <?= Config::getMessage(JAVASCRIPT_TEMPLATES_TOO_MSG) ?>
                            <a class="tooltipLink" href="javascript: void(0)">
                                <sup title="<?= Config::getMessage(BLOCKS_CONTENT_PARENT_JAVASCRIPT_MSG) ?>">
                                    <i class="fa fa-question-circle" role="presentation" aria-hidden="true"></i>
                                    <span class="screenreader" role="tooltip"><?= Config::getMessage(BLOCKS_CONTENT_PARENT_JAVASCRIPT_MSG) ?></span>
                                </sup>
                            </a>
                        </div>
                        <fieldset class="left" data-for="jsView">
                            <label for="hasJSView"><?= Config::getMessage(YES_LBL) ?></label>
                            <input type="radio" disabled name="jsView" id="hasJSView" value="1"/>
                            <label for="withoutJSView"><?= Config::getMessage(NO_LBL) ?></label>
                            <input type="radio" disabled name="jsView" id="withoutJSView" checked value="0"/>
                        </fieldset>
                    </div>
                </form>
            </div>
            <form class="center">
                <button type="button" class="hide continue"><?= Config::getMessage(CONTINUE_CMD) ?></button>
            </form>
        </div>
    </section>

    <section class="step four hide">
        <h3><span class="stepNumber">4.</span><?= Config::getMessage(ATTRIBUTES_LBL) ?></h3>
        <div class="content">
            <div class="attributes"></div>
            <form class="center">
                <button id="addAttribute"><?= Config::getMessage(ADD_AN_ATTRIBUTE_CMD) ?></button>
            </form>
            <form class="center">
                <button type="button" class="continue"><?= Config::getMessage(CONTINUE_CMD) ?></button>
            </form>
        </div>
    </section>

    <section class="step five hide">
        <div class="content">
            <h3>
                <a href="javascript:void(0);" role="button">
                    <span class="stepToggle">►</span>
                    <?= Config::getMessage(ADD_ADDITIONAL_DETAILS_CMD) ?>
                    <span class="aside"><?= Config::getMessage(OPT_LBL) ?></span>
                </a>
            </h3>
            <div class="hide">
                <div class="documentation">
                    <h4 class="heading"><?= Config::getMessage(DOCUMENTATION_LBL) ?></h4>
                    <div class="row">
                        <label for="description"><?= Config::getMessage(WIDGET_DESCRIPTION_LBL) ?></label>
                        <textarea id="description" cols="30" rows="4" placeholder="<?= Config::getMessage(ADD_A_DESCRIPTION_CMD) ?>"></textarea>
                    </div>
                    <div class="row">
                        <label>
                            <?= Config::getMessage(URL_PARAMETERS_LBL) ?>
                            <sup title="<?= Config::getMessage(PROV_DOCUMENTATION_URL_PARMS_LBL) ?>"></sup>
                            <a id="addUrlParam" href="javascript:void(0);" role="button"><?= Config::getMessage(ADD_A_URL_PARAMETER_CMD) ?></a>
                        </label>
                        <div class="urlParams"></div>
                    </div>
                </div>
                <div class="compatibility">
                    <h4 class="heading"><?= Config::getMessage(COMPATIBILITY_LBL) ?></h4>
                    <div class="row">
                        <p class="explain"><?= Config::getMessage(SPEC_WIDGET_SPECIFIC_RN_JAVASCRIPT_MSG) ?></p>
                        <label for="jsModule-standard">
                            <input type="checkbox" id="jsModule-standard" name="jsModule" data-for="standard" checked/>
                            <?= Config::getMessage(STANDARD_DESKTOP_JAVASCRIPT_MODULE_LBL) ?>
                        </label>
                        <label for="jsModule-mobile">
                            <input type="checkbox" id="jsModule-mobile" name="jsModule" data-for="mobile" checked/>
                            <?= Config::getMessage(MOBILE_JAVASCRIPT_MODULE_LBL) ?>
                        </label>
                        <label for="jsModule-none">
                            <input type="checkbox" id="jsModule-none" name="jsModule" data-for="none"/>
                            <?= Config::getMessage(FRAMEWORK_JAVASCRIPT_REQUIRED_MSG) ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <div class="final">
            <div class="title">☞ <?= Config::getMessage(FINISH_UP_CMD) ?></div>
            <form class="center">
                <button type="button" id="finishIt"><?= Config::getMessage(CREATE_WIDGET_CMD) ?></button>
            </form>
        </div>
    </section>

    <section class="step last hide">
        <h3><?= Config::getMessage(GENERATED_WIDGET_LBL) ?></h3>
        <div class="content bigwait">
        </div>
    </section>
</div>

<aside class="sidebar module right" role="complementary">
    <h3 class="heading"><?= Config::getMessage(SUMMARY_LBL) ?></h3>
    <ol class="content">
        <li class="one hide"></li>
        <li class="two hide"></li>
        <li class="three hide"></li>
        <li class="four hide"></li>
        <li class="five hide"></li>
    </ol>
</aside>

<script id="step-three-module" type="text/x-yui3-template">
    <div class="moduleContainer">
        <label class="screenreader"><?= Config::getMessage(MODULE_NAME_LBL) ?></label>
        <input type="text" placeholder="<?= Config::getMessage(MODULE_NAME_LBL) ?>"/>
        <a class="removeModule" role="button" href="javascript:void(0);" title="<?= Config::getMessage(REMOVE_CMD) ?>">
            <i class="fa fa-times" role="presentation" aria-hidden="true"></i>
            <span class="screenreader" role="tooltip"><?= Config::getMessage(REMOVE_CMD) ?></span>
        </a>
    </div>
</script>

<script id="step-four-attribute" type="text/x-yui3-template">
    <div class="attribute module" data-index="{attributeIndex}">
        <a href="javascript:void(0)" class="remove" role="button" title="<?= Config::getMessage(REMOVE_CMD) ?>">
            <i class="fa fa-times" role="presentation" aria-hidden="true"></i>
            <span class="screenreader" role="tooltip"><?= Config::getMessage(REMOVE_CMD) ?></span>
        </a>
        <div class="heading">
            <label class="screenreader" for="attribute-name-{attributeIndex}"><?= Config::getMessage(NAME_LBL) ?></label>
            <input data-name="name" id="attribute-name-{attributeIndex}" placeholder="<?= Config::getMessage(ATTRIBUTE_NAME_UC_LBL) ?>" type="text" value="{name}"/>
        </div>
        <div class="row">
            <label for="attribute-type-{attributeIndex}">
                <?= Config::getMessage(TYPE_LBL) ?>
                <select data-name="type" id="attribute-type-{attributeIndex}">
                    <option value="string"><?= Config::getMessage(STRING_LBL) ?></option>
                    <option value="boolean"><?= Config::getMessage(BOOLEAN_LBL) ?></option>
                    <option value="ajax"><?= Config::getMessage(AJAX_ENDPOINT_LBL) ?></option>
                    <option value="option"><?= Config::getMessage(OPTION_LBL) ?></option>
                    <option value="multioption"><?= Config::getMessage(MULTI_OPT_LBL) ?></option>
                    <option value="filepath"><?= Config::getMessage(FILE_PATH_LBL) ?></option>
                    <option value="int"><?= Config::getMessage(INTEGER_LBL) ?></option>
                </select>
            </label>
        </div>
        <div class="row">
            <label for="attribute-description-{attributeIndex}">
                <?= Config::getMessage(DESCRIPTION_LBL) ?>
                <textarea data-name="description" id="attribute-description-{attributeIndex}" placeholder="<?= Config::getMessage(ADD_A_DESCRIPTION_CMD) ?>" rows="4">{description}</textarea>
            </label>
        </div>
        <div class="options row hide">
            <label>
                <?= Config::getMessage(OPTIONS_LBL) ?>
                <a href="javascript:void(0)" class="addOption"><?= Config::getMessage(ADD_OPTION_CMD) ?></a>
            </label>
        </div>
        <div class="default row">
            <label for="attribute-default-{attributeIndex}">
                <?= Config::getMessage(DEFAULT_VALUE_UC_LBL) ?>
                <input data-name="default" id="attribute-default-{attributeIndex}" type="text" placeholder="<?= Config::getMessage(SET_A_DEFAULT_CMD) ?>" value="{defaultValue}"/>
            </label>
            <span id="booleanDefaultDescription-{attributeIndex}" class="hide inline normal"><?= Config::getMessage(FALSE_LBL) ?></span>
        </div>
        <div class="row">
            <label class="inline" for="attribute-required-{attributeIndex}">
                <input data-name="required" id="attribute-required-{attributeIndex}" type="checkbox"/>
                <span><?= Config::getMessage(THIS_ATTRIBUTE_IS_REQUIRED_MSG) ?></span>
            </label>
        </div>
    </div>
</script>

<script id="step-four-option" type="text/x-yui3-template">
    <div class="row">
        <a class="removeOption" role="button" href="javascript:void(0)">
            <i class="fa fa-times" role="presentation" aria-hidden="true"></i>
            <span class="screenreader" role="tooltip"><?= Config::getMessage(REMOVE_CMD) ?></span>
        </a>
        <label class="screenreader" for="attribute-option-{optionIndex}"><?= Config::getMessage(NEW_OPTION_LBL) ?></label>
        <input data-name="option" id="attribute-option-{optionIndex}" type="text" placeholder="<?= Config::getMessage(NEW_OPTION_LBL) ?>" value="{value}"/>

        <input type="{defaultType}" name="defaultValueOption-{attributeIndex}" id="attribute-option-default-{optionIndex}" {checked}>
        <label for="attribute-option-default-{optionIndex}"><?= Config::getMessage(DEFAULT_CMD) ?></label>
    </div>
</script>

<script id="step-four-validation" type="text/x-yui3-template">
    <div class="validation">{message}</div>
</script>

<script id="step-five-param" type="text/x-yui3-template">
    <div class="module urlParam">
         <a href="javascript:void(0)" role="button" class="remove" title="<?= Config::getMessage(REMOVE_CMD) ?>">
            <i class="fa fa-times" role="presentation" aria-hidden="true"></i>
            <span class="screenreader" role="tooltip"><?= Config::getMessage(REMOVE_CMD) ?></span>
        </a>
        <div class="heading">
            <label class="screenreader" for="url-key-{urlParamIndex}"><?= Config::getMessage(URL_PARAMETER_KEY_UC_LBL) ?></label>
            <input data-name="key" id="url-key-{urlParamIndex}" placeholder="<?= Config::getMessage(URL_PARAMETER_KEY_UC_LBL) ?>" type="text" value="{name}"/>
        </div>
        <div class="row">
            <label for="url-name-{urlParamIndex}">
                <?= Config::getMessage(NAME_LBL) ?>
                <input data-name="name" id="url-name-{urlParamIndex}" placeholder="<?= Config::getMessage(HUMAN_READABLE_NAME_LBL) ?>" type="text" value="{readable}"/>
            </label>
        </div>
        <div class="row">
            <label for="url-description-{urlParamIndex}">
                <?= Config::getMessage(DESCRIPTION_LBL) ?>
                <textarea data-name="description" id="url-description-{urlParamIndex}" placeholder="<?= Config::getMessage(ADD_A_DESCRIPTION_CMD) ?>" rows="4">{description}</textarea>
            </label>
        </div>
        <div class="row">
            <label for="url-example-{urlParamIndex}">
                <?= Config::getMessage(EXAMPLE_UC_LBL) ?>
                <input data-name="example" id="url-example-{urlParamIndex}" type="text" value="{example}"/>
            </label>
         </div>
    </div>
</script>

<script id="step-five-validation" type="text/x-yui3-template">
    <div class="validation"><?= Config::getMessage(INV_URL_PARAM_PARAMETER_CONT_MSG) ?></div>
</script>

<script id="step-last-list-item" type="text/x-yui3-template">
    <li>
        <a target="_blank" href="{link}" class="{className}">
            <span class="fileIcon">{type}</span>
            <span class="label">{label}</span>
        </a>
    </li>
</script>

<script>
var allWidgets = <?= json_encode($allWidgets) ?>,
    yuiPath = "<?= \RightNow\Utils\Url::getYUICodePath() ?>",
    messages = <?= json_encode(array(
    'activatedWidget'    => Config::getMessage(WIDGET_ACTIVATED_READY_DEVELOPMENT_MSG),
    'ajax'               => Config::getMessage(AJAX_HANDLER_LBL),
    'ajaxDescription'    => Config::getMessage(DEFAULT_AJAX_ENDPOINT_LBL),
    'attribute'          => Config::getMessage(PCT_S_ATTRIBUTE_LBL),
    'attributes'         => Config::getMessage(PCT_S_ATTRIBUTES_LBL),
    'base'               => Config::getMessage(BASE_CSS_LBL),
    'components'         => Config::getMessage(COMPONENTS_COLON_LBL),
    'extendsFrom'        => Config::getMessage(EXTENDS_FROM_PCT_S_LBL),
    'falseCap'           => Config::getMessage(FALSE_LBL),
    'inheritedAttribute' => Config::getMessage(THIS_ATTRIBUTE_IS_INHERITED_MSG),
    'inheritedUrlParam'  => Config::getMessage(THIS_URL_PARAMETER_IS_INHERITED_MSG),
    'js'                 => Config::getMessage(JAVASCRIPT_LBL),
    'jsView'             => Config::getMessage(JAVASCRIPT_VIEW_LBL),
    'jsViewSubstitution' => Config::getMessage(JAVASCRIPT_VIEW_PCT_S_LBL),
    'manifest'           => Config::getMessage(WIDGET_INFO_FILE_LBL),
    'newExtendingWidget' => Config::getMessage(NEW_EXTENDING_WIDGET_LBL),
    'newWidget'          => Config::getMessage(NEW_WIDGET_LBL),
    'optionError'        => Config::getMessage(OPTION_OPTIONTYPE_CLICK_OPTION_MSG),
    'overrideView'       => Config::getMessage(OVERRIDES_THE_PARENT_WIDGETS_VIEW_LBL),
    'parentCss'          => Config::getMessage(INCLUDES_THE_PARENT_WIDGETS_CSS_LBL),
    'php'                => Config::getMessage(PHP_CONTROLLER_LBL),
    'presentation'       => Config::getMessage(PRESENTATION_CSS_LBL),
    'subDone'            => Config::getMessage(FILES_CREATED_WIDGET_MSG),
    'trueCap'            => Config::getMessage(TRUE_LBL),
    'view'               => Config::getMessage(PHP_VIEW_LBL),
    'validationErrors' => array(
        'name'        => Config::getMessage(INV_ATTRIB_NAME_NAME_LOWERCASE_UNIQ_MSG),
        'option'      => Config::getMessage(INV_OPTION_NAME_OPTS_CONT_LOWERCASE_MSG),
        'description' => Config::getMessage(PLEASE_ADD_DESCRIPTION_MSG),
     ),
    )) ?>;
</script>
