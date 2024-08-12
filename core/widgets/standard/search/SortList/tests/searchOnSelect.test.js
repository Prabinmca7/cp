UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SortList_0'
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
                    this.selectedDropdownValue = null;
                }
            };
            
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }            
        }
    });
    sortListTests.add(new Y.Test.Case({
        name: "Search on select",
        /**
         * Tests the SortList widget's search event operation
         * to ensure correct functional operation based on changes to the UI
         */
        testColumnSearchOnSelect: function() {
            this.initValues();
        
            this.instance.searchSource()
            .on("search", function(type, args) {
                Y.Assert.areSame("search", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(this.instanceID, args.w_id);

                Y.Assert.areSame(this.selectedIndex, document.getElementById(this.instance.baseDomID + "_Headings").value);
                Y.Assert.areSame(this.direction, document.getElementById(this.instance.baseDomID + "_Direction").value);
            }, this)
            .on("send", function() { return false; });
            
            var col = document.getElementById(this.instance.baseDomID + "_Headings"),
                options = col.options.length;
            for (var i = 0; i < options; i++) {
                this.selectedIndex = i;
                col.selectedIndex = i;
            }
        },
        
        /**
         * Tests the SortList widget's search event operation
         * to ensure correct functional operation based on changes to the UI
         */
        testDirectionSearchOnSelect: function() {
            this.initValues();
        
            this.instance.searchSource()
            .on("search", function(type, args) {
                Y.Assert.areSame("search", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(this.instanceID, args.w_id);

                Y.Assert.areSame(this.sortby, document.getElementById(this.instance.baseDomID + "_Headings").value);
                Y.Assert.areSame(this.selectedIndex, document.getElementById(this.instance.baseDomID + "_Direction").value);
            }, this)
            .on("send", function() { return false; });
            
            var col = document.getElementById(this.instance.baseDomID + "_Direction"),
                options = col.options.length;
            for (var i = 0; i < options; i++) {
                this.selectedIndex = i;
                col.selectedIndex = i;
            }
        }
    }));
    
    return sortListTests;
});
UnitTest.run();
