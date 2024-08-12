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
                    this.view_type = widget.data.attrs.view_type;
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

        "Widget should display products if filter-type is not specified": function() {
            this.initValues();
            Y.Assert.isNotNull(this.titleDiv.getHTML());
            Y.Assert.areSame(widget.data.attrs.label_input, this.titleDiv.getHTML());
            if (this.view_type === 'explorer') {
                var type = this.filter_type === 'category' ? 'Category' : 'Product';
                Y.Assert.areSame(type, this.parentNode.getAttribute('data-type'));
            }
        },

        "Widget should display parent nodes on page load": function() {
            this.initValues();
            Y.Assert.isNull(Y.one('.rn_CategoryExplorerListHidden')); // Children are not rendered
            Y.Assert.isNotNull(this.parentNode); //Only parent Node is rendered
        },

        "Widget should render children on click of parent node": function() {
            this.initValues();
            if (this.view_type === 'explorer') {
                var collapsedNode = Y.one('#rn_OkcsProductCategorySearchFilter_0_RN_PRODUCT_7_Collapsed'),
                    expandedNode = Y.one('#rn_OkcsProductCategorySearchFilter_0_RN_PRODUCT_7_Expanded');

                collapsedNode.simulate('click');
                Y.Assert.isNotNull(this.childNodes); // Children are rendered
                Y.Assert.areSame(collapsedNode.getAttribute('class'), 'rn_CategoryExplorerCollapsedHidden');
                Y.Assert.areSame(expandedNode.getAttribute('class'), 'rn_CategoryExplorerExpanded');
            }
        },

        "Widget should display selected icon when slected response is fired": function() {
            this.initValues();
            var eo = new RightNow.Event.EventObject(null, {data: {"categoryRecordID": "RN_PRODUCT_7"}});
            widget.productSelected = true;
            widget._selectedProductRecordID = "RN_PRODUCT_7";
            this.instance.searchSource().fire("response", eo);
            Y.Assert.isTrue(Y.one('#rn_OkcsProductCategorySearchFilter_0_RN_PRODUCT_7').hasClass('rn_CategoryExplorerLinkSelected'));
        },

        "Widget should display expand icon if children are present": function() {
            this.initValues();
            var collapsedNode = Y.one('#rn_OkcsProductCategorySearchFilter_0_RN_PRODUCT_7_Collapsed'),
                expandedNode = Y.one('#rn_OkcsProductCategorySearchFilter_0_RN_PRODUCT_7_Expanded');

            Y.Assert.isNotNull(this.parentNode);//Only parent Node is rendered
            if (this.view_type === 'explorer') {
                Y.Assert.areSame(expandedNode.getAttribute('class'), 'rn_CategoryExplorerExpanded');
                Y.Assert.areSame(expandedNode.getAttribute('role'), 'button');
            }
        },

        "Updates the tree in response to the 'response' event": function () {
            if (this.view_type !== 'explorer') {
                var categoryData = widget._convertData(RightNow.JSON.parse('[{"dateAdded":1407216286000,"dateModified":1407216310000,"objectID":"001.001","sortOrder":1,"hasChildren":true,"recordID":"082020204574de750147a1909bd9007ff6","referenceKey":"WINDOWS","name":"Windows&#039;s Special characters -- !@#$%*() &amp;lt;b&amp;gt; html &amp;lt;\/b&amp;gt;","externalType":"CATEGORY"},{"dateAdded":1407216292000,"dateModified":1407216292000,"objectID":"001.002","sortOrder":2,"hasChildren":false,"recordID":"082020204574de750147a1909bd9007ff3","referenceKey":"LINUX","name":"Linux","externalType":"CATEGORY"}]'));
                var response = RightNow.JSON.parse('[{"data": {"data_type" : "", "current_root" : "OPERATING_SYSTEMS", "hier_data" : "", "label" : "Operating Systems", "linking_on" : 0, "linkingProduct" : 0, "value" : "", "reset" : false, "level" : 1}}]');
                response[0].data.hier_data = categoryData;
                widget.getSubLevelResponse(null, response);
                Y.Assert.areSame(Y.one('#ygtvlabelel3').get('text'), "Windows's Special characters -- !@#$%*() <b> html </b>");
            }
        }
    }));
    return tests;
});
UnitTest.run();
