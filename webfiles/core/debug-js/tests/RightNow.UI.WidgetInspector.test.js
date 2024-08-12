
UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.Text.js', '/euf/core/thirdParty/codemirror/lib/codemirror-min.js', '/euf/core/debug-js/RightNow.UI.WidgetInspector.js'],
    cssFiles: ['/euf/core/thirdParty/codemirror/lib/codemirror.css'],
    namespaces: ['RightNow.UI.WidgetInspector']
}, function(Y) {
    var codeTextArea, widget, dialog, templateWidget, templateCodeTextArea;
    var WI = RightNow.UI.WidgetInspector;
    var suite = new Y.Test.Suite({
        name: "RightNow.UI.WidgetInspector",
        setUp: function() {
            RightNow.UI.WidgetInspector.provideYUI(Y);
            if (!codeTextArea) {
                codeTextArea = Y.Node.create("<div style='display:none'><textarea id='code_bodyContent'></textarea><textarea id='bodyContent'></textarea></div>");
                codeTextArea.one("#code_bodyContent").set("value", pagePhp);
                templateCodeTextArea = Y.Node.create("<div style='display:none'><textarea id='code_templateContent'></textarea><textarea id='templateContent'></textarea></div>");
                templateCodeTextArea.one("#code_templateContent").set("value", pagePhp);
                Y.one(document.body).append(templateCodeTextArea);
                Y.one(document.body).append(codeTextArea);
                widget = Y.Node.create(sampleWidget);
                Y.one(document.body).append(widget);
                templateWidget = Y.Node.create(sampleTemplateWidget);
                Y.one(document.body).append(templateWidget);
                dialog = Y.Node.create(attrDialog);
                Y.one(document.body).append(dialog);
                RightNow.UI.WidgetInspector.init();
            }
        }
    });
    suite.add(new Y.Test.Case({
        name: "tests",
        //Test Methods
        testGetAttributeValue: function() {
            var getAttributeValue = RightNow.UI.WidgetInspector.getAttributeValue;
            var intValue = Y.Node.create('<input type="text" value="1"/>');
            Y.Assert.areSame(getAttributeValue({type: "int"}, intValue).value, 1);
            intValue.set('value', '');
            Y.Assert.areSame(getAttributeValue({type: "INT"}, intValue).value, null);
            intValue.set('value', 'NaN');
            Y.Assert.areSame(getAttributeValue({type: "int"}, intValue).value, null);
            var boolValue = Y.Node.create('<input type="checkbox" />');
            Y.Assert.areSame(getAttributeValue({type: "bool"}, boolValue).value, false);
            boolValue.set('checked', true);
            Y.Assert.areSame(getAttributeValue({type: "BOOL"}, boolValue).value, true);
            var optionValue = Y.Node.create('<select><option value="a">a</option><option value="b">b</option></select>');
            Y.Assert.areSame(getAttributeValue({type: "option"}, optionValue).value, 'a');
            optionValue.set('value', 'b');
            Y.Assert.areSame(getAttributeValue({type: "OPTION"}, optionValue).value, 'b');
            optionValue.set('multiple', true);
            optionValue.get("options").each(function() {
                this.set('selected', true);
            });
            Y.Assert.areSame(getAttributeValue({type: "MULTIOPTION"}, optionValue).value, 'a,b');
        },

        testReplaceWidgetLineWithAtrributes: function() {
            var widgetLine = '<rn:widget sub:subwidgetid:attr="test" testDefault="sample" path="path" text="1" bool="true" option="opt1" multiopt="opt1,opt2"/>';
            var newAttributes = {
                modifiedAttrs: {
                    newAttribute: "newValue",
                    text: "2",
                    opt1: "newopt1",
                    multiopt1: "opt2,opt1"
                },
                ignoredAttrs: {
                    "testDefault": {
                        val: "default",
                        isDefault: true
                    }
                }
            };
            var modifiedWidgetLine = RightNow.UI.WidgetInspector.replaceWidgetLineWithAtrributes(widgetLine, newAttributes);
            var expectedWidgetLine = '<rn:widget sub:subwidgetid:attr="test" path="path" text="2" bool="true" option="opt1" multiopt="opt1,opt2" newAttribute="newValue" opt1="newopt1" multiopt1="opt2,opt1"/>';
            expectedWidgetLine = WI.deserializeParsedWidgetXML(WI.parseWidgetLine(expectedWidgetLine));
            Y.Assert.areSame(modifiedWidgetLine, expectedWidgetLine);
            widgetLine = '<rn:widget sub:subwidgetid:attr="test" path="path" text="1" bool="true" option="opt1" multiopt="opt1,opt2">';
            modifiedWidgetLine = RightNow.UI.WidgetInspector.replaceWidgetLineWithAtrributes(widgetLine, newAttributes);
            widgetLine =  WI.deserializeParsedWidgetXML(WI.parseWidgetLine(widgetLine));
            Y.Assert.areSame(modifiedWidgetLine, expectedWidgetLine.replace(/\s?\/>$/, ">"));
        },

        testGetModifiedAttributes: function() {
            var attrs = RightNow.UI.WidgetInspector.getModifiedAttributes("rn_RecentlyAnsweredQuestions_29");
            Y.Assert.isTrue(Object.keys(attrs.modifiedAttrs).length == 0);
            Y.Assert.isTrue(Object.keys(attrs.ignoredAttrs).length > 0);
            dialog.one("[name='avatar_size'] option[value=large]").setAttribute("selected", true);
            dialog.one("[name='avatar_size'] option[value=small]").removeAttribute("selected");
            attrs = RightNow.UI.WidgetInspector.getModifiedAttributes("rn_RecentlyAnsweredQuestions_29");
            Y.Assert.areSame(attrs.modifiedAttrs["avatar_size"], "large");
        },

        testFindAndModifyTheWidgetLine: function() {
            dialog.one("[name='avatar_size'] option[value=small]").removeAttribute("selected");
            dialog.one("[name='avatar_size'] option[value=large]").setAttribute("selected", true);
            dialog.one("[name='questions_with_answers']").set("checked", false);
            RightNow.UI.WidgetInspector.findAndModifyTheWidgetLine("rn_RecentlyAnsweredQuestions_29");
            editor = RightNow.UI.WidgetInspector.getEditor("body");
            var positions = Y.one("#rn_RecentlyAnsweredQuestions_29").getAttribute("data-widget-position").split("@"),
                start = parseInt(positions[0], 10),
                widgetLineLength = parseInt(positions[2], 10),
                phpFile = editor.getValue(),
                widgetLine = phpFile.substring(start, start + widgetLineLength);
            Y.Assert.areSame(widgetLine, WI.deserializeParsedWidgetXML(WI.parseWidgetLine('<rn:widget path="discussion/RecentlyAnsweredQuestions" show_excerpt="true" maximum_questions="5" avatar_size="large" answer_type="author" questions_with_answers="false"/>')));
        },

        testFindAndModifyTheTemplateWidgetLine: function() {
            dialog.setAttribute("id", "widgetAttrDialog_rn_RecentlyAnsweredQuestions_28");
            dialog.one("[name='avatar_size'] option[value=small]").removeAttribute("selected");
            dialog.one("[name='avatar_size'] option[value=large]").removeAttribute("selected");
            dialog.one("[name='avatar_size'] option[value=medium]").setAttribute("selected", true);
            dialog.one("[name='questions_with_answers']").set("checked", true);
            RightNow.UI.WidgetInspector.findAndModifyTheWidgetLine("rn_RecentlyAnsweredQuestions_28", "standard/discussion/RecentlyAnsweredQuestions");
            editor = RightNow.UI.WidgetInspector.getEditor("template");
            var positions = Y.one("#rn_RecentlyAnsweredQuestions_28").getAttribute("data-widget-position").split("@"),
                start = parseInt(positions[0], 10),
                widgetLineLength = parseInt(positions[2], 10),
                phpFile = editor.getValue(),
                widgetLine = phpFile.substring(start, start + widgetLineLength);
            Y.Assert.areSame(widgetLine, WI.deserializeParsedWidgetXML(WI.parseWidgetLine('<rn:widget path="discussion/RecentlyAnsweredQuestions" show_excerpt="true" maximum_questions="5" answer_type="author"/>')));
        },

        testEscapeAttrbuteValues: function(){
            var widgetLine = '<rn:widget path="discussion/RecentlyAnsweredQuestions" label_answer_more_link=">>>>>>\"!@#@#@3?>\'\'" />';
            var escapedWidgetLine = '<rn:widget path="discussion&#x2F;RecentlyAnsweredQuestions" label_answer_more_link="&gt;&gt;&gt;&gt;&gt;&gt;&quot;!@#@#@3?&gt;&#x27;&#x27;" />';
            Y.Assert.areSame(escapedWidgetLine, WI.escapeAttributeValues(widgetLine, "rn_RecentlyAnsweredQuestions_29"));
        }
    }));
    return suite;
});
var primaryConnectObjects = "";
var pagePhp = '<rn:meta title="#rn:msg:SHP_TITLE_HDG#" template="standard.php" clickstream="home"/>\n\
\n\
<div class="rn_Hero">\n\
    <div class="rn_HeroInner">\n\
        <div class="rn_HeroCopy">\n\
            <h1>#rn:msg:WERE_HERE_TO_HELP_LBL#</h1>\n\
        </div>\n\
        <div class="rn_SearchControls">\n\
            <h1 class="rn_ScreenReaderOnly">#rn:msg:SEARCH_CMD#</h1>\n\
            <form method="get" action="/app/results">\n\
                <rn:container source_id="KFSearch">\n\
                    <div class="rn_SearchInput">\n\
                        <rn:widget path="searchsource/SourceSearchField" initial_focus="true" label_placeholder="Enter the Search term..."/>\n\
                    </div>\n\
                    <rn:widget path="searchsource/SourceSearchButton" search_results_url="/app/results"/>\n\
                </rn:container>\n\
            </form>\n\
        </div>\n\
    </div>\n\
</div>\n\
\n\
<div class="rn_PageContent rn_Home">\n\
    <div class="rn_Container">\n\
        <rn:widget path="navigation/VisualProductCategorySelector" numbered_pagination="true"/>\n\
    </div>\n\
\n\
    <div class="rn_PopularKB">\n\
        <div class="rn_Container">\n\
            <h2>#rn:msg:POPULAR_PUBLISHED_ANSWERS_LBL#</h2>\n\
            <rn:widget path="reports/TopAnswers" show_excerpt="true" limit="5"/>\n\
            <span class="rn_AnswersLink">\n\
                <a href="/app/answers/list#rn:session#">#rn:msg:SHOW_MORE_PUBLISHED_ANSWERS_LBL#</a>\n\
            </span>\n\
        </div>\n\
    </div>\n\
\n\
    <div class="rn_PopularSocial">\n\
        <div class="rn_Container">\n\
            <h2>#rn:msg:RECENT_COMMUNITY_DISCUSSIONS_LBL#</h2>\n\
            <rn:widget path="discussion/RecentlyAnsweredQuestions" show_excerpt="true" maximum_questions="5" avatar_size="small" answer_type="author"/>\n\
            <span class="rn_DiscussionsLink">\n\
                <a href="/app/social/questions/list/kw/*#rn:session#">#rn:msg:SHOW_MORE_COMMUNITY_DISCUSSIONS_LBL#</a>\n\
            </span>\n\
        </div>\n\
    </div>\n\
</div>\n';
var sampleWidget = '<div data-attrs="{&quot;avatar_size&quot;:&quot;small&quot;,&quot;questions_with_answers&quot;:true,&quot;product_filter&quot;:null,&quot;category_filter&quot;:null,&quot;include_children&quot;:true,&quot;question_detail_url&quot;:&quot;\/app\/social\/questions\/detail&quot;,&quot;maximum_questions&quot;:5,&quot;display_answers&quot;:true,&quot;show_excerpt&quot;:true,&quot;excerpt_max_length&quot;:256,&quot;answer_type&quot;:[&quot;author&quot;],&quot;answer_text_length&quot;:150,&quot;label_answer_more_link&quot;:&quot;More&quot;,&quot;label_moderator_answer&quot;:&quot;Moderator Best Answer&quot;,&quot;label_user_answer&quot;:&quot;Best Answer&quot;,&quot;label_no_questions&quot;:&quot;No Discussions Available&quot;}" data-widget-position = "1582@body@139" data-widget-path="standard/discussion/RecentlyAnsweredQuestions" data-widget-identifier="RecentlyAnsweredQuestions" id="rn_RecentlyAnsweredQuestions_29" class="rn_RecentlyAnsweredQuestions"></div>';
var sampleTemplateWidget = '<div data-attrs="{&quot;avatar_size&quot;:&quot;small&quot;,&quot;questions_with_answers&quot;:true,&quot;product_filter&quot;:null,&quot;category_filter&quot;:null,&quot;include_children&quot;:true,&quot;question_detail_url&quot;:&quot;\/app\/social\/questions\/detail&quot;,&quot;maximum_questions&quot;:5,&quot;display_answers&quot;:true,&quot;show_excerpt&quot;:true,&quot;excerpt_max_length&quot;:256,&quot;answer_type&quot;:[&quot;author&quot;],&quot;answer_text_length&quot;:150,&quot;label_answer_more_link&quot;:&quot;More&quot;,&quot;label_moderator_answer&quot;:&quot;Moderator Best Answer&quot;,&quot;label_user_answer&quot;:&quot;Best Answer&quot;,&quot;label_no_questions&quot;:&quot;No Discussions Available&quot;}" data-widget-position = "1582@template@139" data-widget-path="standard/discussion/RecentlyAnsweredQuestions" data-widget-identifier="RecentlyAnsweredQuestions" id="rn_RecentlyAnsweredQuestions_28" class="rn_RecentlyAnsweredQuestions_30"></div>';
var attrDialog =
'<table style="display:none" id="widgetAttrDialog_rn_RecentlyAnsweredQuestions_29" class="rn_WidgetAttrsList" data-dialog-widget-path="standard/discussion/RecentlyAnsweredQuestions">\n\
    <thead id="yui_3_17_2_3_1474611440809_295">\n\
        <tr id="yui_3_17_2_3_1474611440809_294">\n\
            <th class="rn_PanelTitle" id="yui_3_17_2_3_1474611440809_293">Attribute Name</th>\n\
            <th class="rn_PanelTitle">Current Value</th>\n\
        </tr>\n\
    </thead>\n\
\n\
    <tbody>\n\
\n\
            <tr>\n\
                <td width="700px"><b>avatar_size</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
                        <select data-attr="true" name="avatar_size" value="small">\n\
