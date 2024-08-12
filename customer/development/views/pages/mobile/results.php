<rn:meta title="#rn:msg:FIND_ANSWERS_AND_DISCUSSIONS_LBL#" template="mobile.php" clickstream="answer_list"/>

<rn:container source_id="KFSearch,SocialSearch" per_page="5">
<div class="rn_Hero">
    <div class="rn_HeroInner">
        <div class="rn_SearchControls">
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <form onsubmit="return false;">
                <div class="rn_SearchInput">
                    <rn:widget path="searchsource/SourceSearchField" initial_focus="true" filter_label="Keyword" filter_type="query"/>
                </div>
                <rn:widget path="searchsource/SourceSearchButton" history_source_id="KFSearch"/>
            </form>

            <div class="rn_SearchFilters">
                <rn:widget path="searchsource/MobileSourceProductCategorySearchFilter"/>
                <rn:widget path="searchsource/MobileSourceProductCategorySearchFilter" filter_type="Category"/>
            </div>
        </div>
    </div>
</div>
</rn:container>

<div class="rn_Container">
    <div class="rn_PageContent rn_ResultList">
        <div class="rn_KBAnswerResults">
            <rn:container source_id="KFSearch" per_page="5">
                <rn:widget path="searchsource/SourceResultDetails"/>
                <rn:widget path="searchsource/SourceResultListing" label_heading="#rn:msg:PUBLISHED_ANSWERS_LBL#"/>
            </rn:container>
        </div>

        <div class="rn_QuestionResults">
            <rn:container source_id="SocialSearch" per_page="5">
                <rn:widget path="searchsource/SourceResultDetails"/>
                <rn:widget path="searchsource/SocialResultListing" label_heading="#rn:msg:RESULTS_FROM_THE_COMMUNITY_LBL#" more_link_url="/app/social/questions/list"/>
            </rn:container>
        </div>
    </div>

</div>
