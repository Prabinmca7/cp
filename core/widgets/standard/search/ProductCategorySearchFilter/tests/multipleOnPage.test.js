UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProductCategorySearchFilter_0',
    subInstanceIDs: ['ProductCategorySearchFilter_1']
}, function(Y, widget, baseSelector) {
    var tests = new Y.Test.Suite({
        name: 'standard/search/ProductCategorySearchFilter',
    });

    tests.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        'Second instance of widget stays in sync with the first' : function() {
            var instance1 = baseSelector,
                instance2 = '#rn_ProductCategorySearchFilter_1',
                category = {id: 70, label: 'Mobile Broadband', selector: '#ygtvlabelel6'};

            Y.one(instance1 + '_Category_Button').simulate('click');
            Y.one(category.selector).simulate('click');
            widget.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));

            //Check that the value is now set to Mobile Broadband
            widget.searchSource().once('send', function(evtName, eo) {
                eo = eo[0].allFilters[widget.data.js.searchName];
                Y.Assert.areSame(widget.data.js.oper_id, eo.filters.oper_id);
                Y.Assert.areSame(widget.data.js.fltr_id, eo.filters.fltr_id);
                Y.Assert.areSame(widget.data.attrs.report_id, eo.filters.report_id);
                Y.Assert.areSame(widget.data.js.searchName, eo.filters.searchName);
                Y.Assert.areSame('menufilter', eo.filters.rnSearchType);
                Y.Assert.areSame(category.id, eo.filters.data[0][0]);
                return false;
            }, this);

            // Ensure second instance has same selection
            widget.searchSource().once('response', function() {
                this.resume(function() {
                    Y.Assert.areSame(category.label, Y.one(instance2 + '_ButtonVisibleText').get('innerHTML'));
                });
            }, this);

            widget.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));
            this.wait();
        },

        'First instance of widget stays in sync with the second' : function() {
            var instance1 = baseSelector,
                instance2 = '#rn_ProductCategorySearchFilter_1',
                category = {id: 161, label: 'Basics', selector: '#ygtvlabelel10'};

            widget.searchSource().fire('reset');
            Y.one(instance2 + '_Category_Button').simulate('click');
            Y.one(category.selector).simulate('click');
            widget.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));

            widget.searchSource().once('send', function(evtName, eo) {
                eo = eo[0].allFilters[widget.data.js.searchName];
                Y.Assert.areSame(category.id, eo.filters.data[0][0]);
                return false;
            }, this);

            // Ensure first instance has same selection
            widget.searchSource().once('response', function() {
                this.resume(function() {
                    Y.Assert.areSame(category.label, Y.one(instance1 + '_ButtonVisibleText').get('innerHTML'));
                });
            }, this);

            widget.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));
            this.wait();
        }
    }));
    return tests;
});
UnitTest.run();
