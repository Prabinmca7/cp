<?php
namespace RightNow\Internal\Libraries;

use RightNow\Internal\Libraries\Widget\Registry,
    RightNow\Utils\Config;

abstract class DefinitionBase
{
    public function __construct($memberVariableInitializerArray)
    {
        foreach ($memberVariableInitializerArray as $varName => $varVal)
        {
            $this->$varName = $varVal;
        }
    }

    protected function sortByNameMaintainingKeys(&$array)
    {
        $nameArray = array();
        foreach ($array as $key => $value)
        {
            if (!isset($value->name))
            {
                throw new \Exception("Can't handle element with key '$key' because its value doesn't have a name member.");
            }
            $nameArray[$value->name] = $key;
        }
        uksort($nameArray, 'strcasecmp');

        $newArray = array();
        foreach ($nameArray as $name => $key)
        {
            $newArray[$key] = $array[$key];
        }
        return $newArray;
    }
}

final class TagDefinition extends DefinitionBase
{
    public $name;
    public $description;
    public $hasDialog;
    public $hasInspector;
    public $attributes = array();
    public $isEmpty = true;

    public function getBaseName()
    {
        $namePieces = explode(':', $this->name);
        return $namePieces[count($namePieces) - 1];
    }

    public function addAttribute($attribute)
    {
        $this->attributes[$attribute->value] = $attribute;
    }

    public function sortAttributes()
    {
        $this->attributes = parent::sortByNameMaintainingKeys($this->attributes);
        foreach($this->attributes as $attribute)
        {
            $attribute->sortOptions();
        }
    }
}

final class AttributeDefinition extends DefinitionBase
{
    public $name;
    public $value;
    public $type;
    public $default;
    public $tooltip;
    public $options = array();
    public $min;
    public $max;
    public $length;
    public $required;
    public $optlistId;

    public function addOption($option)
    {
        if (!$option->value) throw new \Exception("Can't add an option which doesn't have a value to attribute " . $this->name . ".  Option = " . var_export($option, true));
        $this->options[$option->value] = $option;
    }
    public function sortOptions()
    {
        $this->options = parent::sortByNameMaintainingKeys($this->options);
        foreach ($this->options as $option)
        {
            $option->sortDependentAttributes();
        }
    }

    protected static function createFromWidgetAttribute($widgetAttribute, $value) {
        $realAttribute = new AttributeDefinition(array());

        $realAttribute->value = $value;
        $realAttribute->name = $widgetAttribute->name;
        $realAttribute->type = $widgetAttribute->type;
        $realAttribute->default = $widgetAttribute->default;
        $realAttribute->tooltip = $widgetAttribute->tooltip;
        $realAttribute->min = $widgetAttribute->min;
        $realAttribute->max = $widgetAttribute->max;
        $realAttribute->length = $widgetAttribute->length;
        $realAttribute->optlistId = $widgetAttribute->optlistId;
        $realAttribute->required = $widgetAttribute->required;

        if (is_array($widgetAttribute->options) && count($widgetAttribute->options) > 0) {
            foreach ($widgetAttribute->options as $option) {
                $realAttribute->addOption(new AttributeOptionDefinition(array('name' => $option, 'value' => $option)));
            }
        }
        return $realAttribute;
    }

    protected static function createFromMiddleLayerProperty($middleLayerProperty, $value) {
        $realAttribute = new AttributeDefinition(array());
        $realAttribute->value = $value;
        $realAttribute->name = $middleLayerProperty->lang_name;
        $realAttribute->length = $middleLayerProperty->field_size;
        return $realAttribute;
    }
}

final class AttributeOptionDefinition extends DefinitionBase
{
    public $name;
    public $value;
    public $description;
    public $dependentAttributes = array();
    public $deprecated;

    public function addDependentAttribute($dependentAttribute)
    {
        $this->dependentAttributes[$dependentAttribute->value] = $dependentAttribute;
    }
    public function sortDependentAttributes()
    {
        $this->dependentAttributes = parent::sortByNameMaintainingKeys($this->dependentAttributes);
    }
}