\n\
                                <option value="none">none</option>\n\
\n\
                                <option value="small" selected="">small</option>\n\
\n\
                                <option value="medium">medium</option>\n\
\n\
                                <option value="large">large</option>\n\
\n\
                                <option value="xlarge">xlarge</option>\n\
\n\
                        </select>\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>questions_with_answers</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="checkbox" name="questions_with_answers" value="true" checked="">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>product_filter</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="product_filter" value="">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>category_filter</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="category_filter" value="">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>include_children</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="checkbox" name="include_children" value="true" checked="">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>question_detail_url</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="question_detail_url" value="/app/social/questions/detail">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>maximum_questions</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="maximum_questions" value="5">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>display_answers</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="checkbox" name="display_answers" value="true" checked="">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>show_excerpt</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="checkbox" name="show_excerpt" value="true" checked="">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>excerpt_max_length</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="excerpt_max_length" value="256">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>answer_type</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <select data-attr="true" name="answer_type" multiple="" value="author">\n\
\n\
\n\
                                <option value="author" selected="">author</option>\n\
\n\
                                <option value="moderator">moderator</option>\n\
\n\
                        </select>\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>answer_text_length</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="answer_text_length" value="150">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>label_answer_more_link</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="label_answer_more_link" value="More">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>label_moderator_answer</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="label_moderator_answer" value="Moderator Best Answer">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>label_user_answer</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="label_user_answer" value="Best Answer">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
            <tr>\n\
                <td width="700px"><b>label_no_questions</b></td>\n\
                <td class="rn_WidgetAttrsValue">\n\
