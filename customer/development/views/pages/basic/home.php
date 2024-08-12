<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="basic.php" clickstream="home"/>

<h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
<rn:widget path="search/BasicKeywordSearch" label_text="" report_page_url="/app/answers/list"/>

<h2>#rn:msg:MOST_POPULAR_ANSWERS_LBL#</h2>
<rn:widget path="reports/TopAnswers" limit="5"/>

<h2>#rn:msg:FEATURED_SUPPORT_CATEGORIES_LBL#</h2>
<rn:widget path="search/MobileProductCategoryList" data_type="categories" levels="1" label_title=""/>