<rn:meta javascript_module="mobile"/>
<!DOCTYPE html>
<html lang="#rn:language_code#">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
        <meta charset="utf-8"/>
        <title><rn:page_title/></title>
        <rn:theme path="/euf/assets/themes/mobile" css="site.css,
        {CORE_ASSETS}/thirdParty/css/font-awesome.min.css"/>
        <rn:head_content/>
        <link rel="icon" href="/euf/assets/images/favicon.png" type="image/png">
        <rn:widget path="utils/ClickjackPrevention"/>
    </head>
    <body>
        <rn:widget path="utils/CapabilityDetector" pass_page_set="mobile"/>
        <header role="banner">
            <nav id="rn_Navigation" role="navigation">
                <span class="rn_FloatLeft">
                    <rn:widget path="navigation/MobileNavigationMenu" submenu="rn_MenuList"/>
                </span>
                <ul id="rn_MenuList" class="rn_Hidden">
                    <li>
                        <a href="/app/#rn:config:CP_HOME_URL##rn:session#">#rn:msg:HOME_LBL#</a>
                    </li>
                    <rn:condition config_check="OKCS_ENABLED == true">
                    <li>
                        <a href="/app/browse#rn:session#">#rn:msg:ANSWERS_HDG#</a>
                    </li>
                    </rn:condition>
                    <li>
                        <a href="javascript:void(0);" class="rn_ParentMenu">#rn:msg:CONTACT_US_LBL#</a>
                        <ul class="rn_Submenu rn_Hidden">
                            <rn:condition config_check="MOD_CHAT_ENABLED == true">
                                <li><a href="/app/chat/chat_launch#rn:session#">#rn:msg:CHAT_LBL#</a></li>
                            </rn:condition>
                            <li><a href="/app/ask#rn:session#">#rn:msg:EMAIL_US_LBL#</a></li>
                            <li><a href="javascript:void(0);">#rn:msg:CALL_US_DIRECTLY_LBL#</a></li>
                            <rn:condition config_check="COMMUNITY_ENABLED == true">
                                <li><a href="javascript:void(0);">#rn:msg:ASK_THE_COMMUNITY_LBL#</a></li>
                            </rn:condition>
                        </ul>
                    </li>
                    <li>
                        <a href="javascript:void(0);" class="rn_ParentMenu">#rn:msg:YOUR_ACCOUNT_LBL#</a>
                        <ul class="rn_Submenu rn_Hidden">
                            <rn:condition logged_in="false">
                                <rn:condition config_check="PTA_ENABLED == true">
                                    <li><a href="javascript:void(0);">#rn:msg:SIGN_UP_LBL#</a></li>
                                    <li><a href="javascript:void(0);">#rn:msg:LOG_IN_LBL#</a></li>
                                <rn:condition_else>
                                    <li><a href="/app/utils/create_account#rn:session#">#rn:msg:SIGN_UP_LBL#</a></li>
                                    <li><a href="/app/#rn:config:CP_LOGIN_URL##rn:session#">#rn:msg:LOG_IN_LBL#</a></li>
                                </rn:condition>
                                <li><a href="/app/#rn:config:CP_ACCOUNT_ASSIST_URL##rn:session#">#rn:msg:ACCOUNT_ASSISTANCE_LBL#</a></li>
                            </rn:condition>
                            <li><a href="/app/account/questions/list#rn:session#">#rn:msg:VIEW_YOUR_SUPPORT_HISTORY_CMD#</a></li>
                            <li><a href="/app/account/profile#rn:session#">#rn:msg:CHANGE_YOUR_ACCOUNT_SETTINGS_CMD#</a></li>
                        </ul>
                    </li>
                </ul>
                <span class="rn_FloatRight rn_Search" role="search">
                    <rn:widget path="navigation/MobileNavigationMenu" label_button="#rn:msg:SEARCH_LBL#<img src='images/search.png' alt='#rn:msg:SEARCH_LBL#'/>" submenu="rn_SearchForm"/>
                </span>
                <div id="rn_SearchForm" class="rn_Hidden">
                    <form method="get" action="/app/search">
                        <rn:container source_id="OKCSSearch">
                            <rn:widget path="searchsource/SourceSearchField" label_placeholder="#rn:msg:ENTER_A_QUESTION_ELLIPSIS_MSG#"/>
                            <rn:widget path="searchsource/SourceSearchButton" search_results_url="/app/search"/>
                            <rn:widget path="okcs/SupportedLanguages" />
                        </rn:container>
                    </form>
                </div>
            </nav>
        </header>

        <section role="main">
            <rn:page_content/>
        </section>

        <footer role="contentinfo">
                <div>
                <rn:condition logged_in="true">
                    <rn:field name="Contact.Emails.PRIMARY.Address"/><rn:widget path="login/LogoutLink"/>
                <rn:condition_else />
                    <rn:condition config_check="PTA_ENABLED == true">
                        <a href="javascript:void(0);">#rn:msg:LOG_IN_LBL#</a>
                    <rn:condition_else/>
                        <a href="/app/#rn:config:CP_LOGIN_URL##rn:session#">#rn:msg:LOG_IN_LBL#</a>
                    </rn:condition>
                </rn:condition>
                <br/><br/>
            </div>
            <rn:condition hide_on_pages="utils/guided_assistant">
                <rn:widget path="utils/PageSetSelector"/>
            </rn:condition>
            <div class="rn_FloatLeft"><a href="javascript:window.scrollTo(0, 0);">#rn:msg:ARR_BACK_TO_TOP_LBL#</a></div>
            <div class="rn_FloatRight">Powered by <a href="#rn:config:rightnow_url#">Oracle</a></div>
            <br/><br/>
        </footer>
    </body>
</html>
