UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SearchResult_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/AnswerList",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'SearchResult_0'
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.messageDOM = Y.one('.rn_NoSearchResultMsg');
                    this.hideWhenNoResultFlag = widget.data.attrs.hide_when_no_results;
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        
        "No results found should be handled properly": function() {
            this.initValues();
            if(this.hideWhenNoResultFlag)
            {
                Y.Assert.isNull(Y.one('.rn_SearchResult'));
                Y.Assert.isTrue(Y.one('.rn_NoSearchResultMsg').hasClass('rn_Hidden'));
            }
            else
            {
                Y.Assert.isTrue(Y.one('.rn_SearchResult').hasClass('rn_NoSearchResult'));
                Y.Assert.isNotNull(this.messageDOM);
            }
        }
    }));

    return suite;
}).run();
