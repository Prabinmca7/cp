<rn:meta title="#rn:msg:FIND_ANS_HDG#" template="standard.php" clickstream="answer_list"/>
<rn:condition config_check="OKCS_ENABLED == true">
    <div class="rn_Container">
        <div id="rn_PageTitle" class="rn_ScreenReaderOnly">
            <h1>#rn:msg:BROWSE1_AB_HDG#</h1>
        </div>
        <rn:container source_id="OKCSBrowse">
            <div id="rn_PageContentChannel" class="rn_Browse rn_PageContentChannel">
                <div class="rn_PageContentChannelInner">
                    <rn:widget path="okcs/ContentType"/>
                </div>
            </div>

            <div id="rn_LoadingIndicator" class="rn_Browse">
               <rn:widget path="okcs/LoadingIndicator"/>
            </div>

            <div id="rn_PageContentArticles" class="rn_AnswerList">
                <div class="rn_ResultPadding">
                    <div>
                        <div id="rn_OkcsLeftContainer" class="rn_OkcsLeftContainer">
                            <rn:widget path="okcs/OkcsProductCategorySearchFilter" filter_type="products" view_type="explorer"/>
                            <rn:widget path="okcs/OkcsProductCategorySearchFilter" filter_type="categories" view_type="explorer"/>
                        </div>
                        <div id="rn_OkcsRightContainer" class="rn_OkcsRightContainer">
                            <div id="rn_Browse_Loading"></div>
                            <div id="rn_OkcsAnswerList">
                                <rn:widget path="okcs/AnswerList" target="_self"/>
                                <div class="rn_FloatRight">
                                    <rn:widget path="okcs/OkcsPagination"/>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </rn:container>
    </div>
</rn:condition>