final class TagDefinitions
{
    public static function getInstance()
    {
        static $tagDefinitionInstance;
        if (!isset($tagDefinitionInstance))
        {
            $tagDefinitionInstance = new TagDefinitions();
        }
        return $tagDefinitionInstance;
    }

    private function __construct()
    {
        $this->buildTags();
    }

    private function buildTags()
    {
        $tags = $this->indexByName(array(
            $this->buildMetaTagDefinition(),
            $this->buildPageTitleTagDefinition(),
            $this->buildHeadContentTagDefinition(),
            $this->buildPageContentTagDefinition(),
            $this->buildConditionTagDefinition(),
            $this->buildConditionElseTagDefinition(),
            $this->buildFieldTagDefinition(),
            $this->buildThemeTagDefinition(),
            $this->buildContainerTagDefinition(),
            $this->buildFormTagDefinition(),
        ));

        $this->tags = $this->sortTagAttributes($tags);
    }

    private function indexByName($tags) {
        $dict = array();

        foreach ($tags as $tag) {
            $dict[$tag->name] = $tag;
        }

        return $dict;
    }

    private function sortTagAttributes($tags)
    {
        foreach ($tags as $tag)
        {
            $tag->sortAttributes();
        }

        return $tags;
    }

    private function buildPageTitleTagDefinition()
    {
        return new TagDefinition(array(
            'name' => 'rn:page_title',
            'hasDialog' => false,
            'hasInspector' => false,
            'description' => Config::getMessage(TAG_INDICATES_LOC_TEMPL_PGS_TITLE_MSG),
        ));
    }

    private function buildHeadContentTagDefinition()
    {
        return new TagDefinition(array(
            'name' => 'rn:head_content',
            'hasDialog' => false,
            'hasInspector' => false,
            'description' => Config::getMessage(TAG_INDICATES_LOC_TEMPL_PGS_HEAD_MSG),
        ));
    }

    private function buildPageContentTagDefinition()
    {
        return new TagDefinition(array(
            'name' => 'rn:page_content',
            'hasDialog' => false,
            'hasInspector' => false,
            'description' => Config::getMessage(TAG_INDICATES_LOC_TEMPL_PAGES_MSG),
        ));
    }

    private function buildConditionElseTagDefinition()
    {
        return new TagDefinition(array(
            'name' => 'rn:condition_else',
            'hasDialog' => false,
            'hasInspector' => false,
            'isEmpty' => true,
        ));
    }

