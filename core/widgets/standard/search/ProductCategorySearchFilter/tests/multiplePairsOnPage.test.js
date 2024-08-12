UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ProductCategorySearchFilter_0',
    subInstanceIDs: ['ProductCategorySearchFilter_1', 'ProductCategorySearchFilter_2', 'ProductCategorySearchFilter_3']
}, function(Y, widget, baseSelector) {
    var tests = new Y.Test.Suite({
        name: 'standard/search/ProductCategorySearchFilter',
    });

    tests.add(new Y.Test.Case({
        name: "Event Handling and Operation",

        checkEO : function(eo, searchName, value) {
            Y.Assert.areSame(searchName, eo.filters.searchName);
            Y.Assert.areSame('menufilter', eo.filters.rnSearchType);
            if (value === null)
                Y.Assert.areSame(0, eo.filters.data[0].length);
            else
                Y.Assert.areSame(value, eo.filters.data[0][0]);
        },

        'Choose second instances of both product and category and verify syncing' : function() {
            var prodInstance1 = baseSelector,
                catInstance1 = '#rn_ProductCategorySearchFilter_1',
                prodInstance2 = '#rn_ProductCategorySearchFilter_2',
                catInstance2 = '#rn_ProductCategorySearchFilter_3',
                prodWidget1 = widget,
                catWidget1 = RightNow.Widgets.getWidgetInstance('ProductCategorySearchFilter_1'),
                prodWidget2 = RightNow.Widgets.getWidgetInstance('ProductCategorySearchFilter_2'),
                catWidget2 = RightNow.Widgets.getWidgetInstance('ProductCategorySearchFilter_3'),
                product = {id: 162, label: 'Text Messaging', selector: '#ygtvlabelel20'},
                category = {id: 158, label: 'Rollover Minutes', selector: '#ygtvlabelel29'};

            Y.one(prodInstance2 + '_Product_Button').simulate('click');
            Y.one(product.selector).simulate('click');
            Y.one(catInstance2 + '_Category_Button').simulate('click');
            Y.one(category.selector).simulate('click');

            widget.searchSource().once('send', function(evtName, eo) {
                Y.Assert.areSame(product.id, eo[0].allFilters['p'].filters.data[0][0]);
                Y.Assert.areSame(category.id, eo[0].allFilters['c'].filters.data[0][0]);
            }, this);

            widget.searchSource().once('response', function() {
                this.resume(function() {
                    Y.Assert.areSame(product.label, Y.one(prodInstance1 + '_ButtonVisibleText').get('innerHTML'));
                    Y.Assert.areSame(product.label, Y.one(prodInstance2 + '_ButtonVisibleText').get('innerHTML'));
                    Y.Assert.areSame(category.label, Y.one(catInstance1 + '_ButtonVisibleText').get('innerHTML'));
                    Y.Assert.areSame(category.label, Y.one(catInstance2 + '_ButtonVisibleText').get('innerHTML'));
                });
            }, this);

            widget.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));
            this.wait();
        },

        'Choose first instances of both product and category and verify syncing' : function() {
            var prodInstance1 = baseSelector,
                catInstance1 = '#rn_ProductCategorySearchFilter_1',
                prodInstance2 = '#rn_ProductCategorySearchFilter_2',
                catInstance2 = '#rn_ProductCategorySearchFilter_3',
                prodWidget1 = widget,
                catWidget1 = RightNow.Widgets.getWidgetInstance('ProductCategorySearchFilter_1'),
                prodWidget2 = RightNow.Widgets.getWidgetInstance('ProductCategorySearchFilter_2'),
                catWidget2 = RightNow.Widgets.getWidgetInstance('ProductCategorySearchFilter_3'),
                product = {id: 6, label: 'Voice Plans', selector: '#ygtvlabelel3'},
                category = {id: 68, label: 'Account and Billing', selector: '#ygtvlabelel12'};

            Y.one(prodInstance1 + '_Product_Button').simulate('click');
            Y.one(product.selector).simulate('click');
            Y.one(catInstance1 + '_Category_Button').simulate('click');
            Y.one(category.selector).simulate('click');

            widget.searchSource().once('send', function(evtName, eo) {
                Y.Assert.areSame(product.id, eo[0].allFilters['p'].filters.data[0][0]);
                Y.Assert.areSame(category.id, eo[0].allFilters['c'].filters.data[0][0]);
            }, this);

            widget.searchSource().once('response', function() {
                this.resume(function() {
                    Y.Assert.areSame(product.label, Y.one(prodInstance1 + '_ButtonVisibleText').get('innerHTML'));
                    Y.Assert.areSame(product.label, Y.one(prodInstance2 + '_ButtonVisibleText').get('innerHTML'));
                    Y.Assert.areSame(category.label, Y.one(catInstance1 + '_ButtonVisibleText').get('innerHTML'));
                    Y.Assert.areSame(category.label, Y.one(catInstance2 + '_ButtonVisibleText').get('innerHTML'));
                });
            }, this);

            widget.searchSource().fire('search', new RightNow.Event.EventObject(this, { filters: {data: 'test'} }));
            this.wait();
        }
    }));
    return tests;
});
UnitTest.run();
