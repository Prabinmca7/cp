<rn:meta title="#rn:msg:REGISTERED_PRODUCTS_LBL#" template="standard.php" clickstream="asset_list" login_required="true" force_https="true"/>
<rn:container report_id="228" per_page="10">
<div id="rn_PageTitle" class="rn_QuestionList">
    <div id="rn_SearchControls">
        <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
        <form onsubmit="return false;">
            <div class="rn_RegisteredProductSearchInput">
                <div class="rn_AdvancedFilter rn_Label">
                    <rn:widget path="search/ProductCatalogSearchFilter"/>
                </div>
                <div class="rn_AdvancedFilter">
                    <rn:widget path="search/FilterDropdown" filter_name="assets.status_id" options_to_ignore="28, 102"/>
                </div>
                <div class="rn_AdvancedFilter">
                    <rn:widget path="search/KeywordText"/>
                    <div class="rn_Span">
                        <span>#rn:msg:PERFORM_WILDCARD_SRCH_ASTERISK_CMD#</span>
                    </div>
                </div>
            </div>
            <rn:widget path="search/SearchButton"/>
        </form>
        <rn:widget path="search/DisplaySearchFilters"/>
    </div>
</div>
<div id="rn_PageContent" class="rn_QuestionList">
    <div class="rn_Padding">
        <rn:widget path="reports/ResultInfo"/>
        <rn:widget path="reports/Grid" add_params_to_url="" label_caption="<span class='rn_ScreenReaderOnly'>#rn:msg:YOUR_REGISTERED_PRODUCTS_LBL#</span>"/>
        <rn:widget path="reports/Paginator"/>
        <a href="/app/account/assets/serialnumber_validate#rn:session#">#rn:msg:REGISTER_A_NEW_PRODUCT_LBL#</a><br/>
    </div>
</div>
</rn:container>