    private function buildConditionTagDefinition()
    {
        $conditionTag = new TagDefinition(array(
            'name' => 'rn:condition',
            'hasDialog' => true,
            'hasInspector' => false,
            'isEmpty' => false,
            'description' => Config::getMessage(TAG_ALLOWS_DEF_CONDITIONAL_SECTIONS_MSG),
        ));

        $conditionals = array(
            array(
                'value' => 'answers_viewed',
                'name' => Config::getMessage(ANSWERS_VIEWED_LBL),
                'type' => 'INT',
                'tooltip' => Config::getMessage(DEFINES_MINIMUM_L_BASE_BEFORE_COND_MET_MSG),
                'min' => 0,
            ),
            array(
                'value' => 'content_viewed',
                'name' => Config::getMessage(CONTENT_VIEWED_LBL),
                'type' => 'INT',
                'tooltip' => Config::getMessage(MIN_L_S_ANDOR_S_QS_BEF_COND_MET_MSG),
                'min' => 0,
            ),
            array(
                'value' => 'questions_viewed',
                'name' => Config::getMessage(QUESTIONS_VIEWED_LBL),
                'type' => 'INT',
                'tooltip' => Config::getMessage(DEFINES_MIN_SOCIAL_QS_BEFORE_COND_MET_MSG),
                'min' => 0,
            ),
            array(
                'value' => 'hide_on_pages',
                'name' => Config::getMessage(HIDE_ON_PAGES_CMD),
                'type' => 'STRING',
                'tooltip' => Config::getMessage(COMMA_SEPARATED_L_PAGES_HIDE_MSG),
            ),
            array(
                'value' => 'show_on_pages',
                'name' => Config::getMessage(SHOW_ON_PAGES_CMD),
                'type' => 'STRING',
                'tooltip' => Config::getMessage(COMMA_SEPARATED_L_PAGES_CONTENT_PG_MSG),
            ),
            array(
                'value' => 'logged_in',
                'name' => Config::getMessage(LOGGED_IN_MSG),
                'type' => 'BOOL',
                'tooltip' => Config::getMessage(DENOTES_LOGGED_VIEW_CONTENT_MSG),
            ),
            array(
                'value' => 'config_check',
                'name' => Config::getMessage(CONFIG_SETTING_CHECK_LBL),
                'type' => 'STRING',
                'tooltip' => sprintf(Config::getMessage(ALLOWS_CHECKING_VAL_CONFIG_SET_FMT_MSG), '&lt;rn:condition config_check="RNW_UI:<m4-ignore>CP_HOME_URL</m4-ignore> == \'home\'"&gt;, &lt;rn:condition config_check="COMMON:<m4-ignore>CACHED_CONTENT_EXPIRE_TIME</m4-ignore> > 0"&gt;, &lt;rn:condition config_check="RNW_UI:<m4-ignore>COMMUNITY_ENABLED</m4-ignore> == true"&gt;'),
            ),
            array(
                'value' => 'site_config_check',
                'name' => Config::getMessage(CHECKS_CONFIG_T_SITECONFIGJSON_FILE_MSG),
                'type' => 'STRING',
                'tooltip' => sprintf(Config::getMessage(LLW_CHCK_STCNFGJSN_FMT_CNFGSLT_PRT_MSG), '&lt;rn:condition site_config_check="CP.EmailConfirmationLoop.Enable</m4-ignore> == 0"&gt;'),
            ),
            array(
                'value' => 'url_parameter_check',
                'name' => Config::getMessage(URL_PARAMETER_CHECK_LBL),
                'type' => 'STRING',
                'tooltip' => sprintf(Config::getMessage(ALLOWS_CHECKING_VAL_URL_PARAM_FMT_MSG), '&lt;rn:condition url_parameter_check="kw == \'roaming\'"&gt;, &lt;rn:condition url_parameter_check="p != null"&gt;'),
            ),
            array(
                'value' => 'language_in',
                'name' => Config::getMessage(LANGUAGE_IN_LBL),
                'type' => 'STRING',
                'tooltip' => Config::getMessage(COMMA_SEPARATED_L_LANG_CODES_MSG),
            ),
            array(
                'value' => 'searches_done',
                'name' => Config::getMessage(SEARCHES_DONE_LBL),
                'type' => 'INT',
                'tooltip' => sprintf(Config::getMessage(DEFS_MINIMUM_SEARCHES_PERFORMED_MSG), 'NavigationTab', 'searches_done'),
                'min' => 0,
            ),
            array(
                'value' => 'incident_reopen_deadline_hours',
                'name' => Config::getMessage(INCIDENT_REOPEN_DEADLINE_HOURS_LBL),
                'type' => 'INT',
                'tooltip' => Config::getMessage(HRS_INC_CL_BEF_CONTENT_HIDDEN_SET_0_MSG),
                'min' => 0,
            ),
            array(
                'value' => 'external_login_used',
                'name' => Config::getMessage(EXTERNAL_LOGIN_USED_LBL),
                'type' => 'BOOL',
                'tooltip' => Config::getMessage(DNOTES_HIDE_CONTENT_AUTHENTICATED_MSG),
            ),
            array(
                'value' => 'sla',
                'name' => Config::getMessage(SLA_LBL),
                'type' => 'OPTION',
                'tooltip' => Config::getMessage(DEFINES_SLA_TYPE_BEFORE_CONTENT_MSG),
                'options' => array(
                    'incident' => array('name' => 'Incident', 'value' => 'incident'),
                    'chat' => array('name' => 'Chat', 'value' => 'chat'),
                    'selfservice' => array('name' => 'Self Service', 'value' => 'selfservice'),
                ),
            ),
            array(
                'value' => 'chat_available',
                'name' => Config::getMessage(CHAT_AVAILABLE_LBL),
                'type' => 'BOOL',
                'tooltip' => Config::getMessage(DENOTES_T_CHAT_OPERATING_HRS_MSG),
            ),
            array(
                'value' => 'flashdata_value_for',
                'name' => Config::getMessage(FLASH_DATA_AVAILABLE_LBL),
                'type' => 'STRING',
                'default' => null,
                'tooltip' => Config::getMessage(WHETHER_ERR_FLASHDAT_S_FLASHDAT_IT_KEY_LBL),
            ),
            array(
                'value' => 'is_social_moderator',
                'name' => Config::getMessage(IS_SOCIAL_MODERATOR_LBL),
                'type' => 'BOOL',
                'tooltip' => Config::getMessage(T_PRMVWSCLMDRTRDSHBRD_PRM_CNT_PRMSSND_CN_MSG),
            ),
            array(
                'value' => 'is_social_user_moderator',
                'name' => Config::getMessage(IS_SOCIAL_USER_MODERATOR_LBL),
                'type' => 'BOOL',
                'tooltip' => Config::getMessage(T_PRMVWSCLMDRTR_PRM_MDRTN_PRMSSND_MDRTN_MSG),
            ),
            array(
                'value' => 'is_social_user',
                'name' => Config::getMessage(IS_SOCIAL_USER_LBL),
                'type' => 'BOOL',
                'tooltip' => Config::getMessage(INDICATES_WHETHER_L_LOGGED_SOCIAL_USER_MSG),
            ),
            array(
                'value' => 'is_active_social_user',
                'name' => Config::getMessage(IS_ACTIVE_SOCIAL_USER_LBL),
                'type' => 'BOOL',
                'tooltip' => Config::getMessage(INDICATES_T_L_LOGGED_S_ACTIVE_STATUS_MSG),
            ),
        );

        foreach ($conditionals as $condition) {
            $attribute = new AttributeDefinition($condition);
            if (isset($condition['options']) && is_array($condition['options'])) {
                foreach ($condition['options'] as $name => $option) {
                    $attribute->addOption(new AttributeOptionDefinition($option));
                }
            }
            $conditionTag->addAttribute($attribute);
        }

        return $conditionTag;
    }

