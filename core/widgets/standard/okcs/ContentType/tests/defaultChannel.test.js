UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'ContentType_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/ContentType",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'ContentType_0'
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.contentTypes = Y.all(baseSelector + ' a');
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Verify widget displays first content type selected by default": function() {
            this.initValues();
            Y.Assert.isTrue(this.contentTypes.item(0).hasClass('rn_Selected'));
            for (var i=1; i < this.contentTypes.size(); i++) {
                Y.Assert.isFalse(this.contentTypes.item(i).hasClass('rn_Selected'));
            }
        },

        "Verify selected content type": function() {
            this.initValues();
            var eo = new RightNow.Event.EventObject(null, {}),
                previousSelectedContentType = this.contentTypes.item(0);

            widget._selectedContentTypeID = this.contentTypes.item(1).getData('id');
            this.instance.searchSource().fire("response", eo);
            Y.Assert.isFalse(previousSelectedContentType.hasClass('rn_Selected'));
            Y.Assert.isTrue(this.contentTypes.item(1).hasClass('rn_Selected'));
        }
    }));

    return suite;
}).run();
