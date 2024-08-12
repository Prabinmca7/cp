<rn:meta title="#rn:msg:FIND_DISCUSSIONS_LBL#" template="mobile.php" clickstream="answer_list"/>

<rn:container source_id="SocialSearch,KFSearch" per_page="10" history_source_id="SocialSearch,KFSearch">
<div class="rn_Hero">
    <div class="rn_HeroInner">
        <div class="rn_SearchControls">
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <form onsubmit="return false;" class="translucent">
                <div class="rn_SearchInput">
                    <rn:widget path="searchsource/SourceSearchField" allow_empty_search="true" initial_focus="true" filter_label="Keyword" filter_type="query"/>
                </div>
                <rn:widget path="searchsource/SourceSearchButton" endpoint="/ci/ajaxRequest/search" history_source_id="SocialSearch" report_page_url="/app/social/questions/list"/>
            </form>

            <label class="rn_SearchFiltersLabel" for="rn_SearchFiltersToggle">#rn:msg:ADVANCED_LBL#</label>
            <input type="checkbox" id="rn_SearchFiltersToggle" role="button">
            <div class="rn_SearchFilters rn_SocialSearchFilters">
                <rn:widget path="searchsource/MobileSourceProductCategorySearchFilter" verify_permissions="true"/>

                <rn:container source_id="SocialSearch">
                <rn:widget path="searchsource/SourceFilter"
                    filter_type="updatedTime"
                    label_default="--"
                    labels="#rn:php:\RightNow\Utils\Config::getMessage(LAST_DAY_LBL) . ',' . \RightNow\Utils\Config::getMessage(LAST_WEEK_LBL) . ',' . \RightNow\Utils\Config::getMessage(LAST_MONTH_LBL) . ',' . \RightNow\Utils\Config::getMessage(LAST_YEAR_LBL)#"
                    label_input="#rn:msg:FILTER_BY_AGE_LBL#"/>
                <rn:widget path="searchsource/SourceFilter"
                    filter_type="numberOfBestAnswers"
                    label_default="--"
                    labels="#rn:php:\RightNow\Utils\Config::getMessage(YES_LBL) . ','  . \RightNow\Utils\Config::getMessage(NO_LBL)#"
                    label_input="#rn:msg:FILTER_BY_BEST_ANSWER_LBL#"/>
                </rn:container>
            </div>
        </div>
    </div>
</div>
</rn:container>

<div class="rn_Container">
    <div class="rn_PageContent rn_QuestionList">
        <h2>#rn:msg:RESULTS_FROM_THE_COMMUNITY_LBL#</h2>

        <rn:condition flashdata_value_for="info">
            <div class="rn_MessageBox rn_InfoMessage">
                #rn:flashdata:info#
            </div>
        </rn:condition>

        <rn:container source_id="SocialSearch" per_page="10" history_source_id="SocialSearch">
        <div>
            <rn:widget path="searchsource/SourceResultDetails" />
            <rn:widget path="searchsource/SocialResultListing" show_dates="false" more_link_url=""/>
            <rn:widget path="searchsource/SourcePagination"/>
        </div>
        </rn:container>
    </div>
</div>
