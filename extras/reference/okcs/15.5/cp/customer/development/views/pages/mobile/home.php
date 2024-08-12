<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="mobile.php" clickstream="home"/>

<section id="rn_PageContent" class="rn_Home">
    <h1 class="rn_ScreenReaderOnly"><rn:page_title/></h1>
    <div class="rn_Module">
        <rn:widget path="navigation/Accordion" toggle="rn_AccordTriggerPopular"/>
        <h2 id="rn_AccordTriggerPopular" class="rn_Expanded">#rn:msg:MOST_POPULAR_ANSWERS_LBL#<span class="rn_Expand"></span></h2>
        <div class="rn_Report">
            <rn:widget path="okcs/AnswerList" type="popular" show_headers="false" per_page="5" target="_self"/>
        </div>
    </div>
    <div class="rn_Module">
        <rn:widget path="navigation/Accordion" toggle="rn_AccordTriggerRecent"/>
        <h2 id="rn_AccordTriggerRecent" class="rn_Collapsed">#rn:msg:MOST_RECENT_ANSWERS_LBL#<span class="rn_Expand"></span></h2>
        <div class="rn_Report rn_Hidden">
            <rn:widget path="okcs/AnswerList" type="recent" show_headers="false" per_page="5" target="_self"/>
        </div>
    </div>
    <div class="rn_Module">
        <rn:widget path="navigation/Accordion" toggle="rn_ContactTrigger"/>
        <h2 id="rn_ContactTrigger" class="rn_Expanded">#rn:msg:CONTACT_US_LBL#<span class="rn_Expand"></span></h2>
        <ul class="rn_ContactChannels">
            <rn:condition config_check="MOD_CHAT_ENABLED == true">
                <li>
                    <a class="rn_ChatChannel" href="/app/chat/chat_launch">#rn:msg:CHAT_LBL#</a>
                </li>
            </rn:condition>
            <li>
                <a class="rn_AskChannel" href="/app/ask">#rn:msg:EMAIL_US_LBL#</a>
            </li>
            <li>
                <a class="rn_VoiceChannel" href="javascript:void(0);">#rn:msg:CALL_US_DIRECTLY_LBL#</a>
            </li>
            <rn:condition config_check="COMMUNITY_ENABLED == true">
                <li>
                    <a class="rn_CommunityChannel" href="javascript:void(0);">#rn:msg:ASK_THE_COMMUNITY_LBL#</a>
                </li>
            </rn:condition>
        </ul>
    </div>
</section>
