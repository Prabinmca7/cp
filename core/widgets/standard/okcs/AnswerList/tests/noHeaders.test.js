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
                    this.viewType = widget.data.attrs.view_type;
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        
        "Test for false value of attribute 'show_headers'": function() {
            this.initValues();
            if (this.viewType === 'table') {
                this.tableHeader = Y.one('thead');
                this.tableCaption = Y.one('caption');
                Y.Assert.isTrue(this.tableHeader.hasClass('rn_ScreenReaderOnly'));
                Y.Assert.isTrue(this.tableCaption.hasClass('rn_ScreenReaderOnly'));
            }
            else {
                Y.Assert.isNull(Y.one('h2'));
            }
        }
    }));

    return suite;
}).run();
