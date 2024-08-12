UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'AccountDropdown_0'
}, function (Y, widget, baseSelector) {

    var suite = new Y.Test.Suite({
        name: "standard/login/AccountDropdown"
    });

    suite.add(new Y.Test.Case({
        name: 'Functional tests',

        dropdown: Y.one(baseSelector + ' ' + baseSelector + '_SubNavigation'),

        trigger: Y.one(baseSelector + ' .rn_AccountDropdownTrigger'),

        tearDown: function () {
            this.clickBody();
        },

        clickBody: function () {
            Y.one(document.body).simulate('click');
        },

        clickButton: function () {
            this.trigger.simulate('click');
        },

        dropdownIsHidden: function () {
            return this.dropdown.hasClass('rn_Hidden');
        },

        "Dropdown shows when the trigger button is clicked": function () {
            Y.Assert.isTrue(this.dropdownIsHidden());
            this.clickButton();
            Y.Assert.isFalse(this.dropdownIsHidden());
        },

        "Dropdown hides when anything is clicked": function () {
            this.clickButton();
            Y.Assert.isFalse(this.dropdownIsHidden());
            this.clickBody();
            Y.Assert.isTrue(this.dropdownIsHidden());
        },

        "Arrow keys navigate the menu": function () {
            this.clickButton();

            this.trigger.simulate('keydown', { keyCode: 38 }); // Up
            Y.Assert.isTrue(this.trigger.compareTo(document.activeElement));

            this.trigger.simulate('keydown', { keyCode: 40 }); // Down
            Y.Assert.isTrue(this.dropdown.one('a').compareTo(document.activeElement));
        },

        "Tab key inside the menu closes the dropdown": function () {
            this.clickButton();
            Y.Assert.isTrue(this.trigger.compareTo(document.activeElement));

            this.trigger.simulate('keydown', { keyCode: 9 }); // Tab

            Y.Assert.isTrue(this.dropdownIsHidden());
        },

        "Esc key inside the menu closes the dropdown and focuses the trigger": function () {
            this.clickButton();

            this.trigger.simulate('keydown', { keyCode: 27 }); // Escape
            Y.Assert.isTrue(this.dropdownIsHidden());
            Y.Assert.isTrue(this.trigger.compareTo(document.activeElement));
        }
    }));

    return suite;
}).run();