    private function buildContainerTagDefinition()
    {
        $containerTag = new TagDefinition(array(
            'name' => 'rn:container',
            'hasDialog' => true,
            'hasInspector' => false,
            'isEmpty' => false,
            'description' => Config::getMessage(MULT_WIDGETS_PG_ATTRIB_VALS_COMMON_MSG),
        ));

        $containerTag->addAttribute(new AttributeDefinition(array(
            'value' => 'rn_container_id',
            'name' => Config::getMessage(CONTAINER_ID_LBL),
            'type' => 'STRING',
            'tooltip' => sprintf(Config::getMessage(DEFINES_VAL_WIDGETS_OPT_PCT_S_MSG), "rn_container_id", "rnc_"),
        )));

        return $containerTag;
    }

    private function buildFieldTagDefinition()
    {
        $fieldTag = new TagDefinition(array(
            'name' => 'rn:field',
            'hasDialog' => true,
            'hasInspector' => false,
            'isEmpty' => true,
            'description' => Config::getMessage(TAG_ALLOWS_OUTPUT_BUS_OBJECT_FLD_MSG),
        ));
        $fieldTag->addAttribute(new AttributeDefinition(array(
            'value' => 'name',
            'name' => Config::getMessage(NAME_LBL),
            'type' => 'OPTION',
            'tooltip' => sprintf(Config::getMessage(HREF_EQUALS_PCT_S_BUS_OBJECT_FLD_S_MSG), '/ci/admin/docs/framework/businessObjects'),
        )));
        $fieldTag->addAttribute(new AttributeDefinition(array(
            'value' => 'id',
            'name' => Config::getMessage(ID_LBL),
            'type' => 'INT',
            'tooltip' => Config::getMessage(PARAMETER_PCT_EG_AID1_DOES_L_BUS_OBJECT_MSG),
        )));
        $fieldTag->addAttribute(new AttributeDefinition(array(
            'value' => 'highlight',
            'name' => Config::getMessage(HIGHLIGHT_LBL),
            'type' => 'BOOL',
            'default' => false,
            'tooltip' => Config::getMessage(DENOTES_HIGHLIGHT_CONTENT_KEYWORD_MSG),
        )));
        $fieldTag->addAttribute(new AttributeDefinition(array(
            'value' => 'label',
            'name' => Config::getMessage(LABEL_LBL),
            'type' => 'STRING',
            'default' => '',
            'tooltip' => Config::getMessage(CONT_SUBSTITUTION_RRR_R_INSERT_USED_MSG),
        )));

        return $fieldTag;
    }

