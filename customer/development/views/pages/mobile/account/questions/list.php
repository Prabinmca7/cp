<rn:meta title="#rn:msg:SUPPORT_HISTORY_LBL#" template="mobile.php" clickstream="incident_list" login_required="true" force_https="true" />
<rn:container report_id="196">
<div class="rn_Hero">
    <div class="rn_HeroInner">
        <div class="rn_SearchControls">
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <form onsubmit="return false;" class="translucent">
                <rn:container report_id="196">
                <div class="rn_SearchInput">
                    <rn:widget path="search/KeywordText" label_text="#rn:msg:SEARCH_YOUR_SUPPORT_HISTORY_CMD#" label_placeholder="#rn:msg:SEARCH_YOUR_SUPPORT_HISTORY_CMD#" initial_focus="true"/>
                </div>
                <rn:widget path="search/SearchButton"/>
                </rn:container>
            </form>
            <label class="rn_SearchFiltersLabel" for="rn_SearchFiltersToggle">#rn:msg:ADVANCED_LBL#</label>
            <input type="checkbox" id="rn_SearchFiltersToggle">
            <div class="rn_SearchFilters rn_SocialSearchFilters">
                <rn:widget path="search/MobileProductCategorySearchFilter" />
                <rn:widget path="search/MobileProductCategorySearchFilter" filter_type="Category"/>
            </div>
        </div>
    </div>
</div>
<div class="rn_PageContent rn_Container">
    <h2 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_RESULTS_CMD#</h2>
    <rn:widget path="reports/ResultInfo"/>
    <rn:widget path="reports/MobileMultiline" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:SEARCH_YOUR_SUPPORT_HISTORY_CMD#</span>"/>
    <rn:widget path="reports/Paginator"/>
</div>
</rn:container>
