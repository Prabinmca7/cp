<h2><?=\RightNow\Utils\Config::getMessage(PAGE_TAGS_LBL);?></h2>
<br />

<h3><?=\RightNow\Utils\Config::getMessage(PAGE_TITLE_LBL);?></h3>
<h4>&lt;rn:page_title /&gt;</h4>
<p><?=$pageTitle->description;?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/>
    &lt;title&gt; <br/>
    &nbsp;&nbsp;&nbsp;&lt;rn:page_title /&gt;<br/>
    &lt;/title&gt;
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(HEAD_CONTENT_LBL);?></h3>
<h4>&lt;rn:head_content /&gt;</h4>
<p><?=$headContent->description;?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/>
    &lt;head&gt; <br/>
    &nbsp;&nbsp;&nbsp;&lt;rn:head_content /&gt;<br/>
    &lt;/head&gt;
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(PAGE_CONTENT_LBL);?></h3>
<h4>&lt;rn:page_content /&gt;</h4>
<p><?=$pageContent->description;?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/>
    &lt;div&gt; <br/>
    &nbsp;&nbsp;&nbsp;&lt;rn:page_content /&gt;<br/>
    &lt;/div&gt;
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(CONDITIONAL_LBL);?></h3>
<h4>&lt;rn:condition <i>attribute="value"</i>&gt;Content&lt;/rn:condition&gt;</h4>
<p><?=htmlspecialchars($conditionTag->description);?></p>
<?foreach($conditionTag->attributes as $attr):?>
<h4><?=$attr->value;?></h4>
<ul>
    <li><b><?=\RightNow\Utils\Config::getMessage(NAME_LBL);?>:</b> <?=$attr->name?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(TYPE_LBL);?>:</b> <?=$attr->type?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(DESCRIPTION_LBL);?>:</b> <?=$attr->tooltip?></li>
    <?if($attr->default === true):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(TRUE_LBL);?></li>
    <?elseif($attr->default === false):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(FALSE_LBL);?></li>
    <?elseif($attr->default):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=$attr->default?></li>
    <?endif;?>
    <?if(count($attr->options)):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(POSSIBLE_VALUES_LBL);?>:</b>
            <ul>
            <?foreach($attr->options as $option):?>
                <li><?=$option->value;?></li>
            <?endforeach;?>
            </ul>
        </li>
    <?endif;?>
    <?if(isset($attr->min)):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MN_LBL);?>:</b> <?=$attr->min?></li>
    <?endif;?>
    <?if($attr->max):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MAX_LBL);?>:</b> <?=$attr->max?></li>
    <?endif;?>
    <?if($attr->length):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(LENGTH_LBL);?>:</b> <?=$attr->length?></li>
    <?endif;?>
</ul>
<?endforeach;?>
<div class="box">
    <b><?=\RightNow\Utils\Config::getMessage(OR_EXAMPLE_LBL);?></b><br/>
    &lt;rn:condition logged_in="true" show_on_pages="home" &gt; <br/>
    &nbsp;&nbsp;&nbsp;&lt;span&gt;<?=\RightNow\Utils\Config::getMessage(YOURE_EITHER_LOGGED_OR_HOME_PG_MSG) ?>&lt;/span&gt;<br/>
    &lt;/rn:condition&gt;
    <br/>
    <br/>
    <b><?=\RightNow\Utils\Config::getMessage(AND_EXAMPLE_LBL);?></b><br/>
    &lt;rn:condition logged_in="true"&gt;<br/>
    &nbsp;&nbsp;&nbsp;&lt;rn:condition show_on_pages="home"&gt; <br/>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;span&gt;<?=\RightNow\Utils\Config::getMessage(YOURE_LOGGED_AND_HOME_PG_MSG) ?>&lt;/span&gt;<br/>
    &nbsp;&nbsp;&nbsp;&lt;/rn:condition&gt;<br/>
    &lt;/rn:condition&gt;
    <br/>
    <br/>
    <b><?=\RightNow\Utils\Config::getMessage(ELSE_EXAMPLE_LBL);?></b><br/>
    &lt;rn:condition answers_viewed="3" &gt; <br/>
    &nbsp;&nbsp;&nbsp;&lt;span&gt;<?=\RightNow\Utils\Config::getMessage(YOUVE_VIEWED_AT_LEAST_3_ANSWERS_MSG) ?>&lt;/span&gt;<br/>
    &lt;rn:condition_else/&gt;<br/>
    &nbsp;&nbsp;&nbsp;&lt;span&gt;<?=\RightNow\Utils\Config::getMessage(YOU_HAVENT_VIEWED_LEAST_3_ANSWERS_MSG) ?>&lt;/span&gt;<br/>
    &lt;/rn:condition&gt;
