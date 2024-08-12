<? use RightNow\Utils\Config; ?>
<h2><?= Config::getMessage(WIDGET_INFO_LBL) ?></h2>
<p><? printf(Config::getMessage(WIDGET_REQD_PCT_S_FILE_DIRECTORY_MSG), '<i>info.yml</i>') ?></p>
<p><? printf(Config::getMessage(WIDGET_PCT_S_FILES_WRITTEN_PCT_S_MSG), '<i>info.yml</i>', '<a href="http://en.wikipedia.org/wiki/YAML" target="_blank">YAML</a>') ?></p>

<dl>
    <dt id="version" class="highlight">version</dt>
    <dd>
        <?= Config::getMessage(STRING_CONSISTING_MAJOR_MINOR_MSG) ?>
        <strong><?= Config::getMessage(REQUIRED_LBL) ?></strong>
        <div class="example">
            <span class='label'><?= Config::getMessage(EXAMPLE_COLON_LBL) ?></span>
            <a href="javascript:void(0);" data-toggle="<?= Config::getMessage(HIDE_CMD) ?>"><?= Config::getMessage(SHOW_CMD) ?></a>
            <pre class="hide">version: "1.0"</pre>
        </div>
    </dd>

    <dt id="requires" class="highlight">requires</dt>
    <dd>
        <dl>
            <dt>framework</dt>
            <dd>
                <?= Config::getMessage(STRING_LIST_MAJOR_MINOR_VERSION_MSG) ?>
                <div class="example">
                    <span class='label'><?= Config::getMessage(EXAMPLE_COLON_LBL) ?></span>
                    <a href="javascript:void(0);" data-toggle="<?= Config::getMessage(HIDE_CMD) ?>"><?= Config::getMessage(SHOW_CMD) ?></a>
                    <pre class="hide">framework: "3.0"</pre>
                </div>
            </dd>

            <dt class="highlight">jsModule</dt>
            <dd>
                <? printf(Config::getMessage(LIST_CONSISTING_PCT_S_WIDGET_MSG), '<a href="/ci/admin/docs/framework/pageMeta#javascript_module">' . Config::getMessage(JAVASCRIPT_MODULE_UC_LBL) . '</a>') ?>
                <strong><?= Config::getMessage(REQUIRED_LBL) ?></strong>
                <div>
                    <?= Config::getMessage(VALID_OPTIONS_INCLUDE_LBL) ?>
                    <dl>
                        <dt>standard</dt>
                        <dd><?= Config::getMessage(WIDGET_DEF_SET_YUI_RN_LIBRARIES_MSG) ?></dd>
                        <dt>mobile</dt>
                        <dd><?= Config::getMessage(WDGET_DEF_SET_YUI_RN_LIBRARIES_MSG) ?></dd>
                        <dt>none</dt>
                        <dd><?= Config::getMessage(WIDGET_RELIES_DEF_YUI_RN_MSG) ?></dd>
                    </dl>
                </div>
                <div class="example">
                    <span class='label'><?= Config::getMessage(EXAMPLE_COLON_LBL) ?></span>
                    <a href="javascript:void(0);" data-toggle="<?= Config::getMessage(HIDE_CMD) ?>"><?= Config::getMessage(SHOW_CMD) ?></a>
                    <pre class="hide">jsModule: [standard, mobile]</pre>
                </div>
            </dd>

            <dt>yui</dt>
            <dd>
                <? printf(Config::getMessage(L_CONSISTING_PCT_S_WIDGET_MSG), '<a href="http://yuilibrary.com/yui/docs/yui/modules.html">' . Config::getMessage(YUI_MODULES_LBL) . '</a>') ?>
                <div>
                    <?= Config::getMessage(DEFAULT_MODULES_INCLUDED_PAGE_MSG) ?>
                    <div>
                        <a href="javascript:void(0);" data-toggle="<?= Config::getMessage(HIDE_LIST_CMD) ?>"><?= Config::getMessage(SHOW_LIST_CMD) ?></a>
                        <ul class="hide">
                            <li>anim-base</li>
                            <li>anim-easing</li>
                            <li>escape</li>
                            <li>event-base</li>
                            <li>history</li>
                            <li>node-core</li>
                            <li>node-event-delegate</li>
                            <li>node-screen</li>
                            <li>node-style</li>
                        </ul>
                    </div>
                </div>
                <br>
                <?= Config::getMessage(YUI_REQS_LISTED_JSMODULE_BASIS_MSG) ?>
                <div class="example">
                    <span class='label'><?= Config::getMessage(EXAMPLE_COLON_LBL) ?></span>
                    <a href="javascript:void(0);" data-toggle="<?= Config::getMessage(HIDE_CMD) ?>"><?= Config::getMessage(SHOW_CMD) ?></a>
                    <pre class="hide">