    private function buildThemeTagDefinition()
    {
        $themeTag = new TagDefinition(array(
            'name' => 'rn:theme',
            'hasDialog' => true,
            'hasInspector' => false,
            'isEmpty' => true,
            'description' => Config::getMessage(TG_ALLOWS_DECLARE_PG_TEMPL_THEME_MSG),
        ));

        $themeTag->addAttribute(new AttributeDefinition(array(
            'value' => 'path',
            'name' => Config::getMessage(PATH_LBL),
            'type' => 'STRING',
            'tooltip' => Config::getMessage(SPCIFIES_PATH_BASE_DIRECTORY_THEME_MSG),
        )));
        $themeTag->addAttribute(new AttributeDefinition(array(
            'value' => 'css',
            'name' => Config::getMessage(CSS_UC_LBL),
            'type' => 'STRING',
            'tooltip' => Config::getMessage(SPCFIES_CSS_FILES_INCLUDE_PG_MSG),
        )));

        return $themeTag;
    }

    private function buildFormTagDefinition() {
        $formTag = new TagDefinition(array(
            'name' => 'rn:form',
            'hasDialog' => false,
            'hasInspector' => false,
            'description' => Config::getMessage(TAG_ALLOWS_SERVER_VALIDATION_WIDGET_MSG),
        ));

        $formTag->addAttribute(new AttributeDefinition(array(
            'value' => 'action',
            'name' => Config::getMessage(ACTION_LBL),
            'type' => 'STRING',
            'tooltip' => Config::getMessage(ACT_ATTRIB_FORM_TAG_DEFS_MSG),
        )));
        $formTag->addAttribute(new AttributeDefinition(array(
            'value' => 'post_handler',
            'name' => Config::getMessage(POST_HANDLER_LBL),
            'type' => 'STRING',
            'tooltip' => Config::getMessage(PATH_POST_REQ_HANDLER_POSTREQUEST_LBL),
        )));

        return $formTag;
    }

