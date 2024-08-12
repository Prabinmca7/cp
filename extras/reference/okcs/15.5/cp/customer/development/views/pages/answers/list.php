<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="standard.php" clickstream="answer_list"/>

<h1><i class='icon-lightbulb'></i> Knowledge Base Results</h1>

<rn:container source_id="KFSearch" per_page="10" history_source_id="KFSearch">

<div id="rn_PageTitle" class="">
    <div id="rn_SearchControls">
        <form class="rn_Group">
            <rn:widget path="searchsource/SourceSearchField" initial_focus="true" filter_label="Keyword" filter_type="query"/>
            <rn:widget path="searchsource/SourceSearchButton" report_page_url="/app/answers/list"/>
        </form>
    </div>

    <br>

    <form class="rn_Group">
        <div class="rn_FloatRight rn_Padding">
            <!-- Commented as this widget is not available in okcs branch -->
            <!--<rn:widget path="searchsource/SourceSortFilter" filter_type="direction" source_id="KFSearch" labels="Ascending,Descending" label_input="#rn:msg:SRT_ORDER_LBL#" search_on_select="true"/>-->
        </div>
        <div class="rn_FloatRight rn_Padding">
            <!-- Commented as this widget is not available in okcs branch -->
            <!--<rn:widget path="searchsource/SourceSortFilter" filter_type="sort" source_id="KFSearch" label_default="Default" labels="Created,Updated" label_input="#rn:msg:SORT_BY_UC_LBL#" search_on_select="true"/>-->
        </div>
    </form>
</div>

<div id="rn_PageContent" class="rn_AnswerList">
    <div class="rn_Padding rn_Group">
        <div class="rn_FloatRight">
            <rn:widget path="searchsource/SourceResultDetails"/>
        </div>
        <rn:widget path="searchsource/SourceResultListing" hide_when_no_results="true" more_link_url=""/>
        <rn:widget path="searchsource/SourcePagination" history_source_id="KFSearch"/>

        <br><br>
        <h3>Can't find what you're looking for? Check the <a href="/app/social/questions/list/kw/#rn:url_param_value:kw#">thriving community results</a></h3>
    </div>
</div>

</rn:container>
