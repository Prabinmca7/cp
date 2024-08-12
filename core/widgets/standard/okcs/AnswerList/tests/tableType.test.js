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
                    this.type = widget.data.attrs.type;
                    this.headers = Y.all('.yui3-datatable-header span');
                    this.tableCaption = Y.one('caption');
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Valid data should be displayed properly": function() {
            this.initValues();
            var tableHeader = Y.one('thead');
            Y.Assert.isNotNull(tableHeader);
        },
        "Valid data header should be displayed": function() {
            this.initValues();
            var tableCaption = Y.one('.yui3-datatable-caption');
            if (this.type === 'recent') {
                Y.Assert.areSame(this.tableCaption.get('text'), widget.data.attrs.label_recent_list_title);
            }
            else if (this.type === 'popular') {
                Y.Assert.areSame(this.tableCaption.get('text'), widget.data.attrs.label_popular_list_title);
            }
            else {
                Y.Assert.areSame(this.tableCaption.get('text'), widget.data.attrs.label_table_title);
                for (var i = 0; i < this.headers.size(); i+=2) {
                    Y.Assert.isTrue(this.headers.item(i).hasClass('rn_SortIndicator'));
                }
            }
        }
    }));
    return suite;
}).run();
