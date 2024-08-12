UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'DisplaySearchFilters_0'
}, function(Y, widget, baseSelector){
    var testSuite = new Y.Test.Suite({
        name: "standard/search/DisplaySearchFilters",
        setUp: function(){
            var testExtender = {
                getResponseObject: function(testFilters) {
                    return new RightNow.Event.EventObject(null, {
                        filters: {
                            allFilters: testFilters
                        }
                    });
                },
                textContent: (Y.UA.ie) ? 'innerText' : 'textContent'
            }
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });
    testSuite.add(new Y.Test.Case({
        name: 'Test product and category filters',

        'A response with a product displays the filter': function() {
            widget.data.attrs.label_filter_remove = widget.data.attrs.label_filter_remove.replace('%s', 'Product');
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector).getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer')
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a remove element in the correct place
                var removeDiv = filterContainer.one('.rn_Label');
                Y.assert(removeDiv);
                Y.Assert.areSame('Product ' + widget.data.attrs.label_filter_remove, Y.Lang.trim(removeDiv.get('innerText')));

                //The filter has anchor elements with the correct value
                var filterElements = filterContainer.all('a.rn_FilterItem');
                Y.assert(filterElements);
                Y.Assert.areSame(2, filterElements.size());

                var expectedElements = ['Mobile Phones', 'iPhone'],
                    expectedUrls = ['p/1', 'p/4'];
                Y.Array.each(filterElements, function(element, index) {
                    Y.Assert.areSame(expectedElements[index], element.getHTML());
                    Y.assert(element.get('href').indexOf(expectedUrls[index]) !== -1);
                }, this);

                //Verify the remove button and click it triggering a reset event
                var removeLink = removeDiv.one('a');
                Y.assert(removeLink);
                Y.Assert.areSame(widget.data.attrs.label_filter_remove, removeLink.get('title'));
                removeLink.simulate('click')
            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                p: {
                    filters: {
                        data: {
                            '0': ['1','4','160'],
                            reconstructData: [{
                                    hierList: '1',
                                    label: 'Mobile Phones'
                                }, {
                                    hierList: '1,4',
                                    label: 'iPhone'
                                }, {
                                    hierList: '1,4,160',
                                    label: 'iPhone 3GS'
                                }
                            ]
                        }
                    }
                }
            }));
        },
        'A response with a product displays the filter from an integer value': function() {
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector).getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer')
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a remove element in the correct place
                var removeDiv = filterContainer.one('.rn_Label');
                Y.assert(removeDiv);
                Y.Assert.areSame('Product ' + widget.data.attrs.label_filter_remove, Y.Lang.trim(removeDiv.get('innerText')));

                //The filter has three elements with the correct value
                var filterElements = filterContainer.all('a.rn_FilterItem');
                Y.assert(filterElements);
                Y.Assert.areSame(2, filterElements.size());

                var expectedElements = ['Mobile Phones', 'iPhone'],
                    expectedUrls = ['p/1', 'p/4'];
                Y.Array.each(filterElements, function(element, index) {
                    Y.Assert.areSame(expectedElements[index], element.getHTML());
                    Y.assert(element.get('href').indexOf(expectedUrls[index]) !== -1);
                }, this);

                //Verify the remove button and click it triggering a reset event
                var removeLink = removeDiv.one('a');
                Y.assert(removeLink);
                Y.Assert.areSame(widget.data.attrs.label_filter_remove, removeLink.get('title'));
                removeLink.simulate('click')
            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                p: {
                    filters: {
                        data: {
                            '0': [1,4,160],
                            reconstructData: [{
                                    hierList: '1',
                                    label: 'Mobile Phones'
                                }, {
                                    hierList: '1,4',
                                    label: 'iPhone'
                                }, {
                                    hierList: '1,4,160',
                                    label: 'iPhone 3GS'
                                }
                            ]
                        }
                    }
                }
            }));
        },
        'A response with a product displays the filter from a string filter': function() {
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector).getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer')
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a remove element in the correct place
                var removeDiv = filterContainer.one('.rn_Label');
                Y.assert(removeDiv);
                Y.Assert.areSame('Product ' + widget.data.attrs.label_filter_remove, Y.Lang.trim(removeDiv.get('innerText')));

                //The filter has three elements with the correct value
                var filterElements = filterContainer.all('a.rn_FilterItem');
                Y.assert(filterElements);
                Y.Assert.areSame(2, filterElements.size());

                var expectedElements = ['Mobile Phones', 'iPhone'],
                    expectedUrls = ['p/1', 'p/4'];
                Y.Array.each(filterElements, function(element, index) {
                    Y.Assert.areSame(expectedElements[index], element.getHTML());
                    Y.assert(element.get('href').indexOf(expectedUrls[index]) !== -1);
                }, this);

                //Verify the remove button and click it triggering a reset event
                var removeLink = removeDiv.one('a');
                Y.assert(removeLink);
                Y.Assert.areSame(widget.data.attrs.label_filter_remove, removeLink.get('title'));
                removeLink.simulate('click')
            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                p: {
                    filters: {
                        data: {
                            '0': '1,4,160'
                        }
                    }
                }
            }));
        },
        'A response with a searchType displays a filter': function() {
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));
            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector).getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer')
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a remove element in the correct place
                var removeDiv = filterContainer.one('.rn_Label');
                Y.assert(removeDiv);
                Y.Assert.areSame('Search Type ' + widget.data.attrs.label_filter_remove, Y.Lang.trim(removeDiv.get('innerText')));

                //The filter has three elements with the correct value
                var filterElement = filterContainer.one('.rn_FilterItem');
                Y.assert(filterElement);
                Y.Assert.areSame('Custom Int Search', Y.Lang.trim(filterElement.getHTML()));
                
                //Verify the remove button and click it triggering a reset event
                var removeLink = removeDiv.one('a');
                Y.assert(removeLink);
                Y.Assert.areSame(widget.data.attrs.label_filter_remove, removeLink.get('title'));
                removeLink.simulate('click')
            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                searchType: {
                    filters: {
                        data: {
                            val: 8,
                            label: 'Custom Int Search'
                        }
                    }
                }
            }));
        },
        'A response with an org displays a filter': function() {
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));
            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector).getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer')
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a remove element in the correct place
                var removeDiv = filterContainer.one('.rn_Label');
                Y.assert(removeDiv);
                Y.Assert.areSame('Organization ' + widget.data.attrs.label_filter_remove, Y.Lang.trim(removeDiv.get('innerText')));

                //The filter has three elements with the correct value
                var filterElement = filterContainer.one('.rn_FilterItem');
                Y.assert(filterElement);
                Y.Assert.areSame('My incidents and my organizations', Y.Lang.trim(filterElement.getHTML()));
       
                //Verify the remove button and click it triggering a reset event
                var removeLink = removeDiv.one('a');
                Y.assert(removeLink);
                Y.Assert.areSame(widget.data.attrs.label_filter_remove, removeLink.get('title'));
                removeLink.simulate('click')
            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                org: {
                    filters: {
                        data: {
                            selected: 1,
                            label: 'My incidents and my organizations'
                        }
                    }
                }
            }));
        },
        'A filter with HTML in the label has the content escaped': function() {
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));
            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector).getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer')
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a remove element in the correct place
                var removeDiv = filterContainer.one('.rn_Label');
                Y.assert(removeDiv);
                Y.Assert.areSame('Organization ' + widget.data.attrs.label_filter_remove, Y.Lang.trim(removeDiv.get('innerText')));

                //The filter has three elements with the correct value
                var filterElement = filterContainer.one('.rn_FilterItem');
                Y.assert(filterElement);
                Y.Assert.areSame('&lt;strong&gt;all the incidents!&lt;/strong&gt;', Y.Lang.trim(filterElement.getHTML()).toLowerCase());

                //Verify the remove button and click it triggering a reset event
                var removeLink = removeDiv.one('a');
                Y.assert(removeLink);
                Y.Assert.areSame(widget.data.attrs.label_filter_remove, removeLink.get('title'));
                removeLink.simulate('click')
            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                org: {
                    filters: {
                        data: {
                            selected: 2,
                            label: '<strong>All the incidents!</strong>'
                        }
                    }
                }
            }));
        },
        'Ensure that a default org does not display': function() {
            //Only run this test with the org render, since the default is only set if a contact is logged
            //in and has an org.
            if(RightNow.Url.getParameter('org')) {
                Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));
                widget.searchSource().once('response', function() {
                    //Widget is hidden, the value is a default.
                    Y.assert(Y.one(baseSelector));
                    Y.Assert.areSame('none', Y.one(baseSelector).getStyle('display'));

                    //Filter container is empty, no filter is displayed
                    Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));
                }, this);
                widget.searchSource().fire('response', this.getResponseObject({
                    org: {
                        filters: {
                            data: {
                                selected: 0,
                                label: 'Only My Incidents'
                            }
                        }
                    }
                }));
            }
        }
    }));
    return testSuite;
});
UnitTest.run();