</div>
<h3><?=\RightNow\Utils\Config::getMessage(CONTAINER_LBL);?></h3>
<h4>&lt;rn:container rn_container_id="ID" attribute="value" /&gt;</h4>
<p><?=$containerTag->description;?></p>
<?foreach($containerTag->attributes as $attr):?>
<h4><?=$attr->value;?></h4>
<ul>
    <li><b><?=\RightNow\Utils\Config::getMessage(NAME_LBL);?>:</b> <?=$attr->name?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(TYPE_LBL);?>:</b> <?=$attr->type?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(DESCRIPTION_LBL);?>:</b> <?=$attr->tooltip?></li>
</ul>
<?endforeach;?>
<div class="box">
    <b><?=\RightNow\Utils\Config::getMessage(CONTAINING_EXAMPLE_LBL);?></b><br/>
    &lt;rn:container report_id="196"&gt;<br/>
    &nbsp;&nbsp;&nbsp;&lt;rn:widget path="reports/Multiline" label_text="" initial_focus="true"/&gt;<br/>
    &nbsp;&nbsp;&nbsp;&lt;rn:widget path="reports/Paginator" icon_path="images/icons/search.png"/&gt;<br/>
    &lt;/rn:container&gt;<br/>
    <br/>
    <b><?=\RightNow\Utils\Config::getMessage(REFERENCING_EXAMPLE_LBL);?></b><br/>
    &lt;rn:container rn_container_id="1" report_id="196" per_page="2"/&gt;<br/>
    <br/>
    &lt;rn:widget path="reports/Multiline" label_text="" initial_focus="true" rn_container_id="1"/&gt;<br/>
    &lt;rn:widget path="reports/Paginator" icon_path="images/icons/search.png" rn_container_id="1"/&gt;<br/>
</div>
<br />

<h3><?=\RightNow\Utils\Config::getMessage(FORM_LBL);?></h3>
<h4>&lt;rn:form post_handler="postRequest/sendForm" attribute="value"&gt;&lt;/rn:form&gt;</h4>
<p><?=$formTag->description;?></p>
<?foreach($formTag->attributes as $attr):?>
<h4><?=$attr->value;?></h4>
<ul>
    <li><b><?=\RightNow\Utils\Config::getMessage(NAME_LBL);?>:</b> <?=$attr->name?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(TYPE_LBL);?>:</b> <?=$attr->type?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(DESCRIPTION_LBL);?>:</b> <?=$attr->tooltip?></li>
</ul>
<?endforeach;?>
<div class="box">
    <b><?=\RightNow\Utils\Config::getMessage(STANDARD_FORM_EXAMPLE_LBL);?></b><br/>
    &lt;rn:form post_handler="postRequest/sendForm"&gt;<br/>
    &nbsp;&nbsp;&nbsp;&lt;rn:widget path="input/TextInput" name="Incident.Subject" min_length="15"/&gt;<br/>
    &lt;/rn:form&gt;<br/>
    <br/>
</div>

