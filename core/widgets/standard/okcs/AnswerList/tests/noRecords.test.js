UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'AnswerList_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/AnswerList",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'AnswerList_0'
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.messageDOM = Y.one('.yui3-datatable-message-content');
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        
        "No data found is handled properly": function() {
            this.initValues();
            //Y.Assert.isNotNull(this.messageDOM);
            //Y.Assert.areSame(widget.data.attrs.label_no_results, this.messageDOM.getHTML());
        }
    }));

    return suite;
}).run();
