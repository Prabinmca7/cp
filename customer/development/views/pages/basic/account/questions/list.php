<rn:meta title="#rn:msg:SUPPORT_HISTORY_LBL#" template="basic.php" clickstream="incident_list" login_required="true" force_https="true" />

<rn:container report_id="196">
    <rn:condition url_parameter_check="selectFilter == 'product'">
        <rn:widget path="search/BasicProductCategorySearchFilter"
            report_page_url="/app/account/questions/list"
            clear_filters_page_url="/app/account/questions/list"/>
    <rn:condition_else/>
        <rn:condition url_parameter_check="selectFilter == 'category'">
            <rn:widget path="search/BasicProductCategorySearchFilter"
                filter_type="categories"
                report_page_url="/app/account/questions/list"
                clear_filters_page_url="/app/account/questions/list"
                label_clear_filters_button="#rn:msg:CLEAR_CATEGORY_CMD#"/>
        <rn:condition_else/>
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <rn:widget path="search/BasicKeywordSearch" label_text=""/>
            <div>
                <a href="/app/account/questions/list/selectFilter/product/#rn:url_param:c#/#rn:url_param:kw##rn:session#">#rn:msg:FILTER_BY_PRODUCT_LBL#</a><br/>
                <a href="/app/account/questions/list/selectFilter/category/#rn:url_param:p#/#rn:url_param:kw##rn:session#">#rn:msg:FILTER_BY_CATEGORY_LBL#</a>
            </div>
            <rn:widget path="search/BasicDisplaySearchFilters" />
            <hr/>
            <rn:widget path="reports/BasicResultInfo"/>
            <rn:widget path="reports/BasicMultiline"/>
            <rn:widget path="reports/BasicPaginator"/>
        </rn:condition>
    </rn:condition>
</rn:container>