<h3><?=\RightNow\Utils\Config::getMessage(FIELD_LBL);?></h3>
<h4>&lt;rn:field name="<i>Object</i>.<i>Field</i>" /&gt;</h4>
<p><?=$fieldTag->description;?></p>
<?foreach($fieldTag->attributes as $attr):?>
<h4><?=$attr->value;?></h4>
<ul>
    <li><b><?=\RightNow\Utils\Config::getMessage(NAME_LBL);?>:</b> <?=$attr->name?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(TYPE_LBL);?>:</b> <?=$attr->type?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(DESCRIPTION_LBL);?>:</b> <?=$attr->tooltip?></li>
    <?if($attr->default === true):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(TRUE_LBL);?></li>
    <?elseif($attr->default === false):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(FALSE_LBL);?></li>
    <?elseif($attr->default):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=$attr->default?></li>
    <?endif;?>
    <?if(count($attr->options)):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(POSSIBLE_VALUES_LBL);?>: </b><?=sprintf(\RightNow\Utils\Config::getMessage(PLS_PCT_SBUSINESS_OBJECTS_PCT_S_PG_MSG), '<a href="/ci/admin/tags/businessObjects">', '</a>');?></li>
    <?endif;?>
    <?if($attr->min):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MN_LBL);?>:</b> <?=$attr->min?></li>
    <?endif;?>
    <?if($attr->max):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MAX_LBL);?>:</b> <?=$attr->max?></li>
    <?endif;?>
    <?if($attr->length):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(LENGTH_LBL);?>:</b> <?=$attr->length?></li>
    <?endif;?>
</ul>
<?endforeach;?>
<?if(isset($dependantAttributes) && $dependantAttributes):?>
<?foreach($dependantAttributes as $attr):?>
<h4><?=$attr->value;?></h4>
<ul>
    <li><b><?=\RightNow\Utils\Config::getMessage(NAME_LBL);?>:</b> <?=$attr->name?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(TYPE_LBL);?>:</b> <?=$attr->type?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(DESCRIPTION_LBL);?>:</b> <?=$attr->tooltip?></li>
    <?if($attr->default === true):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(TRUE_LBL);?></li>
    <?elseif($attr->default === false):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(FALSE_LBL);?></li>
    <?elseif($attr->default):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=$attr->default?></li>
    <?endif;?>
    <?if($attr->min):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MN_LBL);?>:</b> <?=$attr->min?></li>
    <?endif;?>
    <?if($attr->max):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MAX_LBL);?>:</b> <?=$attr->max?></li>
    <?endif;?>
    <?if($attr->length):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(LENGTH_LBL);?>:</b> <?=$attr->length?></li>
    <?endif;?>
</ul>
<?endforeach;?>
<?endif;?>
<div class="box">
    <b><?=$exampleLabel;?></b><br/>
    &lt;rn:field name="Answer.Summary" id="1" /&gt;<br/>
    &lt;rn:field name="Contact.CustomFields.c.fieldName" /&gt; <br/>
