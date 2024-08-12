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
                    this.widgetData = this.instance.data;
                    this.defaultContentType = widget.data.attrs.default_content_type;
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

        "Verify widget output": function() {
            this.initValues();
            Y.Assert.isNotNull(Y.one('ul'));
            Y.Assert.isNotNull(this.contentTypes);
            for (var i=0; i < this.contentTypes.size(); i++) {
                Y.Assert.isTrue(this.contentTypes.item(i).hasChildNodes());
            }
        },

        "Verify widget displays content type data": function() {
            this.initValues();
            Y.Assert.isNull(Y.one('.rn_NoChannelsMsg'));
            for (var i=0; i < this.contentTypes.size(); i++) {
                Y.Assert.isNotNull(this.contentTypes.item(i).get('text'));
            }
        },

        "Accessibility check screenreader detail": function() {
            this.initValues();
            var selectedContentType = Y.one('.rn_Selected'),
                selectedContentTypeChildren = selectedContentType.get('children'),
                contentTypeSelectedScreenReaderSpan = selectedContentTypeChildren.item(0);
            Y.Assert.isTrue(selectedContentType.hasChildNodes());
            Y.Assert.areSame(contentTypeSelectedScreenReaderSpan.get('nodeName'), 'SPAN');
            Y.Assert.isTrue(contentTypeSelectedScreenReaderSpan.hasClass("rn_ScreenReaderOnly"));
            Y.Assert.areSame(document.activeElement.className, 'rn_Selected');
        },

        "Verify default selected channel": function() {
            this.initValues();
            var selectedContentType = Y.one('.rn_Selected');
            if (this.defaultContentType !== ''){
                for (var i=0; i < this.contentTypes.size(); i++) {
                    if (this.contentTypes.item(i).getData('id').toUpperCase() === this.defaultContentType.toUpperCase()) {
                        Y.Assert.isTrue(this.contentTypes.item(i).hasClass('rn_Selected'));
                    }
                    else {
                        Y.Assert.isFalse(this.contentTypes.item(i).hasClass('rn_Selected'));
                    }
                }
            }
        }
    }));
    return suite;
}).run();
