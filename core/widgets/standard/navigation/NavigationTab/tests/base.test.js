UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'NavigationTab_0',
}, function(Y, widget, baseSelector){
    var navigationTabTests = new Y.Test.Suite({
        name: "standard/navigation/NavigationTab",

        setUp: function(){
            var testExtender = {
                initValues : function() {
                    this.instanceID = 'NavigationTab_0';
                    this.instance = RightNow.Widgets.getWidgetInstance(this.instanceID);
                    this.widgetData = this.instance.data;
                    this.toggleElement = document.getElementById("rn_" + this.instanceID + "_DropdownButton");
                    this.tabElement = document.getElementById("rn_" + this.instanceID);
                    this.dropdownElement = document.getElementById("rn_" + this.instanceID + "_SubNavigation");
                    if (this.dropdownElement)
                        this.linkElements = Y.one(this.dropdownElement).get('children');
                }
            };

            for(var item in this.items)
            {
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    navigationTabTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",

        /**
         * Tests the widget's response to a click event on the toggle element, if one is present
         */
        testClick: function() {
            this.initValues();
            if (this.toggleElement) {
                var node = Y.one(this.toggleElement);
                node.simulate('click');
                Y.Assert.isFalse(Y.one(this.dropdownElement).hasClass("rn_ScreenReaderOnly"));
                node.simulate('click');
                Y.Assert.isTrue(Y.one(this.dropdownElement).hasClass("rn_ScreenReaderOnly"));
            }
        },

        /**
         * Tests the widget's response to a focus event if the widget has a dropdown element present
         */
        testFocus: function() {
            this.initValues();

            if (this.dropdownElement) {
                var node = Y.one(this.dropdownElement);
                Y.Assert.isTrue(node.hasClass("rn_ScreenReaderOnly"));

                //Trigger an event and check if the dropdown appears.
                if (Y.UA.ie) {
                    this.linkElements._nodes[0].fireEvent('onfocus');
                } else {
                    var testEvent = document.createEvent("HTMLEvents");
                    testEvent.initEvent('focus', true, true);
                    this.linkElements._nodes[0].dispatchEvent(testEvent);
                }

                Y.Assert.isFalse(node.hasClass("rn_ScreenReaderOnly"));
                Y.one(this.toggleElement).simulate('click');
                Y.Assert.isTrue(node.hasClass("rn_ScreenReaderOnly"));
            }
        },

        /**
         * Tests widget visibility based on searches_done criteria
         */
        testVisibility: function() {
            this.initValues();

            if (this.widgetData.attrs.searches_done) {
                if (this.widgetData.js.searches < this.widgetData.attrs.searches_done) {
                    Y.Assert.isTrue(Y.one(this.tabElement).hasClass('rn_Hidden'), "Tab is visible when it should be hidden.");
                }
                else {
                    Y.Assert.isFalse(Y.one(this.tabElement).hasClass('rn_Hidden'), "Tab is hidden when it should be visible.");
                }
            }
        },

        /**
         * Tests widget visibility if kicking off a search request is going to bump it over the searches_done criteria
         */
        testSearchRequest: function() {
            this.initValues();

            if (this.widgetData.attrs.searches_done &&
                this.widgetData.js.searches + 1 === this.widgetData.attrs.searches_done) {
                Y.Assert.isTrue(Y.one(this.tabElement).hasClass('rn_Hidden'), "Tab is visible when it should be hidden.");
                RightNow.Event.fire("evt_searchRequest");
                Y.Assert.isFalse(Y.one(this.tabElement).hasClass('rn_Hidden'), "Tab is hidden when it should be visible.");
            }
        },

        /**
         * If this widget has a toggle element, then this tests the widgets response to the tab
         * keydown event.
         */
        testTab: function() {
            this.initValues();

            if (this.toggleElement && this.dropdownElement) {
                //Click the toggleElement to open the menu.
                var dropdown = Y.one(this.dropdownElement)
                var toggle = Y.one(this.toggleElement);
                toggle.simulate('click');

                //Target the link and shift+tab off of it.
                Y.Assert.isFalse(dropdown.hasClass("rn_ScreenReaderOnly"));
                var link = document.getElementById('rn_' + this.instanceID + '_Link');
                link.focus();
                Y.one(link).simulate('keydown', {"keyCode" : 9 /* TAB */, "shiftKey" : true});

                //Lost focus, menu should collapse
                Y.Assert.isTrue(dropdown.hasClass("rn_ScreenReaderOnly"));

                //Reopen the menu, and tab off the link so it collapses
                toggle.simulate('click');
                Y.Assert.isFalse(dropdown.hasClass("rn_ScreenReaderOnly"));
                var lastElement = this.linkElements._nodes[this.linkElements._nodes.length - 1];
                lastElement.focus();
                Y.one(lastElement).simulate('keydown', {"keyCode" : 9 /* TAB */});
                Y.Assert.isTrue(dropdown.hasClass("rn_ScreenReaderOnly"));
            }
        },

        /**
         * if the widget has a toggle element then this will test the widgets response to a mouseout
         * event occurring within it's SubNavigation component.
         */
        testMouseOut: function() {
            this.initValues();

            if(this.toggleElement && this.dropdownElement) {
                var toggle = Y.one(this.toggleElement);
                var dropdown = Y.one(this.dropdownElement);

                if(dropdown.hasClass("rn_ScreenReaderOnly")) {
                    toggle.simulate('click');
                    Y.Assert.isFalse(dropdown.hasClass("rn_ScreenReaderOnly"));
                }

                dropdown.simulate('mouseover');
                dropdown.simulate('mouseout');

                Y.Assert.isTrue(dropdown.hasClass("rn_ScreenReaderOnly"));
            }
        }
    }));
    return navigationTabTests;
});
UnitTest.run();
