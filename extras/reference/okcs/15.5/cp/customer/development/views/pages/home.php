<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="standard.php" clickstream="home"/>
<div id="rn_PageTitle" class="rn_Home">
    <div id="rn_SearchControls">
        <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
        <form method="get" action="/app/search">
            <rn:container source_id="OKCSSearch">
                <rn:widget path="searchsource/SourceSearchField" initial_focus="true" label_placeholder="#rn:msg:ENTER_A_QUESTION_ELLIPSIS_MSG#"/>
                <rn:widget path="searchsource/SourceSearchButton" initial_focus="true" search_results_url="/app/search"/>
                <rn:widget path="okcs/SupportedLanguages" />
            </rn:container>
        </form>
    </div>
</div>
<div id="rn_PageContent" class="rn_Home">
    <div class="rn_Module rn_AnswerListContainer">
        <rn:widget path="okcs/AnswerList" type="popular" target="_self"/>
    </div>
    <div class="rn_Module rn_AnswerListContainer">
        <rn:widget path="okcs/AnswerList" type="recent" target="_self"/>
    </div>
</div>