<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="mobile.php" clickstream="home"/>

<div class="rn_Hero">
    <div class="rn_HeroInner">
        <div class="rn_HeroCopy">
            <h1>#rn:msg:WERE_HERE_TO_HELP_LBL#</h1>
        </div>
        <div class="rn_SearchControls">
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <form method="get" action="/app/results">
                <rn:container source_id="KFSearch">
                    <div class="rn_SearchInput">
                        <rn:widget path="searchsource/SourceSearchField" initial_focus="true"/>
                    </div>
                    <rn:widget path="searchsource/SourceSearchButton" search_results_url="/app/results"/>
                </rn:container>
            </form>
        </div>
    </div>
</div>

<div class="rn_PageContent rn_Home">
    <div class="rn_Container">
        <rn:widget path="navigation/VisualProductCategorySelector" maximum_items="6" numbered_pagination="true"/>
    </div>

    <div class="rn_PopularKB">
        <div class="rn_Container">
            <h2>#rn:msg:POPULAR_PUBLISHED_ANSWERS_LBL#</h2>
            <rn:widget path="reports/TopAnswers" show_excerpt="true" excerpt_max_length="100" limit="5"/>
            <span class="rn_AnswersLink">
                <a href="/app/answers/list#rn:session#">#rn:msg:SHOW_MORE_PUBLISHED_ANSWERS_LBL#</a>
            </span>
        </div>
    </div>

    <div class="rn_PopularSocial">
        <div class="rn_Container">
            <h2>#rn:msg:RECENT_COMMUNITY_DISCUSSIONS_LBL#</h2>
            <rn:widget path="discussion/RecentlyAnsweredQuestions" avatar_size="small" display_answers="false" maximum_questions="5"/>
            <span class="rn_DiscussionsLink">
                <a href="/app/social/questions/list/kw/*#rn:session#">#rn:msg:SHOW_MORE_COMMUNITY_DISCUSSIONS_LBL#</a>
            </span>
        </div>
    </div>
</div>
