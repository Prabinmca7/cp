UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'ModerationFilterBreadcrumbs_0'
}, function(Y, widget, baseSelector){
    var testSuite = new Y.Test.Suite({
        name: "standard/moderation/ModerationFilterBreadcrumbs",
        setUp: function(){
            var testExtender = {
                getResponseObject: function(testFilters) {
                    return new RightNow.Event.EventObject(null, {
                        filters: {
                            allFilters: testFilters
                        }
                    });
                }
            }
            for(var item in this.items) {
                Y.mix(this.items[item], testExtender);
            }
        }
    });
    testSuite.add(new Y.Test.Case({

        name: 'Test Question filters',

        'A response with a Question Status displays the filter': function() {
            Y.one(baseSelector + '_FilterContainer').setHTML('');
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector).getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer');
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a lable element in the correct place
                var labelDiv = filterContainer.one('.rn_Selected');
                Y.assert(labelDiv);
                Y.Assert.areSame('Status', Y.Lang.trim(labelDiv.get('text')), 'Incorrect label');

                //The filter has 2 elements with the correct value
                var filterElements = filterContainer.all('.rn_FilterItem');
                Y.assert(filterElements);
                Y.Assert.areSame(3, filterElements.size());

                var expectedElements = ['Active', 'Suspended'];
                Y.Array.each(filterElements, function(element, index) {
                    Y.Assert.areSame(expectedElements[index], element.getHTML());
                }, this);

                //Has a remove element
                var removeLink = filterContainer.one('.rn_Selected a');
                Y.assert(removeLink);
                Y.Assert.areSame('Remove Filter', Y.Lang.trim(removeLink.get('title').toString()));

                //Simulate clear filter and make sure filter is removed
                removeLink.simulate('click');
                var labelDiv = filterContainer.one('.rn_Selected');
                Y.Assert.areSame(null, labelDiv);

            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                "questions.status": {
                    filters: {
                        data : '29,30'
                    }
                }
            }));
            Y.one(baseSelector + '_FilterContainer').setHTML('');
        },
        'Ensure that a default filter questions.updated with value last_90_days does not display': function() {
            Y.one(baseSelector + '_FilterContainer').setHTML('');
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));
            widget.searchSource().once('response', function() {
                //Widget is hidden, the value is a default.
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('none', Y.one(baseSelector).getStyle('display'));

                //Filter container is empty, no filter is displayed
                Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));
            }, this);
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                "questions.updated": {
                    filters: {
                        data: 'last_90_days'
                    }
                }
            }));
            Y.one(baseSelector + '_FilterContainer').setHTML('');

        },

        'Ensure that a non default filter value of questions.updated is displayed in the breadcrumb': function() {
            Y.one(baseSelector + '_FilterContainer').setHTML('');
            widget.searchSource().once('response', function() {
                //Widget is  not hidden
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector+ '_FilterContainer').getStyle('display'));

                //Filter container is not empty, filter is displayed
                Y.Assert.areSame(true, Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML().length >0));

                var filterContainer = Y.one(baseSelector + '_FilterContainer');
                //Has a remove element
                var removeLink = filterContainer.one('.rn_Selected a');
                Y.assert(removeLink);
                Y.Assert.areSame('Remove Filter', Y.Lang.trim(removeLink.get('title').toString()));

                //Simulate clear filter and make sure filter is removed
                removeLink.simulate('click');
                var labelDiv = filterContainer.one('.rn_Selected');
                Y.Assert.areSame(null, labelDiv);

            }, this);
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                "questions.updated": {
                    filters: {
                        data: 'last_24_hours'
                    }
                }
            }));
            Y.one(baseSelector + '_FilterContainer').setHTML('');
        },

        'A response with a questions.updated displays the filter': function() {
            Y.one(baseSelector + '_FilterContainer').setHTML('');
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector+ '_FilterContainer').getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer');
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a lable element in the correct place
                var labelDiv = filterContainer.one('.rn_Selected');
                Y.assert(labelDiv);
                Y.Assert.areSame('Updated Date', Y.Lang.trim(labelDiv.get('text')));

                //The filter has 1 elements with the correct value
                var filterElements = filterContainer.all('.rn_FilterItem');
                Y.assert(filterElements);
                Y.Assert.areSame(2, filterElements.size());
                Y.Assert.areSame('Updated Date  , Last 30 days', Y.Lang.trim(filterElements.get('text').toString()));

            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                "questions.updated": {
                    filters: {
                        data : 'last_30_days'
                    }
                }
            }));
            Y.one(baseSelector + '_FilterContainer').setHTML('');
        },

        'A response with a Product displays the filter': function() {
            Y.one(baseSelector + '_FilterContainer').setHTML('');
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector+ '_FilterContainer').getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer');
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a lable element in the correct place
                var labelDiv = filterContainer.one('.rn_Selected');
                Y.assert(labelDiv);
                Y.Assert.areSame('Product', Y.Lang.trim(labelDiv.get('text')), 'Incorrect label');

                //The filter has 2 elements with the correct value
                var filterElements = filterContainer.all('.rn_FilterItem_P');
                Y.assert(filterElements);
                Y.Assert.areSame(3, filterElements.size());
                var prodLabels = '';
                for (var i = filterElements.size() - 1; i >= 0; i--) {
                    prodLabels += filterElements.item(i).getHTML().trim() + (i !== 0 ? ">" : "");
                }
                Y.Assert.areSame("Mobile Phones>Android>Nexus One", prodLabels);
                //Has a remove element
                var removeLink = filterContainer.one('.rn_Selected a');
                Y.assert(removeLink);
                Y.Assert.areSame('Remove Filter', Y.Lang.trim(removeLink.get('title').toString()));

                //Simulate clear filter and make sure filter is removed
                removeLink.simulate('click');
                var labelDiv = filterContainer.one('.rn_Selected');
                Y.Assert.areSame(null, labelDiv);

            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {
                return false;
            });
            var responseFilter = {
                p: {
                    filters: {
                        data: [[1, 2, 9]]
                    }
                }
            };
            responseFilter.p.filters.data['reconstructData'] = [{"level": 3, "label": "Nexus One", "hierList": "1,2,9"}, {"level": 2, "label": "Android", "hierList": "1,2"}, {"level": 1, "label": "Mobile Phones", "hierList": "1"}];
            widget.searchSource().fire('response', this.getResponseObject(responseFilter));
            Y.one(baseSelector + '_FilterContainer').setHTML('');
        },
        'A response with a Product data and labels mismatch will not display the filter': function() {
            Y.one(baseSelector + '_FilterContainer').setHTML('');
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector+ '_FilterContainer').getStyle('display'));

                //Has no filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer');
                Y.assert(filterContainer);
                Y.Assert.areSame(0, filterContainer.get('childNodes').size());
            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {
                return false;
            });
            //Input with not data and labels mismatch
            var responseFilter = {
                p: {
                    filters: {
                        data: []
                    }
                }
            };
            responseFilter.p.filters.data['reconstructData'] = [{"level": 3, "label": "Nexus One", "hierList": "1,2,9"}, {"level": 2, "label": "Android", "hierList": "1,2"}, {"level": 1, "label": "Mobile Phones", "hierList": "1"}];
            widget.searchSource().fire('response', this.getResponseObject(responseFilter));
            Y.one(baseSelector + '_FilterContainer').setHTML('');
        },
        'A questions.updated with custom date range displays the filter': function() {
            Y.one(baseSelector + '_FilterContainer').setHTML('');
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector+ '_FilterContainer').getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer');
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a lable element in the correct place
                var labelDiv = filterContainer.one('.rn_Selected');
                Y.assert(labelDiv);
                Y.Assert.areSame('Updated Date', Y.Lang.trim(labelDiv.get('text')));

                //The filter has 1 elements with the correct value
                var filterElements = filterContainer.all('.rn_FilterItem');
                Y.assert(filterElements);
                Y.Assert.areSame(2, filterElements.size());
                Y.Assert.areSame('Updated Date  , 01/01/2014 - 12/31/2014', Y.Lang.trim(filterElements.get('text').toString()));

            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {return false;});
            widget.searchSource().fire('response', this.getResponseObject({
                "questions.updated": {
                    filters: {
                        data : '01/01/2014|12/31/2014'
                    }
                }
            }));
            Y.one(baseSelector + '_FilterContainer').setHTML('');
        },
        'A response with a Category displays the filter': function() {
            Y.one(baseSelector + '_FilterContainer').setHTML('');
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector+ '_FilterContainer').getStyle('display'));

                //Has only one filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer');
                Y.assert(filterContainer);
                Y.Assert.areSame(1, filterContainer.get('childNodes').size());

                //Has a lable element in the correct place
                var labelDiv = filterContainer.one('.rn_Selected');
                Y.assert(labelDiv);
                Y.Assert.areSame('Category', Y.Lang.trim(labelDiv.get('text')), 'Incorrect label');

                //The filter has 2 elements with the correct value
                var filterElements = filterContainer.all('.rn_FilterItem_C');
                Y.assert(filterElements);
                Y.Assert.areSame(1, filterElements.size());

                //Has a remove element
                var removeLink = filterContainer.one('.rn_Selected a');
                Y.assert(removeLink);
                Y.Assert.areSame('Remove Filter', Y.Lang.trim(removeLink.get('title').toString()));

                //Simulate clear filter and make sure filter is removed
                removeLink.simulate('click');
                var labelDiv = filterContainer.one('.rn_Selected');
                Y.Assert.areSame(null, labelDiv);

            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {
                return false;
            });
            var responseFilter = {
                c: {
                    filters: {
                        data: [[77]]
                    }
                }
            };
            responseFilter.c.filters.data['reconstructData'] = [{"level": 2, "label": "Call Quality", "hierList": "71,77"}, {"level": 1, "label": "Troubleshooting", "hierList": "71"}];
            widget.searchSource().fire('response', this.getResponseObject(responseFilter));
            Y.one(baseSelector + '_FilterContainer').setHTML('');
        },
        'A response with a Category data and labels mismatch will not display the filter': function() {
            Y.one(baseSelector + '_FilterContainer').setHTML('');
            Y.Assert.areSame(widget.data.attrs.label_title, Y.Lang.trim(Y.one(baseSelector + ' .rn_Heading').getHTML()));
            Y.Assert.areSame('', Y.Lang.trim(Y.one(baseSelector + '_FilterContainer').getHTML()));

            widget.searchSource().once('response', function() {
                //Widget is visible
                Y.assert(Y.one(baseSelector));
                Y.Assert.areSame('block', Y.one(baseSelector+ '_FilterContainer').getStyle('display'));

                //Has no filter
                var filterContainer = Y.one(baseSelector + '_FilterContainer');
                Y.assert(filterContainer);
                Y.Assert.areSame(0, filterContainer.get('childNodes').size());
            }, this);

            //Make sure that no searches are performed.
            widget.searchSource().once('send', function() {
                return false;
            });
            //Input with not data and labels mismatch
            var responseFilter = {
                c: {
                    filters: {
                        data: []
                    }
                }
            };
            responseFilter.c.filters.data['reconstructData'] = [{"level": 2, "label": "Call Quality", "hierList": "71,77"}, {"level": 1, "label": "Troubleshooting", "hierList": "71"}];
            widget.searchSource().fire('response', this.getResponseObject(responseFilter));
            Y.one(baseSelector + '_FilterContainer').setHTML('');
        },
        "Test AreArraysEqual method": function() {
            Y.Assert.isTrue(widget.areArraysEqual(new Array(1, 2, 3), new Array(3, 2, 1)));
            Y.Assert.isTrue(widget.areArraysEqual(new Array("1", "2", "3"), new Array(3, 1, 2)));
            Y.Assert.isTrue(widget.areArraysEqual(new Array(), new Array()));
            Y.Assert.isFalse(widget.areArraysEqual(new Array(1), new Array()));
            Y.Assert.isFalse(widget.areArraysEqual(new Array(1), new Array("1", 2)));
            Y.Assert.isFalse(widget.areArraysEqual(new Array(1, 2, 3), new Array("1", 2)));
        }
    }));
    return testSuite;
});
UnitTest.run();
