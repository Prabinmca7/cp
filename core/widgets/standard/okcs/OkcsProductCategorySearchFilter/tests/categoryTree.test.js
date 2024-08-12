UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsProductCategorySearchFilter_0'
}, function(Y, widget, baseSelector) {
    var tests = new Y.Test.Suite({
        name: 'standard/okcs/OkcsProductCategorySearchFilter',

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'OkcsProductCategorySearchFilter_0'
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.titleDiv = Y.one('.rn_CategoryExplorerTitleDiv') || Y.one('.rn_Label') ;
                    this.parentNode = Y.one('.rn_CategoryExplorerLink') || Y.one("#rn_" + this.instanceID + "_TreeContainer").get('parentNode');
                    this.filter_type = widget.data.attrs.filter_type;
                    this.view_type = widget.data.js.viewType;
                    if (this.view_type === 'explorer') {
                        this.childNodes = Y.one('.rn_CategoryExplorerLeaf');
                        this.expandIcon = Y.one('.rn_CategoryExplorerCollapsed');
                        this.unselectIcon = Y.one('.rn_CategoryExplorerExpanded');
                    }
                }
            };

            for (var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    tests.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        setUp: function () {
            // Don't navigate away from this page.
            widget.data.attrs.search_on_select = false;
        },

        "Updates the tree in response to the 'response' event": function () {
            this.initValues();
            if (this.view_type !== 'explorer') {
                var categoryData = widget._convertData(RightNow.JSON.parse('{"items" : [{"dateAdded":1407216286000,"dateModified":1407216310000,"objectID":"001.001","sortOrder":1,"hasChildren":true,"recordID":"082020204574de750147a1909bd9007ff6","referenceKey":"WINDOWS","name":"Windows","externalType":"CATEGORY"},{"dateAdded":1407216292000,"dateModified":1407216292000,"objectID":"001.002","sortOrder":2,"hasChildren":false,"recordID":"082020204574de750147a1909bd9007ff3","referenceKey":"LINUX","name":"Linux","externalType":"CATEGORY"}]}'));
                var response = RightNow.JSON.parse('[{"data": {"data_type" : "", "current_root" : "OPERATING_SYSTEMS", "hier_data" : "", "label" : "Operating Systems", "linking_on" : 0, "linkingProduct" : 0, "value" : "", "reset" : false, "level" : 1}}]');
                response[0].data.hier_data = categoryData;
                widget.getSubLevelResponse(null, response);
                Y.Assert.areSame(Y.one('#ygtvlabelel3').get('text'), 'Cat2');
            }
        }
    }));
    return tests;
});
UnitTest.run();
