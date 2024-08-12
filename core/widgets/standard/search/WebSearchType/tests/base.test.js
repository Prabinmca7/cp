UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'WebSearchType_0'
}, function(Y, widget, baseSelector){
    var webSearchTypeTests = new Y.Test.Suite({
        name: "standard/search/WebSearchType",
            
        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'WebSearchType_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.dropdown = document.getElementById("rn_" + this.instanceID + "_Options");
                }
            };
            
            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }            
        }
    });
        
    webSearchTypeTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",
        
        /**
         * Tests WebSearchType widget's searchTypeResponse operation
         * to ensure correct functional operation based on changes to the ui
         */
        testSearchType: function()
        {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject();
            eo.w_id = this.instanceID;
            eo.filters = {
                "searchName": this.widgetData.searchName,
                "report_id": this.widgetData.attrs.report_id,
                "data": 3
            };
            
            eo.data.name = "all";
            
            this.instance.searchSource()
                .on("webSearchTypeChanged", function(type, args) {
                        Y.Assert.areSame("webSearchTypeChanged", type);
                        args = args[0];
                        Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                        Y.Assert.isObject(args.filters);
                        Y.Assert.areSame(this.instanceID, args.w_id);

                        Y.Assert.areSame(args.filters.data + '', this.dropdown.value);
                    }, this)
                .fire("webSearchTypeChanged", eo);
        
            RightNow.Event.subscribe("evt_searchTypeResponse", this.responseSearchTypeEventHandler, this);
            RightNow.Event.fire("evt_searchTypeResponse", eo);
        },
        
        /**
         * Tests the WebSearchType widget's searchFiltersResponse and resetFilterResponse
         * to ensure correct functional operation based on changes to the ui
         */
        testFilters: function()
        {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject(),
                currentSelection = this.dropdown.value;
            eo.w_id = this.instanceID;
            eo.filters = {"searchName": this.widgetData.searchName,
                    "report_id": this.widgetData.attrs.report_id,
                    "data": 3
                   };
            eo.data.name = "all";
            
            this.instance.searchSource()
                .on("search", function(type, args) {
                        Y.Assert.areSame("search", type);
                        args = args[0];
                        Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                        Y.Assert.isObject(args.filters);
                        Y.Assert.areSame(this.instanceID, args.w_id);

                        Y.Assert.areSame(currentSelection, this.dropdown.value);
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

                        Y.Assert.areSame(this.widgetData.js.searchDefault + '', this.dropdown.value);
                    }, this)
                .fire("reset", eo);
        
            RightNow.Event.subscribe("evt_searchFiltersResponse", this.responseSearchTypeEventHandler, this);
            RightNow.Event.fire("evt_getFiltersRequest", eo);
            
            RightNow.Event.subscribe("evt_resetFilterRequest", this.responseSearchTypeEventHandler, this);
            RightNow.Event.fire("evt_resetFilterRequest", eo);
        },
        
        /**
         * Tests the WebSearchType widget's reportResponse operation
         * to ensure correct functional operation based on changes to the ui
         */
        testReport: function() {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject();
            eo.w_id = this.instanceID;
            eo.filters = {"searchName": this.widgetData.searchName,
                    "report_id": this.widgetData.attrs.report_id,
                    "data": 3
                   };
            eo.data.name = "all";
            
            this.instance.searchSource()
                .on("response", function(type, args) {
                        Y.Assert.areSame("response", type);
                        args = args[0];
                        Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                        Y.Assert.isObject(args.filters);
                        Y.Assert.areSame(this.instanceID, args.w_id);

                        Y.Assert.areSame(args.filters.data + '', this.dropdown.value);
                    }, this)
                .fire("response", eo);
        
            RightNow.Event.subscribe("evt_reportResponse", this.responseSearchTypeEventHandler, this);
            RightNow.Event.fire("evt_reportResponse", eo);
        },

        responseSearchFiltersEventHandler: function(type, args)
        {
            Y.Assert.areSame("evt_searchFiltersResponse", type);
            args = args[0];
            Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
            Y.Assert.isObject(args.filters);
            Y.Assert.areSame(this.instanceID, args.w_id);
            
            Y.Assert.areSame(args.filters.data + '', this.dropdown.value);
        },
        
        requestResetFiltersEventHandler: function(type, args)
        {
            Y.Assert.areSame("evt_resetFilterRequest", type);
            args = args[0];
            Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
            Y.Assert.isObject(args.filters);
            Y.Assert.areSame(this.instanceID, args.w_id);
            
            Y.Assert.areSame(this.widgetData.js.searchDefault + '', this.dropdown.value);
        },
        
        responseSearchTypeEventHandler: function(type, args)
        {
            args = args[0];
            Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
            Y.Assert.isObject(args.filters);
            Y.Assert.areSame(this.instanceID, args.w_id);
            
            Y.Assert.areSame(args.filters.data + '', this.dropdown.value);
        }
    }));
    
    return webSearchTypeTests;
});
UnitTest.run();