    private function buildMetaTagDefinition()
    {
        $metaTag = new TagDefinition(array(
            "name" => "rn:meta",
            "hasDialog" => true,
            "hasInspector" => false,
            "description" => Config::getMessage(TAG_SPECIFIES_PG_OPTS_TEMPL_APPLY_MSG),
        ));

        $templateAttribute = new AttributeDefinition(array(
            "value" => "template",
            "name" => Config::getMessage(TEMPLATE_PATH_LBL),
            "type" => "OPTION",
            "optlistId" => "templates",
            "tooltip" => Config::getMessage(NME_TEMPL_ASSOC_PG_TEMPL_LOCATED_MSG),
        ));
        foreach (\RightNow\Utils\FileSystem::getListOfTemplates() as $template) {
            $templateAttribute->addOption(new AttributeOptionDefinition(array("name" => $template, "value" => $template)));
        }
        $metaTag->addAttribute($templateAttribute);

        $metaTag->addAttribute(new AttributeDefinition(array(
            "value" => "title",
            "name" => Config::getMessage(PAGE_TITLE_LBL),
            "type" => "STRING",
            "tooltip" => Config::getMessage(VALUE_TITLE_APPEAR_PAGE_LBL),
        )));
        $metaTag->addAttribute(new AttributeDefinition(array(
            "value" => "login_required",
            "name" => Config::getMessage(LOGIN_REQUIRED_LBL),
            "type" => "BOOL",
            "tooltip" => Config::getMessage(INDICATES_LOGGED_VIEW_PG_LOGGED_MSG),
        )));
        $metaTag->addAttribute(new AttributeDefinition(array(
            "value" => "force_https",
            "name" => Config::getMessage(FORCE_HTTPS_LBL),
            "type" => "BOOL",
            "tooltip" => Config::getMessage(ATTRIB_CP_FORCE_PASSWDS_HTTPS_CFG_MSG),
        )));
        $metaTag->addAttribute(new AttributeDefinition(array(
            "value" => "redirect_if_logged_in",
            "name" => Config::getMessage(REDIRECT_IF_LOGGED_IN_LBL),
            "type" => "STRING",
            "tooltip" => Config::getMessage(SET_LOGGED_REDIRECTED_VAL_REDIRECT_MSG),
        )));
        $metaTag->addAttribute(new AttributeDefinition(array(
            'value' => 'account_session_required',
            'name' => Config::getMessage(ACCOUNT_SESSION_ID_REQUIRED_LBL),
            'type' => 'BOOL',
            'tooltip' => Config::getMessage(INDICATES_PG_ACCED_LOGGED_ACCTS_MSG) . '  ' . Config::getMessage(CP_CONT_LOGIN_REQD_CFG_PG_MARKED_MSG),
        )));
        $metaTag->addAttribute(new AttributeDefinition(array(
            "value" => "sla_failed_page",
            "name" => Config::getMessage(SLA_FAILED_PAGE_LBL),
            "type" => "STRING",
            "tooltip" => Config::getMessage(PG_LOC_USERS_REDIRECTED_SLA_CHECK_MSG),
        )));
        $metaTag->addAttribute(new AttributeDefinition(array(
            "value" => "social_moderator_required",
            "name" => Config::getMessage(SOCIAL_MODERATOR_REQUIRED_LBL),
            "type" => "BOOL",
            "tooltip" => Config::getMessage(T_CCD_PRMVWSCLMDRTRDSHBRD_PRM_NBL_PRMSSN_MSG),
        )));
        $metaTag->addAttribute(new AttributeDefinition(array(
            "value" => "social_user_moderator_required",
            "name" => Config::getMessage(SOCIAL_USER_MODERATOR_REQUIRED_LBL),
            "type" => "BOOL",
            "tooltip" => Config::getMessage(T_CCD_PRMVWSCLMDRTRDSHBRD_PRM_PRM_NBL_PR_MSG),
        )));
        $slaTypeAttribute = new AttributeDefinition(array(
            'value' => 'sla_required_type',
            'name' => Config::getMessage(SLA_REQUIRED_TYPE_LBL),
            'type' => 'OPTION',
            'tooltip' => Config::getMessage(DEFINES_TYPE_SLA_REQD_END_ABLE_VIEW_MSG),
        ));
        $slaTypeAttribute->addOption(new AttributeOptionDefinition(array('name' => 'incident', 'value' => 'incident')));
        $slaTypeAttribute->addOption(new AttributeOptionDefinition(array('name' => 'chat', 'value' => 'chat')));
        $slaTypeAttribute->addOption(new AttributeOptionDefinition(array('name' => 'selfservice', 'value' => 'selfservice')));
        $metaTag->addAttribute($slaTypeAttribute);

        $javascriptModuleAttribute = new AttributeDefinition(array(
            'value' => 'javascript_module',
            'name' => Config::getMessage(JAVASCRIPT_MODULE_LBL),
            'type' => 'OPTION',
            'default' => 'Standard',
            'tooltip' => Config::getMessage(DEFINES_JAVASCRIPT_MODULE_LOADED_PG_MSG),
        ));
        $javascriptModuleAttribute->addOption(new AttributeOptionDefinition(array('name' => 'Standard', 'value' => 'standard')));
        $javascriptModuleAttribute->addOption(new AttributeOptionDefinition(array('name' => 'Mobile', 'value' => 'mobile')));
        $javascriptModuleAttribute->addOption(new AttributeOptionDefinition(array('name' => 'None', 'value' => 'none')));
        $metaTag->addAttribute($javascriptModuleAttribute);

        $metaTag->addAttribute(new AttributeDefinition(array(
            "value" => "answer_details",
            "name" => Config::getMessage(ANS_DETAILS_LBL),
            "type" => "BOOL",
            "tooltip" => Config::getMessage(INDCATES_PG_ANS_DET_DISP_PG_MSG),
        )));
        $metaTag->addAttribute(new AttributeDefinition(array(
            "value" => "include_chat",
            "name" => Config::getMessage(INCLUDE_CHAT_LBL),
            'type' => 'BOOL',
            'tooltip' => Config::getMessage(DENOTES_PG_CONT_CHAT_APP_PG_INCLUDE_MSG),
        )));

        $clickstreamAttribute = new AttributeDefinition(array(
            "value" => "clickstream",
            "name" => Config::getMessage(CLICKSTREAM_TAG_LBL),
            "type" => "OPTION",
            "tooltip" => Config::getMessage(INDCATES_TYPE_PG_CLICKSTREAM_STATS_MSG),
        ));
        $this->addClickstreamAttributes($clickstreamAttribute);
        $metaTag->addAttribute($clickstreamAttribute);

        $metaTag->addAttribute(new AttributeDefinition(array(
            'value' => 'noindex',
            'name' => Config::getMessage(NO_INDEX_LBL),
            'type' => 'BOOL',
            'tooltip' => Config::getMessage(T_TG_ADD_DD_SECT_INFORM_ROBOTS_INDEXD_MSG),
        )));

        return $metaTag;
    }

