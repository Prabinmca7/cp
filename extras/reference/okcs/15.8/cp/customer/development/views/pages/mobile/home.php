<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="mobile.php" clickstream="home"/>

<div class="rn_Hero">
    <div class="rn_HeroInner">
        <div class="rn_HeroCopy">
            <h1>#rn:msg:WERE_HERE_TO_HELP_LBL#</h1>
        </div>
        <div class="rn_SearchControls">
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <form method="get" action="/app/search">
                <rn:container source_id="KFSearch">
                    <div class="rn_SearchInput">
                        <rn:widget path="searchsource/SourceSearchField" initial_focus="true" label_placeholder="#rn:msg:ASK_A_QUESTION_ELLIPSIS_MSG#"/>
                    </div>
                    <rn:widget path="searchsource/SourceSearchButton" initial_focus="true" search_results_url="/app/search"/>
                </rn:container>
            </form>
        </div>
    </div>
</div>

<div class="rn_PageContent rn_Home">
    <div class="rn_Container">
        <div class="rn_PopularKB">
           <rn:widget path="navigation/Accordion" toggle="rn_AccordTriggerPopular"/>
            <h2 id="rn_AccordTriggerPopular" class="rn_Expanded">#rn:msg:MOST_POPULAR_ANSWERS_LBL#<span class="rn_Expand"></span></h2>
            <div class="rn_Report">
                <rn:widget path="okcs/AnswerList" type="popular" show_headers="false" per_page="5" target="_self"/>
            </div>
        </div>
        <div class="rn_PopularKB">
           <rn:widget path="navigation/Accordion" toggle="rn_AccordTriggerRecent"/>
            <h2 id="rn_AccordTriggerRecent" class="rn_Collapsed">#rn:msg:MOST_RECENT_ANSWERS_LBL#<span class="rn_Expand"></span></h2>
            <div class="rn_Report rn_Home_Hidden">
                <rn:widget path="okcs/AnswerList" type="recent" show_headers="false" per_page="5" target="_self"/>
            </div>
        </div>
    </div>
</div>
