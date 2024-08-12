<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="okcs_standard.php" clickstream="answer_list"/>
<rn:container source_id="OKCSSearch" document_id_reg_ex="^[\p{L}\p{Nd}_.]*\d{1,10}$" doc_id_navigation="true">
    <div id="rn_LoadingIndicator" class="rn_Browse rn_Container">
       <rn:widget path="okcs/LoadingIndicator"/>
    </div>
    <div id="rn_PageTitle" class="rn_Search">
        <div class="rn_Hero">
            <div class="rn_HeroInner">
                <div class="rn_SearchControls">
                    <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>
                    <form>
                        <div class="rn_SearchInput">
                            <rn:widget path="searchsource/SourceSearchField" initial_focus="true" label_placeholder="#rn:msg:ASK_A_QUESTION_ELLIPSIS_MSG#" filter_label="Keyword" filter_type="query"/>
                        </div>
                        <rn:widget path="okcs/RecentSearches"/>
                        <rn:widget path="okcs/OkcsSuggestions"/>
                        <rn:widget path="searchsource/SourceSearchButton" initial_focus="true" history_source_id="OKCSSearch"/>
                        <rn:widget path="okcs/OkcsInteractiveSpellChecker"/>
                        <div class="rn_StartOver">
                            <a href="/app/home" >#rn:msg:START_OVER_LBL#</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div id="rn_PageContent" class="rn_Container rn_OkcsResultsContainer">
        <div class="rn_ResultList rn_OkcsTable">
            <div class="rn_OkcsTableRow">
                <div id="rn_OkcsLeftContainer" class="rn_OkcsLeftContainer rn_OkcsTableCell">
                    <rn:widget path="okcs/Facet"/>
                </div>
                <div id="rn_OkcsRightContainer" class="rn_OkcsRightContainer rn_OkcsTableCell">
                    <rn:widget path="okcs/SearchResult" hide_when_no_results="false"/>
                    <div class="rn_FloatRight">
                        <rn:widget path="okcs/OkcsPagination"/>
                    </div>
                </div>
            </div>
            <div class="rn_OkcsTableRow">
                <div class="rn_OkcsLeftContainer rn_OkcsTableCell"></div>
                <div class="rn_OkcsRightContainer rn_OkcsTableCell">
                    <rn:widget path="okcs/OkcsRecentlyViewedContent"/>
                </div>
            </div>
        </div>
        <aside class="rn_SideRail" role="complementary">
            <rn:widget path="utils/ContactUs" channels="question,chat,feedback"/>
        </aside>
    </div>
</rn:container>