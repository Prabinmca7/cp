<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="mobile.php" clickstream="answer_list"/>
<section id="rn_PageTitle" class="rn_AnswerList">
    <rn:container source_id="OKCSSearch">
    <div class="rn_Module">
        <h1>#rn:msg:SEARCH_RESULTS_CMD#</h1>
        <form onsubmit="return false;">
            <rn:widget path="searchsource/SourceSearchField" initial_focus="true" label_placeholder="#rn:msg:ENTER_A_QUESTION_ELLIPSIS_MSG#" filter_label="Keyword" filter_type="query"/>
            <rn:widget path="searchsource/SourceSearchButton" history_source_id="OKCSSearch"/>
            <rn:widget path="okcs/SupportedLanguages" />
            <div class="rn_StartOver">
                <a href="/app/home" >#rn:msg:START_OVER_LBL#</a>
            </div>
        </form>
    </div>
    </rn:container>
</section>
<section id="rn_PageContent" class="rn_AnswerList">
    <rn:container source_id="OKCSSearch" truncate_size="200">
        <div class="rn_Module">
            <rn:widget path="okcs/Facet" toggle_title="true"/>
        </div>
        <div id="rn_OkcsRightContainer" class="rn_Padding">
            <rn:widget path="okcs/SearchResult"/>
            <div class="rn_FloatRight rn_Padding">
                <rn:widget path="okcs/OkcsPagination"/>
            </div>
        </div>
        <div class="rn_Module">
            <div class="rn_Padding">
                <rn:widget path="okcs/SearchRating" toggle_title="true" />
            </div>
        </div>
    </rn:container>
</section>