UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'FilterDropdown_0',
}, function(Y, widget, baseSelector){
    var tests = new Y.Test.Suite({
        name: 'standard/search/FilterDropdown',
        setUp: function() {
            var testExtender = {                
                initValues : function() {
                    this.instanceID = 'FilterDropdown_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.selectNode = Y.one('#rn_' + this.instanceID + '_Options');
                } 
            };
            
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }   
        }
    });

    tests.add(new Y.Test.Case({
        name: 'Event Handling',
        'Verify that search event provides initial filter values': function() {
            this.initValues();
            this.instance.searchSource().once('send', function(evtName, args) {
                Y.Assert.areSame('send', evtName);
                args = args[0].allFilters.acct_id.filters;
                if(!RightNow.Url.getParameter('acct_id'))
                    Y.Assert.areSame('~any~', args.data.val);
                else
                    Y.Assert.areSame('10', args.data.val);
                Y.Assert.areSame('172', args.report_id);
                Y.Assert.areSame('acct_id', args.searchName);
                return false;
            }, this);
            this.instance.searchSource().fire('search');
        },
        'Verify that changing the selected value updates the filter value': function() {
            this.initValues();
            this.selectNode.set('value', '2').simulate('change');

            this.instance.searchSource().once('send', function(evtName, args) {
                Y.Assert.areSame('send', evtName);
                args = args[0].allFilters.acct_id.filters;
                Y.Assert.areSame('2', args.data.val);
                Y.Assert.areSame('172', args.report_id);
                Y.Assert.areSame('acct_id', args.searchName);
                return false;
            }, this);
            this.instance.searchSource().fire('search');
        }, 
        'Verify that the all reset event reverts the filter to the last value': function() {
            this.initValues();
            var currentValue = this.selectNode.get('value');
            
            this.instance.searchSource().once('reset', function(evtName, args) {
                if(!RightNow.Url.getParameter('acct_id')) {
                    Y.Assert.areSame('~any~', this.selectNode.get('value'));
                    Y.Assert.areSame(0, this.selectNode.get('selectedIndex'));
                }
                else {
                    Y.Assert.areSame('10', this.selectNode.get('value'));
                }
            }, this);   
            this.instance.searchSource().fire('reset', new RightNow.Event.EventObject(this, {data: {name: 'all'}, filters: {report_id: '172'}}));
        },
        'Verify that search response updates the filter value': function() {
            this.initValues();
            this.instance.searchSource().once('response', function(evtName, args) {
                Y.Assert.areSame('2', this.selectNode.get('value'));
            }, this);
            this.instance.searchSource().fire('response', new RightNow.Event.EventObject(this, {filters: {report_id: '172', allFilters: {'acct_id': {filters: {data: {val:'2', fltr_id: 4}}}}}}));
        }
    }));
    return tests;
});
UnitTest.run();