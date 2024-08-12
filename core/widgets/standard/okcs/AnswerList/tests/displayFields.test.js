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
                    this.headers = Y.all('.yui3-datatable-header');
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Check for column headers": function() {
            this.initValues();
            Y.Assert.areSame(this.headers.size(), 7);
            Y.Assert.areEqual(this.headers.item(0).get('text'), widget.data.attrs.label_title + widget.data.attrs.label_sortable + RightNow.Interface.getMessage("CLICK_TO_SORT_CMD"));
            Y.Assert.areEqual(this.headers.item(1).get('text'), widget.data.attrs.label_version);
            Y.Assert.areEqual(this.headers.item(2).get('text'), widget.data.attrs.label_owner);
            Y.Assert.areEqual(this.headers.item(3).get('text'), widget.data.attrs.label_create_date + widget.data.attrs.label_sortable + RightNow.Interface.getMessage("CLICK_TO_SORT_CMD"));
            Y.Assert.areEqual(this.headers.item(4).get('text'), widget.data.attrs.label_publish_date + widget.data.attrs.label_sortable + RightNow.Interface.getMessage("CLICK_TO_SORT_CMD"));
            Y.Assert.areEqual(this.headers.item(5).get('text'), widget.data.attrs.label_document_id + widget.data.attrs.label_sortable + RightNow.Interface.getMessage("CLICK_TO_SORT_CMD"));
            Y.Assert.areEqual(this.headers.item(6).get('text'), widget.data.attrs.label_answer_id + widget.data.attrs.label_sortable + RightNow.Interface.getMessage("CLICK_TO_SORT_CMD"));
        },

        "Test for fields": function() {
            this.initValues();
            var data = Y.one('tr');
            Y.Assert.areSame(data.get('children').size(), this.headers.size());
        }
    }));

    return suite;
}).run();
