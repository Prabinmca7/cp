<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="mobile.php" clickstream="answer_list"/>
<rn:container source_id="OKCSSearch">
<div id="rn_PageTitle" class="rn_Search">
    <div class="rn_Hero">
        <div class="rn_HeroInner">
            <div class="rn_SearchControls">
                <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
                <form method="get" action="/app/search">
                    <rn:container source_id="OKCSSearch">
                        <div class="rn_SearchInput">
                            <rn:widget path="searchsource/SourceSearchField" initial_focus="true" label_placeholder="#rn:msg:ASK_A_QUESTION_ELLIPSIS_MSG#"/>
                        </div>
                        <rn:widget path="searchsource/SourceSearchButton" initial_focus="true" search_results_url="/app/search"/>
                    </rn:container>
                    <div class="rn_StartOver">
                        <a href="/app/home" >#rn:msg:START_OVER_LBL#</a>
                    </div>
                </form>
            </div>
        </div>
    </div>  
</div>
</rn:container>
<div class="rn_Container">
    <section id="rn_PageContent" class="rn_AnswerList">
        <rn:container source_id="OKCSSearch" truncate_size="200">
            <div class="rn_Module">
                <rn:widget path="okcs/Facet" toggle_title="true"/>
            </div>
            <div id="rn_OkcsRightContainer" class="rn_Padding">
                <rn:widget path="okcs/SearchResult"/>
                <div class="rn_FloatRight rn_Padding">
                    <rn:widget path="okcs/OkcsPagination"/>
                </div>
            </div>
            <div class="rn_Module">
                <div class="rn_Padding">
                    <rn:widget path="okcs/SearchRating" toggle_title="true" />
                </div>
            </div>
        </rn:container>
    </section>
    </div>