\n\
                        <input data-attr="true" type="text" name="label_no_questions" value="No Discussions Available">\n\
\n\
                </td>\n\
            </tr>\n\
\n\
    </tbody>\n\
</table>\n';
var widgetsInfo = {
    "standard\/discussion\/RecentlyAnsweredQuestions": {
        "url": "http:\/\/abbaswi.marias.us.oracle.com\/ci\/admin\/versions\/manage\/#widget=standard\/discussion\/RecentlyAnsweredQuestions",
        "isLatestVersion": true,
        "currentVersion": "1.3.1",
        "attributes": {
            "avatar_size": {
                "name": "rn:msg:AVATAR_SIZE_LBL",
                "description": "rn:msg:SIZE_TO_DISPLAY_USER_AVATARS_LBL",
                "type": "OPTION",
                "options": ["none", "small", "medium", "large", "xlarge"],
                "default": "medium"
            },
            "questions_with_answers": {
                "name": "rn:msg:QUESTIONS_WITH_ANSWERS_LBL",
                "description": "rn:msg:RET_QS_SSS_USRS_QS_SSS_SRS_QS_SSS_NSWRS_MSG",
                "default": true,
                "type": "BOOLEAN"
            },
            "product_filter": {
                "name": "rn:msg:PRODUCT_FILTER_LBL",
                "description": "rn:msg:QS_SPEC_FLTRS_RET_L_ANS_QS_MSG",
                "default": null,
                "min": 1,
                "type": "INT"
            },
            "category_filter": {
                "name": "rn:msg:CATEGORY_FILTER_LBL",
                "description": "rn:msg:QS_SPEC_FLTRS_RET_L_ANS_QUESTIONS_MSG",
                "default": null,
                "min": 1,
                "type": "INT"
            },
            "include_children": {
                "name": "rn:msg:INCLUDE_CHILDREN_LBL",
                "description": "rn:msg:T_ATTRIB_DET_T_L_QS_ID_PROD_CATEGORIES_MSG",
                "default": true,
                "type": "BOOLEAN"
            },
            "question_detail_url": {
                "name": "rn:msg:QUESTION_DETAIL_URL_LBL",
                "description": "rn:msg:LOCATION_REL_L_T_IDS_APPEND_GEN_L_URL_MSG",
                "default": "rn:php:'\/app\/' . \\RightNow\\Utils\\Config::getConfig(CP_SOCIAL_QUESTIONS_DETAIL_URL)",
                "type": "STRING"
            },
            "maximum_questions": {
                "name": "rn:msg:NUMBER_OF_QUESTIONS_LBL",
                "description": "rn:msg:DETERMINES_MAXIMUM_QUESTIONS_DISPLAY_MSG",
                "default": 4,
                "min": 1,
                "type": "INT"
            },
            "display_answers": {
                "name": "rn:msg:DISPLAY_ANSWERS_LBL",
                "description": "rn:msg:SEL_QS_SSS_QUESTIONSWITH_ATTRB_SHWN_MSG",
                "default": true,
                "type": "BOOLEAN"
            },
            "show_excerpt": {
                "name": "rn:msg:SHOW_EXCERPT_CMD",
                "type": "BOOL",
                "description": "rn:msg:DETERMINES_EXCERPT_RESULT_LBL"
            },
            "excerpt_max_length": {
                "name": "rn:msg:EXCERPT_MAXIMUM_LENGTH_LBL",
                "type": "INT",
                "description": "rn:msg:LNG_QS_RPT_SHOWEXCERPT_TRNCTD_RPT_LLPSS_MSG",
                "max": 256,
                "default": 256
            },
            "answer_type": {
                "name": "rn:msg:ANSWER_TYPES_LBL",
                "description": "rn:msg:SSS_QS_DSP_TTRB_THRSLCTD_SSS_TH_MDRTRSLC_MSG",
                "type": "multioption",
                "default": ["author", "moderator"],
                "options": ["author", "moderator"]
            },
            "answer_text_length": {
                "name": "rn:msg:ANSWER_TEXT_LENGTH_LBL",
                "description": "rn:msg:SSS_QS_SSS_BREAK_CLOSEST_BNDRY_LNG_VL_MSG",
                "default": 150,
                "min": 10,
                "type": "INT"
            },
            "label_answer_more_link": {
                "name": "rn:msg:ANSWER_MORE_LINK_LABEL_LBL",
                "description": "rn:msg:LABEL_MORE_LINK_ON_EACH_BEST_ANSWERS_MSG",
                "default": "rn:msg:MORE_LBL",
                "type": "STRING"
            },
            "label_moderator_answer": {
                "name": "rn:msg:MODERATOR_ANSWER_LABEL_LBL",
                "description": "rn:msg:LABEL_BEST_ANSWERS_SELECTED_MODERATOR_MSG",
                "default": "rn:msg:MODERATOR_BEST_ANSWER_LBL",
                "type": "STRING"
            },
            "label_user_answer": {
                "name": "rn:msg:USER_ANSWER_LABEL_LBL",
                "description": "rn:msg:LABEL_BEST_ANSWERS_SELECTED_BY_USER_MSG",
                "default": "rn:msg:BEST_ANSWER_LBL",
                "type": "STRING"
            },
            "label_no_questions": {
                "name": "rn:msg:NO_QUESTIONS_LABEL_LBL",
                "description": "rn:msg:LABEL_DISPLAY_THERE_NO_QUESTIONS_SHOW_MSG",
                "default": "rn:msg:NO_DISCUSSIONS_AVAILABLE_LBL",
                "type": "STRING"
            }
        }
    }
};
var pagePath = "",
    templatePath = "";
UnitTest.run();
