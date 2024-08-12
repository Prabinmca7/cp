<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="basic.php" clickstream="answer_list"/>

<rn:container report_id="176">
    <rn:condition url_parameter_check="selectFilter == 'product'">
        <rn:widget path="search/BasicProductCategorySearchFilter"/>
    <rn:condition_else/>
        <rn:condition url_parameter_check="selectFilter == 'category'">
            <rn:widget path="search/BasicProductCategorySearchFilter"
                filter_type="categories"
                report_page_url="/app/answers/list"
                label_clear_filters_button="#rn:msg:CLEAR_CATEGORY_CMD#"/>
        <rn:condition_else/>
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <rn:widget path="search/BasicKeywordSearch" label_text=""/>
            <div>
                <a href="/app/answers/list/selectFilter/product/#rn:url_param:c#/#rn:url_param:kw##rn:session#">#rn:msg:FILTER_BY_PRODUCT_LBL#</a><br/>
                <a href="/app/answers/list/selectFilter/category/#rn:url_param:p#/#rn:url_param:kw##rn:session#">#rn:msg:FILTER_BY_CATEGORY_LBL#</a>
            </div>
            <rn:widget path="search/BasicDisplaySearchFilters" />
            <hr/>
            <rn:widget path="reports/BasicResultInfo"/>
            <rn:widget path="reports/BasicMultiline"/>
            <rn:widget path="reports/BasicPaginator"/>
        </rn:condition>
    </rn:condition>
</rn:container>
