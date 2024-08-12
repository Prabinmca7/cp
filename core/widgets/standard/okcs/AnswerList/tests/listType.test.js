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
                    this.dataDOM = Y.one('.rn_AnswerList'),
                    this.content = Y.one("#rn_" + this.instanceID + "_Content");
                    this.list = Y.one('ul');
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
            Y.Assert.isNotNull(this.dataDOM);
            Y.Assert.isNotNull(this.list);
        },

        "Test for widget title": function() {
            this.initValues();
            if (this.type === 'browse') {
                if (widget.data.attrs.label_browse_list_title !== '') {
                    Y.Assert.areSame(Y.one('#rn_' + this.instanceID + '_Content h2').get('text'), widget.data.attrs.label_browse_list_title);
                }
                else {
                    Y.Assert.isNull(Y.one('#rn_' + this.instanceID + '_Content h2'));
                }
            }
            else {
                var title = Y.one('#rn_' + this.instanceID + '_Content h2').get('text'),
                    attributeTitle = this.type === 'recent' ? widget.data.attrs.label_recent_list_title : widget.data.attrs.label_popular_list_title;
                Y.Assert.areSame(attributeTitle, title);
            }
        }
    }));
    return suite;
}).run();
