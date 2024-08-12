<rn:meta javascript_module="mobile"/>
<!DOCTYPE html>
<html lang="#rn:language_code#">
    <head>
        <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
        <meta charset="utf-8"/>
        <title><rn:page_title/></title>
        <rn:theme path="/euf/assets/themes/mobile" css="site.css"/>
        <rn:head_content/>
        <link rel="icon" href="/euf/assets/images/favicon.png" type="image/png">
        <rn:widget path="utils/ClickjackPrevention"/>
        <rn:widget path="utils/AdvancedSecurityHeaders"/>
    </head>
    <body itemscope itemtype="http://schema.org/WebPage">
        <header>
            <rn:widget path="utils/CapabilityDetector" pass_page_set="mobile"/>

            <nav>
                <div class="rn_NavigationBar">
                    <label class="rn_NavigationMenuButton" for="rn_NavigationMenuButtonToggle">#rn:msg:MENU_LBL#</label>
                    <input type="checkbox" id="rn_NavigationMenuButtonToggle" />

                    <ul class="rn_NavigationMenu">
                        <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:SUPPORT_HOME_TAB_HDG#" link="/app/#rn:config:CP_HOME_URL#" pages="home"/></li>
                        <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:COMMUNITY_HOME_LBL#" link="/app/social/home" pages="social/home"/></li>
                        <rn:condition config_check="MOD_CHAT_ENABLED == false" config_check="MOD_ENGAGEMENT_CHANNELS_ENABLED == false">
                            <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:CONTACT_US_LBL#" link="/app/ask" subpages="#rn:msg:EMAIL_US_LBL# > /app/ask, #rn:msg:ASK_THE_COMMUNITY_LBL# > /app/social/ask, #rn:msg:CALL_US_DIRECTLY_LBL# > javascript:void(0); "/></li>
                        <rn:condition_else/>
                            <li><rn:widget path="navigation/NavigationTab" label_tab="#rn:msg:CONTACT_US_LBL#" link="/app/ask" subpages="#rn:msg:EMAIL_US_LBL# > /app/ask, #rn:msg:ASK_THE_COMMUNITY_LBL# > /app/social/ask, #rn:msg:CHAT_LBL# > /app/chat/chat_launch, #rn:msg:CALL_US_DIRECTLY_LBL# > javascript:void(0); "/></li>
                        </rn:condition>
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
                        <rn:condition is_active_social_user="true">
                            <rn:widget path="login/AccountDropdown" label_menu_accessibility="#rn:msg:DOUBLE_TAP_TO_OPEN_OR_CLOSE_THE_MENU_LBL#" subpages="#rn:msg:SUPPORT_HISTORY_LBL# > account/questions/list, #rn:msg:ACCOUNT_SETTINGS_LBL# > account/profile, #rn:msg:PUBLIC_PROFILE_LBL# > #rn:config:CP_PUBLIC_PROFILE_URL#/user/#rn:profile:socialUserID#"/>
                        <rn:condition_else/>
                            <rn:widget path="login/AccountDropdown" label_menu_accessibility="#rn:msg:DOUBLE_TAP_TO_OPEN_OR_CLOSE_THE_MENU_LBL#" subpages="#rn:msg:SUPPORT_HISTORY_LBL# > account/questions/list, #rn:msg:ACCOUNT_SETTINGS_LBL# > account/profile"/>
                        </rn:condition>
                    </rn:condition>
                </div>

                <rn:condition hide_on_pages="home, results, answers/list, social/questions/list, social/home, account/profile, utils/create_account, utils/account_assistance, utils/login_form">
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
            <rn:page_content/>
            </div>
        </div>

        <footer class="rn_Footer">
            <div class="rn_Container">
                <rn:widget path="utils/PageSetSelector"/>
                <a href="javascript:void(0);" class="rn_ScrollToTop" onClick="window.scroll(0, 0);">#rn:msg:TOP_LBL#</a>
                <div class="rn_Credit">
                    <rn:widget path="utils/OracleLogo"/>
                </div>
            </div>
        </footer>
    </body>
</html>
