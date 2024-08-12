UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AssetOrgList_0'
}, function(Y, widget, baseSelector){
    var orgListTests = new Y.Test.Suite({
        name: "standard/search/AssetOrgList",
        
        setUp: function() {
            var testExtender = {
                initValues: function() {
                    this.instanceID = 'AssetOrgList_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.reportID = this.widgetData.attrs.report_id;
                    this.dropdown = document.getElementById('rn_' + this.instanceID + '_Options');
                    this.defaultValue = 0;
                    this.defaultEventData = {fltr_id: 5, oper_id: 1, report_id: this.reportID, rnSearchType: 'org', searchName: 'org', data: {selected: 2}};
                }
            };
            
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });
    //@@@ QA 130228-000107 RightNow A&C: End User Asset Visibility in CP set through the Asset config verb
    orgListTests.add(new Y.Test.Case({
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
                Y.Assert.areSame(this.defaultEventData.data.selected, parseInt(this.dropdown.value, 10)); 
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
                Y.Assert.areSame(this.defaultValue, parseInt(this.dropdown.value, 10)); 
            }, this)
            .fire("reset", eo);
        },

        testSearchSourceChange: function(){
            this.initValues();
            var defaultData = this.defaultEventData;
            defaultData.data.selected = 1;
            var eo = new RightNow.Event.EventObject(this.instance, {filters: defaultData})
            this.instance.searchSource().on("orgChanged", function(type, args){
                Y.Assert.areSame("orgChanged", type);
                args = args[0];
                Y.Assert.isInstanceOf(RightNow.Event.EventObject, args);
                Y.Assert.isObject(args.filters);
                Y.Assert.areSame(this.instanceID, args.w_id);
                Y.Assert.areSame(1, parseInt(this.dropdown.value, 10)); 
            }, this)
            .fire("orgChanged", eo);
        }
    }));
    
    return orgListTests;
});
UnitTest.run();
