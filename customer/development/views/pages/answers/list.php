<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="standard.php" clickstream="answer_list"/>

<rn:condition url_parameter_check="kw != null">
    <rn:container source_id="KFSearch,SocialSearch" per_page="10" history_source_id="KFSearch,SocialSearch">
    <div class="rn_Hero">
        <div class="rn_HeroInner">
            <div class="rn_SearchControls">
                <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
                <form onsubmit="return false;">
                    <div class="rn_SearchInput">
                        <rn:widget path="searchsource/SourceSearchField" initial_focus="true" filter_label="Keyword" filter_type="query"/>
                    </div>
                    <rn:widget path="searchsource/SourceSearchButton" endpoint="/ci/ajaxRequest/search" history_source_id="KFSearch" report_page_url="/app/answers/list"/>
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
        <div class="rn_PageContent rn_KBAnswerList">
            <div class="rn_HeaderContainer">
                <h2>#rn:msg:PUBLISHED_ANSWERS_LBL#</h2>
            </div>

            <rn:condition flashdata_value_for="info">
                <div class="rn_MessageBox rn_InfoMessage">
                    #rn:flashdata:info#
                </div>
            </rn:condition>

            <rn:container source_id="KFSearch" per_page="10" history_source_id="KFSearch">
            <div>
                <rn:widget path="searchsource/SourceResultDetails"/>
                <rn:widget path="searchsource/SourceResultListing" more_link_url=""/>
                <rn:widget path="searchsource/SourcePagination"/>
            </div>
            </rn:container>
        </div>

        <aside class="rn_SideRail" role="complementary">
            <rn:widget path="utils/ContactUs"/>
            <rn:widget path="discussion/RecentlyViewedContent"/>
            <rn:widget path="searchsource/SummaryResultListing" label_heading="#rn:msg:COMMUNITY_RESULTS_LBL#" results_type="SocialQuestions" more_link_url="/app/social/questions/list" per_page="10"/>
        </aside>
    </div>
<rn:condition_else/>
    <rn:container report_id="176">
    <div class="rn_Hero">
        <div class="rn_HeroInner">
            <div class="rn_SearchControls">
                <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
                <form onsubmit="return false;">
                    <div class="rn_SearchInput">
                        <rn:widget path="search/KeywordText" label_text="#rn:msg:FIND_THE_ANSWER_TO_YOUR_QUESTION_CMD#" initial_focus="true"/>
                    </div>
                    <rn:widget path="search/SearchButton" force_page_flip="true"/>
                </form>

                <div class="rn_SearchFilters">
                    <rn:widget path="search/ProductCategorySearchFilter"/>
                    <rn:widget path="search/ProductCategorySearchFilter" filter_type="Category"/>
                </div>
            </div>
        </div>
    </div>

    <div class="rn_Container">
        <div class="rn_PageContent rn_KBAnswerList">
            <div class="rn_HeaderContainer">
                <h2>#rn:msg:PUBLISHED_ANSWERS_LBL#</h2>
                <rn:widget path="knowledgebase/RssIcon" />
            </div>

            <rn:condition flashdata_value_for="info">
                <div class="rn_MessageBox rn_InfoMessage">
                    #rn:flashdata:info#
                </div>
            </rn:condition>

            <div>
                <rn:widget path="reports/ResultInfo" show_no_results_msg_without_search_term="true"/>
                <rn:widget path="reports/Multiline" hide_columns="answers.updated"/>
                <rn:widget path="reports/Paginator"/>
            </div>
        </div>

        <aside class="rn_SideRail" role="complementary">
            <rn:widget path="utils/ContactUs"/>
            <rn:widget path="discussion/RecentlyViewedContent"/>
        </aside>
    </div>
    </rn:container>
</rn:condition>
