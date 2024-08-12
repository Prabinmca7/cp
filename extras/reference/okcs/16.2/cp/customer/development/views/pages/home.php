<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="standard.php" clickstream="home"/>

<div class="rn_Hero">
    <div class="rn_HeroInner">
        <div class="rn_HeroCopy">
            <h1>#rn:msg:WERE_HERE_TO_HELP_LBL#</h1>
        </div>
        <div class="rn_SearchControls">
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <form method="get" action="/app/search">
                <rn:container source_id="OKCSSearch">
                    <div class="rn_SearchInput">
                        <rn:widget path="searchsource/SourceSearchField" initial_focus="true" label_placeholder="#rn:msg:ASK_A_QUESTION_ELLIPSIS_MSG#"/>
                    </div>
                    <rn:widget path="okcs/RecentSearches"/>
                    <rn:widget path="searchsource/SourceSearchButton" initial_focus="true" search_results_url="/app/search"/>
                </rn:container>
            </form>
        </div>
    </div>
</div>

<div class="rn_PageContent rn_Home">
    <div class="rn_PopularKB">
        <div class="rn_Container">
            <rn:widget path="okcs/AnswerList" type="popular" target="_self" view_type="list"/>
        </div>
    </div>

    <div class="rn_PopularKB rn_RecentKB">
        <div class="rn_Container">
            <rn:widget path="okcs/AnswerList" type="recent" target="_self" view_type="list"/>
        </div>
    </div>
</div>