yui: [panel, autocomplete, autocomplete-highlighters]

or

yui:
  standard: [overlay]
                    </pre>
                </div>
            </dd>
        </dl>
    </dd>

    <dt id="attributes">attributes</dt>
    <dd>
        <?= Config::getMessage(ASSOCIATIVE_ARRAY_CONSISTING_ATTRIB_MSG) ?>
        <dl>
            <dt>name</dt>
            <dd><?= Config::getMessage(HUMAN_READABLE_NAME_ATTRIBUTE_MSG) ?> <strong><?= Config::getMessage(REQUIRED_LBL) ?></strong></dd>

            <dt>description</dt>
            <dd><?= Config::getMessage(DESCRIPTION_OF_THE_ATTRIBUTE_MSG) ?></dd>

            <dt>type</dt>
            <dd>
                <?= Config::getMessage(DATA_TYPE_ATTRIB_OPTS_INCLUDE_MSG) ?>
                <ul>
                    <li>string</li>
                    <li>boolean</li>
                    <li>int</li>
                    <li>option</li>
                    <li>multioption</li>
                    <li>ajax</li>
                    <li>filepath</li>
                </ul>
            </dd>

            <dt>default</dt>
            <dd>
                <?= Config::getMessage(DEF_VAL_ATTRIB_VAL_ISNT_WIDGET_MSG) ?>
                <dl>
                    <dt>string</dt>
                    <dd><?= Config::getMessage(STRING_VALUE_DEFAULTS_IF_OMITTED_MSG) ?></dd>
                    <dt>boolean</dt>
                    <dd><?= Config::getMessage(TRUE_FALSE_DEFAULTS_FALSE_OMITTED_MSG) ?></dd>
                    <dt>int</dt>
                    <dd><?= Config::getMessage(INTEGER_VALUE_DEFS_NULL_OMITTED_MSG) ?></dd>
                    <dt>option</dt>
                    <dd><? printf(Config::getMessage(STRING_OPTION_NAME_OPTION_PCT_S_MSG), '<em>options</em>') ?> <strong><?= Config::getMessage(REQUIRED_LBL) ?></strong></dd>
                    <dt>multioption</dt>
                    <dd><? printf(Config::getMessage(ARRAY_VALUE_WHICH_MUST_ONE_OPTION_KEY_LBL), '<em>options</em>') ?> </dd>
                    <dt>ajax</dt>
                    <dd><?= Config::getMessage(STRING_PATH_CONTROLLER_ENDPOINT_MSG) ?></dd>
                    <dt>filepath</dt>
                    <dd><?= Config::getMessage(STRING_PATH_ASSET_RELATIVE_THEME_MSG) ?></dd>
                </dl>
            </dd>

            <dt>required</dt>
            <dd><?= Config::getMessage(TRUE_FALSE_DENOTES_ATTRIB_VAL_REQD_MSG) ?></dd>

            <dt>options</dt>
            <dd><? printf(Config::getMessage(OPTION_NAME_ONLY_APPLIE_ATTRIBUTES_MSG), '<em>option-type</em>', '<em>multioption-type</em>') ?></dd>
        </dl>
        <div class="example">
            <span class='label'><?= Config::getMessage(EXAMPLE_COLON_LBL) ?></span>
            <a href="javascript:void(0);" data-toggle="<?= Config::getMessage(HIDE_CMD) ?>"><?= Config::getMessage(SHOW_CMD) ?></a>
            <pre class="hide">
