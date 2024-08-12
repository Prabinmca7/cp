UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'SearchTypeList_0'
}, function(Y, widget, baseSelector){
    var searchTypeListTests = new Y.Test.Suite({
        name: "standard/search/SearchTypeList",
        
        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'SearchTypeList_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.searchOnSelect = this.widgetData.attrs.search_on_select;
                    this.reportID = this.widgetData.attrs.report_id;
                    this.defaultFilterID = 5;
                    this.defaultEventData = {fltr_id: 5, oper_id: 1, report_id: this.reportID, rnSearchType: 'searchType', searchName: 'searchType', data: {val: 8, label: 'Exact Search'}};
                }
            };
            
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });
    searchTypeListTests.add(new Y.Test.Case({
        name: "Event Handling and Operation",
        
        testSearchFilters: function() {
            this.initValues();
            var eo = new RightNow.Event.EventObject(this.instance, {filters: this.defaultEventData});
            this.instance.searchSource()
            .on("search", function(type, args) {
                Y.Assert.areSame("search", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.areSame(this.instanceID, args.w_id);
                Y.Assert.isObject(args.filters);
            }, this)
            .on("send", function() { return false; })
            .fire("search", eo);
        },
        
        testReport: function() {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject(this.instance, {filters: this.defaultEventData});
            this.instance.searchSource().on("response", function(type, args) {
                args = args[0];
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(this.instanceID, args.w_id);
                Y.Assert.areSame(this.defaultEventData.data.val, parseInt(document.getElementById('rn_' + this.instanceID + '_Options').value, 10)); 
            }, this)
            .fire("response", eo);
        },
        
        testReset: function() {
            this.initValues();
            
            var eo = new RightNow.Event.EventObject(this.instance, {filters: this.defaultEventData, data:{name:'all'}});

            this.instance.searchSource().on("reset", function(type, args) {
                Y.Assert.areSame("reset", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(this.instanceID, args.w_id);
                Y.Assert.areSame(this.defaultFilterID, parseInt(document.getElementById('rn_' + this.instanceID + '_Options').value, 10)); 
            }, this)
            .fire("reset", eo);
        },

        testSearchSourceChange: function(){
            this.initValues();
            var defaultData = this.defaultEventData;
            defaultData.data.val = 7;
            defaultData.data.label = "Similar Phrases";
            var eo = new RightNow.Event.EventObject(this.instance, {filters: defaultData})
            this.instance.searchSource().on("searchTypeChanged", function(type, args){
                Y.Assert.areSame("searchTypeChanged", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(this.instanceID, args.w_id);
                Y.Assert.areSame(7, parseInt(document.getElementById('rn_' + this.instanceID + '_Options').value, 10)); 
            }, this)
            .fire("searchTypeChanged", eo);
        }
    }));
    
    return searchTypeListTests;
});
UnitTest.run();