</div>
<h3><?=\RightNow\Utils\Config::getMessage(THEME_LBL);?></h3>
<h4>&lt;rn:theme path="<i>value</i>" css="<i>value</i>" /&gt;</h4>
<p><?=$themeTag->description;?></p>
<?foreach($themeTag->attributes as $attr):?>
<h4><?=$attr->value;?></h4>
<ul>
    <li><b><?=\RightNow\Utils\Config::getMessage(NAME_LBL);?>:</b> <?=$attr->name?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(TYPE_LBL);?>:</b> <?=$attr->type?></li>
    <li><b><?=\RightNow\Utils\Config::getMessage(DESCRIPTION_LBL);?>:</b> <?=$attr->tooltip?></li>
    <?if($attr->default === true):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(TRUE_LBL);?></li>
    <?elseif($attr->default === false):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=\RightNow\Utils\Config::getMessage(FALSE_LBL);?></li>
    <?elseif($attr->default):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(DEFAULT_LBL);?>:</b> <?=$attr->default?></li>
    <?endif;?>
    <?if(count($attr->options)):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(POSSIBLE_VALUES_LBL);?>:</b></li>
        <ul>
        <?foreach($attr->options as $option):?>
            <li><?=$option->value;?></li>
            <?if(count($option->dependentAttributes)):?>
                <?$dependantAttributes = $option->dependentAttributes;?>
            <?endif;?>
        <?endforeach;?>
        </ul>
    <?endif;?>
    <?if($attr->min):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MN_LBL);?>:</b> <?=$attr->min?></li>
    <?endif;?>
    <?if($attr->max):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(MAX_LBL);?>:</b> <?=$attr->max?></li>
    <?endif;?>
    <?if($attr->length):?>
        <li><b><?=\RightNow\Utils\Config::getMessage(LENGTH_LBL);?>:</b> <?=$attr->length?></li>
    <?endif;?>
</ul>
<?endforeach;?>
<div class="box">
    <b><?=$exampleLabel;?></b><br/>
    &lt;rn:theme path="/euf/assets/themes/standard" css="site.css, /rnt/rnw/yui_3.5/overlay/assets/skins/sam/overlay.css, {YUI}/panel/assets/skins/sam/panel.css" /&gt;<br/>
    &lt;rn:theme path="/euf/assets/themes/yourTheme" css="corportate.css, service.css" /&gt;<br/>
</div>
<br/><br/>
<h3 class="info"><?=\RightNow\Utils\Config::getMessage(FOLLOWING_POUND_TAGS_THEMSELVES_PG_MSG);?></h3>
<h3><?=\RightNow\Utils\Config::getMessage(LANGUAGE_CODE_LBL);?></h3>
<h4>#rn:language_code#</h4>
<p><?=\RightNow\Utils\Config::getMessage(LANG_CODE_TAG_OUTPUT_RFC1766_LANG_MSG);?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/><br />
    <b><?=$tagLabel;?>:</b> #rn:language_code#<br/>
    <b><?=$returnLabel;?>:</b> <?=\RightNow\Utils\Text::getLanguageCode();?><br/>
    <b><?=$inlineLabel;?>:</b> &lt;html lang="#rn:language_code#"&gt;
</div>


