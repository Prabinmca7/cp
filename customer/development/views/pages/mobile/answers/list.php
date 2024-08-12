<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="mobile.php" clickstream="answer_list"/>

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
                    <rn:widget path="searchsource/MobileSourceProductCategorySearchFilter"/>
                    <rn:widget path="searchsource/MobileSourceProductCategorySearchFilter" filter_type="Category"/>
                </div>
            </div>
        </div>
    </div>
    </rn:container>

    <div class="rn_Container">
        <div class="rn_PageContent rn_KBAnswerList">
            <h2>#rn:msg:PUBLISHED_ANSWERS_LBL#</h2>
            
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
                    <rn:widget path="search/MobileProductCategorySearchFilter"/>
                    <rn:widget path="search/MobileProductCategorySearchFilter" filter_type="Category"/>
                </div>
            </div>
        </div>
    </div>

    <div class="rn_Container">
        <div class="rn_PageContent rn_KBAnswerList">
            <h2>#rn:msg:PUBLISHED_ANSWERS_LBL#</h2>
            
            <rn:condition flashdata_value_for="info">
                <div class="rn_MessageBox rn_InfoMessage">
                    #rn:flashdata:info#
                </div>
            </rn:condition>

            <div>
                <rn:widget path="reports/ResultInfo"/>
                <rn:widget path="reports/MobileMultiline"/>
                <rn:widget path="reports/Paginator"/>
            </div>
        </div>
    </div>
    </rn:container>
</rn:condition>
