<!DOCTYPE html>
<html lang="#rn:language_code#">
<rn:meta clickstream="chat_landing" javascript_module="standard"/>
<head>
    <meta charset="utf-8"/>
    <title><rn:page_title/></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/> 
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <!--[if lt IE 9]><script src="/euf/core/static/html5.js"></script><![endif]-->
    <rn:widget path="search/BrowserSearchPlugin" pages="home, answers/list, answers/detail" />
    <rn:theme path="/euf/assets/themes/standard" css="site.css"/>
    <rn:head_content/>
    <link rel="icon" href="/euf/assets/images/favicon.png" type="image/png"/>
    <rn:widget path="utils/ClickjackPrevention"/>
    <rn:widget path="utils/AdvancedSecurityHeaders"/>
</head>
<body class="yui-skin-sam yui3-skin-sam" itemscope itemtype="http://schema.org/WebPage">
<a href="#rn_MainContent" class="rn_SkipNav rn_ScreenReaderOnly">#rn:msg:SKIP_NAVIGATION_CMD#</a>

<header>
    <rn:widget path="utils/CapabilityDetector"/>

    <nav>
        <div class="rn_NavigationBar" aria-label="#rn:msg:MAIN_NAVIGATION_BAR_LBL#">
            <input type="checkbox" id="rn_NavigationMenuButtonToggle" class="rn_ScreenReaderOnly" />
            <label class="rn_NavigationMenuButton" for="rn_NavigationMenuButtonToggle">
                #rn:msg:MENU_LWR_LBL#
            </label>

            <ul class="rn_NavigationMenu">
                 <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:SUPPORT_HOME_TAB_HDG#" link="/app/#rn:config:CP_HOME_URL#" pages="home"/></li>
                 <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:ASK_QUESTION_HDG#" link="/app/ask" pages="ask, ask_confirm"/></li>
                 <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:COMMUNITY_HOME_LBL#" link="/app/social/home" pages="social/home"/></li>
            </ul>
        </div>

        <div class="rn_LoginStatus">
            <rn:condition logged_in="false">
                <rn:widget path="login/AccountDropdown" subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > account/overview"
                sub:input_Contact.Emails.PRIMARY.Address:label_input="#rn:msg:EMAIL_ADDR_LBL#"
                sub:input_Contact.Emails.PRIMARY.Address:required="true"
                sub:input_Contact.Emails.PRIMARY.Address:validate_on_blur="false"
                sub:input_Contact.Login:label_input="#rn:msg:USERNAME_LBL#"
                sub:input_Contact.Login:required="true"
                sub:input_Contact.Login:validate_on_blur="false"
                sub:input_Contact.Name.First:required="true"
                sub:input_Contact.Name.First:label_input="#rn:msg:FIRST_NAME_LBL#"
                sub:input_Contact.Name.Last:required="true"
                sub:input_Contact.Name.Last:label_input="#rn:msg:LAST_NAME_LBL#"
                sub:input_SocialUser.DisplayName:label_input="#rn:msg:DISPLAY_NAME_LBL#"
                sub:input_Contact.NewPassword:label_input="#rn:msg:PASSWORD_LBL#"
                />
            <rn:condition_else/>
                <rn:condition is_social_moderator="true">
                    <rn:widget path="login/AccountDropdown" subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > account/overview, #rn:msg:SUPPORT_HISTORY_LBL# > account/questions/list, #rn:msg:ACCOUNT_SETTINGS_LBL# > account/profile, #rn:msg:PUBLIC_PROFILE_LBL# > #rn:config:CP_PUBLIC_PROFILE_URL#/user/#rn:profile:socialUserID#, #rn:msg:MODERATION_LBL# > social/moderate/overview"/>
                <rn:condition_else/>
                    <rn:condition is_active_social_user="true">
                        <rn:widget path="login/AccountDropdown" subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > account/overview, #rn:msg:SUPPORT_HISTORY_LBL# > account/questions/list, #rn:msg:ACCOUNT_SETTINGS_LBL# > account/profile, #rn:msg:PUBLIC_PROFILE_LBL# > #rn:config:CP_PUBLIC_PROFILE_URL#/user/#rn:profile:socialUserID#"/>
                    <rn:condition_else/>
                        <rn:widget path="login/AccountDropdown" subpages="#rn:msg:ACCOUNT_OVERVIEW_LBL# > account/overview, #rn:msg:SUPPORT_HISTORY_LBL# > account/questions/list, #rn:msg:ACCOUNT_SETTINGS_LBL# > account/profile"/>
                    </rn:condition>
                </rn:condition>
            </rn:condition>
        </div>

        <rn:condition hide_on_pages="home, public_profile, results, answers/list, social/questions/list, social/home">
            <div class="rn_SearchBar">
                <rn:condition url_parameter_check="c != null">
                    <rn:widget path="search/SimpleSearch" filter_type="category" report_page_url="/app/results"/>
                <rn:condition_else/>
                    <rn:widget path="search/SimpleSearch" report_page_url="/app/results"/>
                </rn:condition>
            </div>
        </rn:condition>
    </nav>
</header>

<div class="rn_Body">
    <div class="rn_MainColumn" role="main">
        <a id="rn_MainContent"></a>
        <rn:page_content/>
    </div>
</div>

<footer class="rn_Footer">
    <div class="rn_Container">
        <rn:widget path="search/ProductCategoryList" report_page_url="/app/products/detail"/>
        <div class="rn_Misc">
            <rn:widget path="utils/PageSetSelector"/>
            <rn:widget path="utils/OracleLogo"/>
        </div>
    </div>
</footer>
</body>
</html>