<br />
<h3><?=\RightNow\Utils\Config::getMessage(MESSAGE_BASE_LABEL_LBL);?></h3>
<h4>#rn:msg:<i><?=\RightNow\Utils\Config::getMessage(MESSAGEBASE_SLOT_LBL);?>#</i></h4>
<p><?=\RightNow\Utils\Config::getMessage(MSG_TG_DISP_MULTI_LINGUAL_LABEL_MSG);?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/><br />
    <b><?=$tagLabel;?>:</b> #rn:msg:_MY_LABEL_#<br/>
    <b><?=$returnLabel;?>:</b> <?=sprintf(\RightNow\Utils\Config::getMessage(VALUE_OF_PCT_S_LBL), '_MY_LABEL_');?> <br/>
    <b><?=$inlineLabel;?>:</b> &lt;rn:widget path="custom/myWidget" label="#rn:msg:_MY_LABEL_#"/&gt;
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(CONFIG_VALUE_LBL);?></h3>
<h4>#rn:config:<i><?=\RightNow\Utils\Config::getMessage(CONFIG_SLOT_LBL);?>#</i></h4>
<p><?=\RightNow\Utils\Config::getMessage(CFG_TAG_DISP_CONFIG_VALS_PARAM_CFG_MSG);?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/><br />
    <b><?=$tagLabel;?>:</b> #rn:config:_MY_CONFIG_# or #rn:config:_MY_CONFIG_:RNW_UI#<br/>
    <b><?=$returnLabel;?>:</b> <?=sprintf(\RightNow\Utils\Config::getMessage(VALUE_OF_PCT_S_LBL), '_MY_CONFIG_');?> <br/>
    <b><?=$inlineLabel;?>:</b> &lt;a href="/app/#rn:config:_MY_CONFIG_##rn:session#"&gt;#rn:msg:_MY_LABEL_#&lt;/a&gt;
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(SESSION_PARAMETER_LBL);?></h3>
<h4>#rn:session#</h4>
<p><?=\RightNow\Utils\Config::getMessage(SESS_TAG_OUTPUT_USERS_SESS_URL_VAL_MSG);?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/><br />
    <b><?=$tagLabel;?>:</b> #rn:session# <br/>
    <b><?=$returnLabel;?>:</b> /session/L2NyZWF0ZWQvMTIwNzYwNTQ0OC9zaWQvcUQzUHBIKmk= <br/>
    <b><?=$inlineLabel;?>:</b> &lt;a href="/app/page/test#rn:session#"/&gt;Link&lt;/a&gt;
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(COMMUNITY_TOKEN_LBL);?></h3>
<h4>#rn:community_token:<i><?=\RightNow\Utils\Config::getMessage(OPENING_CHARACTER_LBL);?></i>#</h4>
<p><?=\RightNow\Utils\Config::getMessage(CMMUNITY_TOKEN_TAG_OUTPUT_SNGL_MSG);?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/><br />
    <b><?=$tagLabel;?>:</b> #rn:community_token:?# <br/>
    <b><?=$returnLabel;?>:</b> ?opentoken=cF9jaWQ9MTIAkGEnpsNOKnLge76OlNok2KngLeNaongkZv <br/>
    <b><?=$inlineLabel;?>:</b> &lt;a href="http://example.com/home#rn:community_token:?#"/&gt;Community Link&lt;/a&gt;
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(FLASH_DATA_LBL);?></h3>
<h4>#rn:flashdata:<i><?=\RightNow\Utils\Config::getMessage(FLASH_DATA_ITEM_KEY_LBL);?></i>#</h4>
<p><?=\RightNow\Utils\Config::getMessage(FLSHDT_TG_FLSHDT_FLSH_DT_SSS_DT_TT_BG_VL_MSG) ?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/><br />
    <b><?=$tagLabel;?>:</b> #rn:flashdata:_KEY_OF_ITEM_# <br/>
    <b><?=$returnLabel;?>:</b> <?= sprintf(\RightNow\Utils\Config::getMessage(VALUE_OF_PCT_S_LBL), 'flashData[_KEY_OF_ITEM_]') ?> <br/>
    <b><?=$inlineLabel;?>:</b>
    <pre>// Within a PHP controller
