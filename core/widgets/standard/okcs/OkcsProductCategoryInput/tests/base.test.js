UnitTest.addSuite({
    type:       UnitTest.Type.Widget,
    instanceID: 'OkcsProductCategoryInput_0'
}, function (Y, widget, baseSelector) {
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsProductCategoryInput",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'OkcsProductCategoryInput_0'
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.filter_type = widget.data.attrs.filter_type;
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    suite.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        "Updates the tree in response to the 'response' event": function () {
            if (this.filter_type === 'Category') {
                var categoryData = widget._convertData(RightNow.JSON.parse('[{"dateAdded":1407216286000,"dateModified":1407216310000,"objectID":"001.001","sortOrder":1,"hasChildren":true,"recordID":"082020204574de750147a1909bd9007ff6","referenceKey":"WINDOWS","name":"Windows&#039;s Special characters -- !@#$%*() &amp;lt;b&amp;gt; html &amp;lt;\/b&amp;gt;","externalType":"CATEGORY"},{"dateAdded":1407216292000,"dateModified":1407216292000,"objectID":"001.002","sortOrder":2,"hasChildren":false,"recordID":"082020204574de750147a1909bd9007ff3","referenceKey":"LINUX","name":"Linux","externalType":"CATEGORY"}]'));
                var response = RightNow.JSON.parse('[{"data": {"data_type" : "", "current_root" : "OPERATING_SYSTEMS", "hier_data" : "", "label" : "Operating Systems", "linking_on" : 0, "linkingProduct" : 0, "value" : "", "reset" : false, "level" : 1}}]');
                response[0].data.hier_data = categoryData;
                widget.getSubLevelResponse(null, response);
                Y.Assert.areSame(Y.one('#ygtvlabelel3').get('text'), "Windows's Special characters -- !@#$%*() <b> html </b>");
            }
        }
    }));

    return suite;
}).run();
