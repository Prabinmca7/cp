UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'WebSearchSort_0'
}, function(Y, widget, baseSelector){
    var webSearchSortTests = new Y.Test.Suite({
        name: "standard/search/WebSearchSort",
            
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'WebSearchSort_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                }
            };
            
            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }            
        }
    });
        
    webSearchSortTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",
        
        /**
         * Tests the WebSearchSort's response to a sortType change
         * to ensure correct functional operation based on changes to the ui
         */
        testSortType: function()
        {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject();
            eo.w_id = this.instanceID;
            eo.filters.searchName = this.widgetData.searchName;
            eo.filters.report_id = this.widgetData.attrs.report_id; 
            eo.data.name = "all";
            eo.filters.data = {"col_id": 2,
                    "sort_direction": 1,
                    "sort_order": 1
            };
            
            this.instance.searchSource()
                .on("webSearchSortChanged", function(type, args) {
                        Y.Assert.areSame("webSearchSortChanged", type);
                        args = args[0];
                        Y.Assert.isObject(args.filters);
                        Y.Assert.areSame(this.instanceID, args.w_id);

                        Y.Assert.areSame(args.filters.data.col_id + '', document.getElementById("rn_" + this.instanceID + "_Options").value);
                    }, this)
                .fire("webSearchSortChanged", eo);
        },
        
        /**
         * Tests the WebSearchSort widget's reportResponse event handling
         * to ensure correct functional operation based on changes to the ui
         */
        testReport: function() {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject();
            eo.w_id = this.instanceID;
            eo.filters.searchName = this.widgetData.searchName;
            eo.filters.report_id = this.widgetData.attrs.report_id; 
            eo.data.name = "all";
            eo.filters.data = {"col_id": 2,
                    "sort_direction": 1,
                    "sort_order": 1
            };
            
            this.instance.searchSource()
                .on("response", function(type, args) {
                        Y.Assert.areSame("response", type);
                        args = args[0];
                        Y.Assert.isObject(args.filters);
                        Y.Assert.areSame(this.instanceID, args.w_id);

                        Y.Assert.areSame(args.filters.data.col_id + '', document.getElementById("rn_" + this.instanceID + "_Options").value);
                    }, this)
                .fire("response", eo);
        },
        
        /**
         * Test the WebSearchSort widget's searchFilterResponse and resetFilterRequest
         * event handling functions to ensure correct functional operation based on changes to the ui.
         */
        testFilters: function() {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject(),
                currentSelection = document.getElementById("rn_" + this.instanceID + "_Options").value;
            eo.w_id = this.instanceID;
            eo.filters.searchName = "webSearchSort";
            eo.filters.report_id = this.widgetData.attrs.report_id; 
            eo.data.name = "all";
            eo.filters.data = {"col_id": 3,
                    "sort_direction": 1,
                    "sort_order": 1
            };
            
            this.instance.searchSource()
                .on("search", function(type, args) {
                        Y.Assert.areSame("search", type);
                        args = args[0];
                        Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                        Y.Assert.isObject(args.filters);
                        Y.Assert.areSame(this.instanceID, args.w_id);

                        Y.Assert.areSame(currentSelection, document.getElementById("rn_" + this.instanceID + "_Options").value);
                    }, this)
                .on("send", function() { return false; })
                .fire("search", eo);
        
            this.instance.searchSource()
                .on("reset", function(type, args) {
                        Y.Assert.areSame("reset", type);
                        args = args[0];
                        Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                        Y.Assert.isObject(args.filters);
                        Y.Assert.areSame(this.instanceID, args.w_id);

                        Y.Assert.areSame(this.widgetData.js.sortDefault + '', document.getElementById("rn_" + this.instanceID + "_Options").value);
                    }, this)
                .fire("reset", eo);
        }
    }));

    return webSearchSortTests;
});
UnitTest.run();
