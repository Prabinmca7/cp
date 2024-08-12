UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SortList_0',
}, function(Y, widget, baseSelector){
    var sortListTests = new Y.Test.Suite({
        
        name: "standard/search/SortList",
        setUp: function() {
            var testExtender = {
                direction: "2",
                sortby: "1",
                                
                initValues: function() {
                    this.instanceID = 'SortList_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.searchOnSelect = this.widgetData.attrs.search_on_select;
                    this.reportID = this.widgetData.attrs.report_id;
                }
            };
            
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }            
        }
    });
    sortListTests.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        /**
         * Tests the SortList widget's sortChange event operation
         * to ensure correct functional operation based on changes to the UI
         */
        testSortType: function() {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject({}, {filters: {
                searchName: this.widgetData.searchName,
                report_id: this.reportID,
                data: {
                    col_id: parseInt(this.sortby, 10),
                    sort_direction: parseInt(this.direction, 10),
                    reportPage: this.widgetData.attrs.report_page_url
                }
            }});
            
            RightNow.Event.subscribe('evt_sortChange', function(type, args) {
                Y.Assert.areSame("evt_sortChange", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);

                Y.Assert.areSame(this.sortby, document.getElementById("rn_SortList_0_Headings").value);
                Y.Assert.areSame(this.direction, document.getElementById("rn_SortList_0_Direction").value);
            }, this);
            RightNow.Event.fire("evt_sortChange", eo);
        },
        
        /**
         * Tests the SortList widget's search event operation
         * to ensure correct functional operation based on changes to the UI
         */
        testSearchFilters: function() {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject({}, {filters: {
                searchName: this.widgetData.searchName,
                report_id: this.reportID,
                data: {
                    col_id: parseInt(this.sortby, 10),
                    sort_direction: parseInt(this.direction, 10),
                    reportPage: this.widgetData.attrs.report_page_url
                }
            }, data: {name: "all"}});
        
            this.instance.searchSource()
            .on("search", function(type, args) {
                Y.Assert.areSame("search", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);

                Y.Assert.areSame(this.sortby, document.getElementById("rn_SortList_0_Headings").value);
                Y.Assert.areSame(this.direction, document.getElementById("rn_SortList_0_Direction").value);
            }, this)
            .on("send", function() { return false; })
            .fire("search", eo);
        },
        
        /**
         * Tests the SortList widget's reportResponse event operation
         * to ensure correct functional operation based on changes to the UI
         */
        testReport: function() {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject({}, {filters: {
                searchName: this.widgetData.searchName,
                report_id: this.reportID,
                data: {
                    col_id: parseInt(this.sortby, 10),
                    sort_direction: parseInt(this.direction, 10),
                    reportPage: this.widgetData.attrs.report_page_url
                }
            }});

            this.instance.searchSource().on("response", function(type, args) {
                args = args[0];
                Y.Assert.isObject(args.filters);

                Y.Assert.areSame(this.sortby, document.getElementById("rn_SortList_0_Headings").value);
                Y.Assert.areSame(this.direction, document.getElementById("rn_SortList_0_Direction").value);    
            }, this)
            .fire("response", eo);
        },
        
        /**
         * Tests the SortList widget's search event operation
         * to ensure correct functional operation based on changes to the UI
         */
        testReset: function() {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject(this, {filters: {
                searchName: this.widgetData.searchName,
                report_id: this.reportID,
                data: {
                    col_id: parseInt(this.sortby, 10),
                    sort_direction: parseInt(this.direction, 10),
                    reportPage: this.widgetData.attrs.report_page_url
                }
            }, data: {name: "all"}});

            this.instance.searchSource().on("reset", function(type, args) {
                Y.Assert.areSame("reset", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(this.instanceID, args.w_id);

                Y.Assert.areSame(this.widgetData.js.col_id + '', document.getElementById("rn_SortList_0_Headings").value);
                Y.Assert.areSame(this.widgetData.js.sort_direction + '', document.getElementById("rn_SortList_0_Direction").value);    
            }, this)
            .fire("reset", eo);
        }
    }));
    
    return sortListTests;
});
UnitTest.run();


