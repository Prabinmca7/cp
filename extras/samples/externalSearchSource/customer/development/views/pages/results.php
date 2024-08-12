<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="standard.php" clickstream="answer_list"/>

<rn:container source_id="DDGSearch,KFSearch,SocialSearch" per_page="4">
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
                <rn:widget path="searchsource/SourceProductCategorySearchFilter"/>
                <rn:widget path="searchsource/SourceProductCategorySearchFilter" filter_type="Category"/>
            </div>
        </div>
    </div>
</div>
</rn:container>

<div class="rn_Container">
    <div class="rn_PageContent rn_AnswerList">
        <div>
            <rn:container source_id="KFSearch" per_page="4">
                <rn:widget path="searchsource/SourceResultDetails"/>
                <rn:widget path="searchsource/SourceResultListing" label_heading="<i role='presentation' aria-hidden='true' class='fa fa-lightbulb-o'></i> Knowledge Base"/>
            </rn:container>
        </div>

        <div>
            <rn:container source_id="SocialSearch" per_page="4">
                <rn:widget path="searchsource/SourceResultDetails"/>
                <rn:widget path="searchsource/SocialResultListing" label_heading="<i role='presentation' aria-hidden='true' class='fa fa-comments'></i> Social" more_link_url="/app/social/questions/list"/>
            </rn:container>
        </div>

        <div>
            <rn:container source_id="DDGSearch" per_page="4">
                <rn:widget path="searchsource/SourceResultDetails"/>
                <rn:widget path="searchsource/SourceResultListing" label_heading="<i role='presentation' aria-hidden='true' class='fa fa-paperclip'></i> Duck Duck Go" more_link_url=""/>
            </rn:container>
        </div>
    </div>

    <aside class="rn_SideRail" role="complementary">
        <rn:widget path="utils/ContactUs"/>
        <rn:widget path="discussion/RecentlyViewedContent"/>
    </aside>
</div>
