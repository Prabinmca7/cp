<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="standard.php" clickstream="answer_list"/>

<rn:widget path="knowledgebase/RssIcon"/>

<rn:container source_id="OKCSSearch">
    <div id="rn_LoadingIndicator" class="rn_Browse">
       <rn:widget path="okcs/LoadingIndicator"/>
    </div>
    <div id="rn_PageTitle" class="rn_Search">
        <div id="rn_SearchControls">
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
            <form>
                <rn:widget path="searchsource/SourceSearchField" initial_focus="true" label_placeholder="#rn:msg:ENTER_A_QUESTION_ELLIPSIS_MSG#" filter_label="Keyword" filter_type="query"/>
                <rn:widget path="searchsource/SourceSearchButton" history_source_id="OKCSSearch"/>
                <rn:widget path="okcs/SupportedLanguages" />
                <div class="rn_StartOver">
                    <a href="/app/home" >#rn:msg:START_OVER_LBL#</a>
                </div>
            </form>
        </div>
    </div>
    <div id="rn_PageContent" class="rn_AnswerList">
        <div class="rn_ResultPadding rn_Group">
            <div id="rn_OkcsLeftContainer" class="rn_OkcsLeftContainer">
                <rn:widget path="okcs/Facet"/>
                <rn:widget path="okcs/SearchRating"/>
            </div>
            <div id="rn_OkcsRightContainer" class="rn_OkcsRightContainer">
                <rn:widget path="okcs/SearchResult"/>
                <div class="rn_FloatRight">
                    <rn:widget path="okcs/OkcsPagination"/>
                </div>
            </div>
        </div>
    </div>
</rn:container>