    private function addClickstreamAttributes(AttributeDefinition $attr) {
        $clickstreamAttributes = array(
            'account_create' => null,
            'account_login' => null,
            'account_logout' => null,
            'account_update' => null,
            'answer_feedback' => null,
            'answer_list' => null,
            'answer_notification' => null,
            'answer_notification_delete' => null,
            'answer_notification_update' => null,
            'answer_preview' => 'November 09',
            'answer_print' => 'November 09',
            'answer_rating' => null,
            'answer_view' => null,
            'attachment_upload' => null,
            'attachment_view' => null,
            'chat_landing' => null,
            'chat_request' => null,
            'document_detail' => null,
            'document_submit' => null,
            'document_verify_contact' => null,
            'document_view' => null,
            'email_answer' => null,
            'feedback' => null,
            'home' => null,
            'incident_confirm' => null,
            'incident_create' => null,
            'incident_create_smart' => null,
            'incident_list' => null,
            'incident_print' => 'November 09',
            'incident_submit' => null,
            'incident_update' => null,
            'incident_view' => null,
            'opensearch_service' => null,
            'notification_update' => null,
            'notification_delete' => null,
            'product_category_notification' => null,
            'product_category_notification_delete' => null,
            'product_category_notification_update' => null,
            'report_data_service' => null,
            'site_feedback' => null,
        );

        require_once CPCORE . 'Internal/Libraries/Version.php';
        $deprecationBaseline = new \RightNow\Internal\Libraries\Version(Registry::getDeprecationBaseline());

        foreach ($clickstreamAttributes as $attribute => $deprecated) {
            $elements = array('name' => $attribute, 'value' => $attribute);
            if ($deprecated !== null) {
                $elements['deprecated'] = array($deprecated);
            }
            if ($deprecated === null || $deprecationBaseline->lessThan($deprecated)) {
                $attr->addOption(new AttributeOptionDefinition($elements));
            }
        }
    }
}
