UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'VisualProductCategorySelector_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/navigation/VisualProductCategorySelector"
    });

    suite.add(new Y.Test.Case({
        name: "Behavior and Event Handling",

        setUp: function() {
            this.link = Y.one('.rn_ShowChildren');
            this.makeRequest = RightNow.Ajax.makeRequest;
            RightNow.Ajax.makeRequest = function () {
                RightNow.Ajax.makeRequest.calledWith = Array.prototype.slice.call(arguments);
            };
        },

        tearDown: function() {
            Y.all('.rn_Loading').addClass('rn_Hidden');

            RightNow.Ajax.makeRequest.called = 0;
            RightNow.Ajax.makeRequest.calledWith = null;
            RightNow.Ajax.makeRequest = this.makeRequest;
        },

        "Widget does not automatically capture focus on page load when initial_focus is false": function() {
            Y.Assert.areNotSame(Y.Node(document.activeElement).getHTML(), Y.one(".rn_ItemLink").getHTML());
        },

        "Global event is fired prior to doing AJAX request": function() {
            var eventArgs,
                eventHandler = function(evt, args) {
                    eventArgs = args;
                    return false;
                };

            RightNow.Event.on('evt_ItemRequest', eventHandler);

            this.link.simulate('click');

            Y.assert(!RightNow.Ajax.makeRequest.called);
            Y.Assert.isArray(eventArgs);
            Y.Assert.isNotNull(eventArgs[0].data.items);
            Y.Assert.areSame('product', eventArgs[0].data.filter);
            Y.Assert.isFalse(eventArgs[0].data.linking);
            Y.Assert.isNull(widget.paginationNode);

            RightNow.Event.unsubscribe('evt_ItemRequest', eventHandler);
        },

        "AJAX request goes through properly and loading indicator is set while waiting for response": function() {
            this.link.setAttribute('data-id', 2);
            this.link.simulate('click');

            Y.Assert.areSame(widget.data.attrs.sub_item_ajax, RightNow.Ajax.makeRequest.calledWith[0]);
            Y.Assert.isNumber(RightNow.Ajax.makeRequest.calledWith[1].id);
            Y.Assert.isFalse(RightNow.Ajax.makeRequest.calledWith[1].linking);
            Y.Assert.areSame('product', RightNow.Ajax.makeRequest.calledWith[1].filter);

            var loadDiv = Y.one(baseSelector + ' .rn_Items').one('*');
            Y.assert(loadDiv.hasClass('rn_Loading'));
            Y.assert(!loadDiv.hasClass('rn_Hidden'));
            this.link.setAttribute('data-id', 2);
        },

        "Global event is fired upon response from the server": function() {
            var eventArgs,
                eventHandler = function(evt, args) {
                    eventArgs = args;
                    return false;
                };

            RightNow.Event.on('evt_ItemResponse', eventHandler);

            var htmlPriorToResponse = Y.one(baseSelector).getHTML();

            widget._childrenResponse({ result: ['banana'] }, { data: { id: 'genuine' }});

            Y.Assert.areSame(htmlPriorToResponse, Y.one(baseSelector).getHTML());
            Y.Assert.isArray(eventArgs);
            Y.Assert.areSame('genuine', eventArgs[0].data.data.id);
            Y.Assert.areSame('banana', eventArgs[0].response.result[0]);

            RightNow.Event.unsubscribe('evt_ItemResponse', eventHandler);
        },

        "After getting a new sub-level of items, the previous level is hidden and the new level is shown": function() {
            widget.itemLevels[0] = { el: Y.one(baseSelector).one('.rn_ItemGroup'), label: 'identical', id: 'tropic' };

            widget._childrenResponse({ result: [[
                { id: 27, label: 'bananas', hasChildren: true },
                { id: 67, label: 'del mundo', hasChildren: false }
            ]]}, { data: {}});
    
            this.wait(function() {                
            }, 1000);

            Y.assert(widget.itemLevels[0].el.hasClass('rn_Hidden'));

            Y.assert(Y.one(baseSelector + '_tropic_SubItems'));
            Y.assert(Y.one('.rn_ItemWithID27'));
            Y.assert(Y.one('.rn_ItemWithID27 a.rn_ItemLink'));
            Y.assert(Y.one('.rn_ItemWithID27 .rn_VisualItemContainer img'));
            var text = Y.Lang.trim(Y.all('.rn_ItemWithID27 a.rn_ItemLink').get('text'));
            Y.Assert.isTrue(text[1].indexOf('bananas') > -1);
            Y.assert(Y.one('.rn_ItemWithID27 a.rn_ShowChildren'));
            Y.assert(Y.one('.rn_ItemWithID27 a.rn_ItemLink').getAttribute('href').indexOf('/p/27') > -1);

            Y.assert(Y.one('.rn_ItemWithID67'));
            Y.assert(Y.one('.rn_ItemWithID67 a.rn_ItemLink'));
            Y.assert(Y.one('.rn_ItemWithID67 .rn_VisualItemContainer img'));
            var text = Y.Lang.trim(Y.all('.rn_ItemWithID67 a.rn_ItemLink').get('text'));
            Y.Assert.isTrue(text[1].indexOf('del mundo') > -1);
            Y.assert(!Y.one('.rn_ItemWithID67 a.rn_ShowChildren'));
            Y.assert(Y.one('.rn_ItemWithID67 a.rn_ItemLink').getAttribute('href').indexOf('/p/67') > -1);
            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(Y.one(baseSelector + '_tropic_SubItems a')));
        },

        "The generated links use landing_page_url and contain appended parameters and session": function() {
            widget.data.attrs.landing_page_url = 'http://placesheen.com';
            widget.data.js.appendedParameters = '/c/bizarre/session/bronca';
            widget.itemLevels[0] = { el: Y.one(baseSelector).one('.rn_ItemGroup'), label: 'identical', id: 'melo' };

            widget._childrenResponse({ result: [[
                { id: 12, label: 'tabacco', hasChildren: true },
                { id: 13, label: 'freize', hasChildren: false }
            ]]}, { data: {}});

            Y.Assert.areSame('http://placesheen.com/c/bizarre/session/bronca/p/12/~/tabacco', Y.one('.rn_ItemWithID12 a.rn_ItemLink').getAttribute('href'));
            Y.Assert.areSame('http://placesheen.com/c/bizarre/session/bronca/p/13/~/freize', Y.one('.rn_ItemWithID13 a.rn_ItemLink').getAttribute('href'));
        },

        "Broken get replaced by a placeholder image": function() {
            widget.itemLevels[0] = { el: Y.one(baseSelector).one('.rn_ItemGroup'), label: 'found', id: 'cora' };

            widget._childrenResponse({ result: [[
                { id: 165, label: 'hoy', hasChildren: true },
                { id: 166, label: 'silence', hasChildren: false }
            ]]}, { data: {}});
    
            this.wait(function() {                
            }, 1000);

            this.wait(function() {
                Y.one(baseSelector + '_cora_SubItems').all('img').each(function(img) {
                    Y.Assert.areSame(widget.data.attrs.image_path + '/default.png', img.getAttribute('src'));
                });
            }, 2000);
        },

        "Description divs are same height": function() {
            widget.itemLevels[0] = { el: Y.one(baseSelector).one('.rn_ItemGroup'), label: 'found', id: 'height' };

            var longTitle = '';
            for(var i = 0; i < 10; i++) {
                longTitle += 'oh boy this is a really long title that will wrap and cause it to be tall! ';
            }

            widget._childrenResponse({ result: [[
                { id: 265, label: longTitle, hasChildren: true },
                { id: 266, label: 'I am short', hasChildren: false },
                { id: 267, label: 'I, too, am short', hasChildren: false }
            ]]}, { data: {}});

            this.wait(function() {
                var containerHeight;
                Y.all(baseSelector + '_height_SubItems .rn_ActionContainer').each(function(el) {
                    if(!containerHeight) {
                        containerHeight = el.get('clientHeight');
                    }
                    else {
                        Y.Assert.areSame(containerHeight, el.get('clientHeight'));
                    }
                });
            }, 1000);
        },

        "Loading indicator is shown and aria-busy is set to true": function() {
            widget._toggleLoading(true);
            Y.Assert.areSame(1, Y.one(baseSelector).all('.rn_Loading').size());
            Y.assert(!Y.one(baseSelector).one('.rn_Loading').hasClass('rn_Hidden'));
            Y.Assert.areSame('true', Y.one(document.body).getAttribute('aria-busy'));
        },

        "Loading indicator is hidden and aria-busy is set to false": function() {
            widget._toggleLoading(false);
            Y.Assert.areSame(1, Y.one(baseSelector).all('.rn_Loading').size());
            Y.assert(Y.one(baseSelector).one('.rn_Loading').hasClass('rn_Hidden'));
            Y.Assert.areSame('false', Y.one(document.body).getAttribute('aria-busy'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Breadcrumb Behavior",

        setUp: function() {
            widget.currentLevel = 0;
            widget.itemLevels = [];

            this.currentLevel = 0;
            this.startingIndex = 100;

            Y.all('.rn_ItemGroup').slice(1).remove();
            Y.one('.rn_ItemGroup').removeClass('rn_Hidden');

            this.originalBreadCrumb = Y.one('.rn_BreadCrumb').getHTML();
        },

        tearDown: function() {
            Y.one('.rn_BreadCrumb').setHTML(this.originalBreadCrumb);
        },

        addNewLevel: function() {
            var parentID = this.currentLevel + 100,
                previousElement = (!this.currentLevel)
                    ? Y.one(baseSelector).one('.rn_ItemGroup')
                    : Y.one(baseSelector).one('.rn_ItemGroup.rn_Item_' + (parentID - 1) + '_SubItems');

            widget.itemLevels.push({ el: previousElement, label: 'bananas ' + this.currentLevel, id: parentID });
            widget.currentLevel = ++this.currentLevel;
            widget._childrenResponse({ result: [[
                { id: this.startingIndex++, label: 'bananas ' + this.startingIndex, hasChildren: true }
            ]]}, { data: {}});
        },

        verifyBreadCrumb: function(expectedItems) {
            Y.Assert.areSame(expectedItems, Y.one('.rn_BreadCrumb').all('a').size());
            Y.Assert.areSame(expectedItems, Y.one('.rn_BreadCrumb').all('a.rn_BreadCrumbLink').size());
            Y.Assert.areSame(expectedItems, Y.one('.rn_BreadCrumb').all('.rn_BreadCrumbSeparator').size());
            Y.Assert.areSame(1, Y.one('.rn_BreadCrumb').all('.rn_CurrentLevelBreadCrumb').size());

            // First link should always be the top-level title.
            Y.Assert.areSame(widget.data.attrs.label_breadcrumb, Y.Lang.trim(Y.one('.rn_BreadCrumb .rn_BreadCrumbLink').get('text')));
            // Current level should always the be final non-link crumb.
            Y.Assert.areSame('bananas ' + (expectedItems - 1), Y.Lang.trim(Y.one('.rn_BreadCrumb .rn_CurrentLevelBreadCrumb').get('text')));

            // Verify the in-between links.
            for (var i = 1, crumbs = Y.one('.rn_BreadCrumb').all('.rn_BreadCrumbLink'); i < expectedItems; i++) {
                Y.Assert.areSame('bananas ' + (i - 1), Y.Lang.trim(crumbs.item(i).get('text')));
            }
        },

        "New level is added and top level is turned into a link": function() {
            this.addNewLevel();
            this.verifyBreadCrumb(1);
        },

        "Subsequent levels are added and previous levels are turned into links": function() {
            this.addNewLevel();
            this.addNewLevel();
            this.addNewLevel();
            this.addNewLevel();
            this.verifyBreadCrumb(4);
        },

        "Clicking on a previous level shows that level": function() {
            this.addNewLevel();
            this.addNewLevel();
            this.addNewLevel();

            Y.one('.rn_BreadCrumb').all('a').slice(-1).item(0).simulate('click');
            this.verifyBreadCrumb(2);

            Y.one('.rn_BreadCrumb').all('a').slice(-1).item(0).simulate('click');
            this.verifyBreadCrumb(1);
        }
    }));

    return suite;
}).run();