get_instance()->session->setFlashData( 'alert', 'Please check your email' );</pre>
    <pre><?= htmlspecialchars("<rn:condition flashdata_value_for=\"alert\">
    <strong>#rn:flashdata:alert#</strong>
</rn:condition>", ENT_QUOTES, 'UTF-8') ?></pre>
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(PROFILE_HDG);?></h3>
<h4>#rn:profile:<i><?=\RightNow\Utils\Config::getMessage(PROFILE_ITEM_KEY_LBL);?></i>#</h4>
<p><?=\RightNow\Utils\Config::getMessage(TAG_OUTPUT_VALUES_STORED_PROFILE_MSG) ?></p>
<b><?=\RightNow\Utils\Config::getMessage(COMMON_PROFILE_ITEM_KEYS_LBL)?>:</b>
<ul>
    <li>contactID</li>
    <li>orgID</li>
    <li>firstName</li>
    <li>lastName</li>
    <li>login</li>
    <li>email</li>
    <li>socialUserID</li>
</ul>
<div class="box">
    <b><?=$exampleLabel;?></b><br/><br />
    <b><?=$tagLabel;?>:</b> #rn:profile:_KEY_OF_ITEM_# <br/>
    <b><?=$returnLabel;?>:</b> <?= sprintf(\RightNow\Utils\Config::getMessage(VALUE_OF_PCT_S_LBL), 'profile[_KEY_OF_ITEM_]') ?> <br/>
    <b><?=$inlineLabel;?>:</b> &lt;a href="http://example.com/app/public_profile/user/#rn:profile:socialUserID#"/&gt;Public Profile&lt;/a&gt;
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(URL_PARAMETER_LBL);?></h3>
<h4>#rn:url_param:<i><?=\RightNow\Utils\Config::getMessage(URL_PARAMETER_KEY_LBL);?></i>#</h4>
<p><?=\RightNow\Utils\Config::getMessage(RN_URL_PARAM_TAG_RETRIEVE_KEY_VAL_MSG);?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/>
    <b>URL:</b> /app/answers/detail/a_id/1 <br />
    <b><?=$tagLabel;?>:</b> #rn:url_param:a_id# <br/>
    <b><?=$returnLabel;?>:</b> a_id/1 <br />
    <b><?=$inlineLabel;?>:</b> &lt;a href="/app/page/viewed_answer/#rn:url_param:a_id#"/&gt;Link&lt;/a&gt;
</div>

<br />
<h3><?=\RightNow\Utils\Config::getMessage(URL_PARAMETER_VALUE_LBL);?></h3>
<h4>#rn:url_param_value:<i><?=\RightNow\Utils\Config::getMessage(URL_PARAMETER_KEY_LBL);?></i>#</h4>
<p><?=\RightNow\Utils\Config::getMessage(RN_URL_PARAM_VAL_TAG_RETRIEVE_VAL_MSG);?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/>
    <b>URL:</b> /app/answers/detail/a_id/1 <br />
    <b><?=$tagLabel;?>:</b> #rn:url_param_value:a_id# <br/>
    <b><?=$returnLabel;?>:</b> 1 <br />
    <b><?=$inlineLabel;?>:</b> &lt;a href="/app/page/viewed_answer/a_id/#rn:url_param_value:a_id#"/&gt;Link&lt;/a&gt;
</div>


<br />
<h3><?=\RightNow\Utils\Config::getMessage(WIDGET_ATTRIBUTE_PHP_LBL);?></h3>
<h4>#rn:php:<i><?=\RightNow\Utils\Config::getMessage(PHP_CODE_LBL);?></i>#</h4>
<p><?=\RightNow\Utils\Config::getMessage(PHP_CODE_TAG_EXECUTE_PHP_CODE_RN_MSG);?></p>
<div class="box">
    <b><?=$exampleLabel;?></b><br/>
    <b><?=$tagLabel;?>:</b> &lt;?$phpVariable = "<?=\RightNow\Utils\Config::getMessage(SOME_TEXT_LBL);?>";?&gt;<br/>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;rn:widget path="path/to/widget" label="#rn:php:$phpVariable#" /&gt; <br/>
    <b><?=$returnLabel;?>:</b>&nbsp;<?=\RightNow\Utils\Config::getMessage(LABEL_ATTRIBUTE_WIDGET_LBL) . " '" . \RightNow\Utils\Config::getMessage(SOME_TEXT_LBL) . "'";?>  <br /><br />
    <b><?=$tagLabel;?>:</b> &lt;?$phpVariable = "<?=\RightNow\Utils\Config::ASTRgetMessage("someText");?>";?&gt;<br/>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&lt;rn:form post_handler="customRequest/#rn:php:$phpVariable#"/&gt; <br/>
    <b><?=$returnLabel;?>:</b>&nbsp;<?=\RightNow\Utils\Config::getMessage(POSTHANDLER_ATTRIB_CONTAIN_VALUE_H_UHK) . " '" . \RightNow\Utils\Config::getMessage(CUSTOMREQUESTSOMETEXT_LBL) . "'";?>  <br />
</div>