attributes:
  label_heading:
    name: Heading label
    description: Label for the heading
    type: string
    default: Welcome back!
  display_option:
    name: Display option
    description: How to display the widget
    options: [subtle, bold, obnoxious]
    default: subtle
            </pre>
        </div>
    </dd>

    <dt id="extends">extends</dt>
    <dd>
        <?= Config::getMessage(INDICATES_WIDGET_WIDGET_EXTENDS_MSG) ?>
        <dl>
            <dt>widget</dt>
            <dd><?= Config::getMessage(DIRECTORY_PATH_WIDGET_EXTEND_BEG_MSG) ?></dd>

            <dt>components</dt>
            <dd>
                <?= Config::getMessage(L_WIDGET_COMPONENTS_EXTEND_OPTS_MSG) ?>
                <ul>
                    <li>php</li>
                    <li>view</li>
                    <li>js</li>
                    <li>css</li>
                </ul>
            </dd>

            <dt>overrideViewAndLogic</dt>
            <dd><? printf(Config::getMessage(SPEC_TRUE_OVRRIDE_WIDGETS_OWN_VIEW_MSG), '<em>js</em>', '<em>view</em>', '<em>components</em>') ?></dd>
        </dl>
        <div class="example">
            <span class='label'><?= Config::getMessage(EXAMPLE_COLON_LBL) ?></span>
            <a href="javascript:void(0);" data-toggle="<?= Config::getMessage(HIDE_CMD) ?>"><?= Config::getMessage(SHOW_CMD) ?></a>
            <pre class="hide">
extends:
  widget: "standard/utils/SocialBookmarkLink"
  components: [php, css]
  overrideViewAndLogic: true
            </pre>
        </div>
    </dd>

    <dt id="info">info</dt>
    <dd>
        <?= Config::getMessage(ASSOCIATIVE_ARRAY_CONTAINING_INFO_LBL) ?>
        <dl>
            <dt>description</dt>
            <dd>
                <?= Config::getMessage(DESCRIPTIVE_TXT_EXPLAINING_WIDGETS_MSG) ?>
                <div class="example">
                    <span class='label'><?= Config::getMessage(EXAMPLE_COLON_LBL) ?></span>
                    <a href="javascript:void(0);" data-toggle="<?= Config::getMessage(HIDE_CMD) ?>"><?= Config::getMessage(SHOW_CMD) ?></a>
                    <pre class="hide">description: This widget provides a contact us form for users to submit complaints and feature requests.</pre>
                </div>
            </dd>

            <dt>urlParameters</dt>
            <dd>
                <?= Config::getMessage(ASSOCIATIVE_ARRAY_CONSISTING_URL_MSG) ?>
                <dl>
                    <dt>name</dt>
                    <dd><?= Config::getMessage(HUMAN_READABLE_NAME_ATTRIBUTE_LBL) ?></dd>

                    <dt>description</dt>
                    <dd><?= Config::getMessage(DESCRIPTION_PARAMETERS_PURPOSE_LBL) ?></dd>

                    <dt>example</dt>
                    <dd><?= Config::getMessage(EXAMPLE_PARAMETERS_KEY_AND_VALUE_LBL) ?></dd>
                </dl>
                <div class="example">
                    <span class='label'><?= Config::getMessage(EXAMPLE_COLON_LBL) ?></span>
                    <a href="javascript:void(0);" data-toggle="<?= Config::getMessage(HIDE_CMD) ?>"><?= Config::getMessage(SHOW_CMD) ?></a>
                    <pre class="hide">
urlParameters:
  a_id:
    name: Answer ID
    description: ID of the answer to display
    example: a_id/123
                    </pre>
                </div>
            </dd>
        </dl>
    </dd>
</dl>