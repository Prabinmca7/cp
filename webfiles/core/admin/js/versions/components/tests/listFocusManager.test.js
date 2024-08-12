UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['ListFocusManager'],
    preloadFiles: [
        '/euf/core/admin/js/versions/components/tests/bootstrap.js',
        '/euf/core/admin/js/versions/components/tabs.js',
        '/euf/core/admin/js/versions/components/component.js',
        '/euf/core/admin/js/versions/components/helpers.js',
        '/euf/core/admin/js/versions/components/listFocusManager.js'
    ]
}, function(Y) {

    var container = Y.Node.create('<div class="widgetPanel"></div>')
        .append('<div class="listing-item">0</div>')
        .append('<div class="listing-item">1</div>')
        .append('<div class="listing-item">2</div>');

    Y.one(document.body).append(container);

    var calledWith;
    new Y.ListFocusManager(container).on('select', function (e) {
        calledWith = e;
    });

    var suite = new Y.Test.Suite({ name: "List focus behavior" });

    suite.add(new Y.Test.Case({
        name: "Behavior",

        setUp: function () {
            this.input = Y.one('.widgetPanel');
        },

        "Correct item is focused when ↓ or ↑ is pressed": function () {
            this.input.focus();

            container.simulate('keydown', { keyCode: 40 }); //Down

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.input.all('*').item(1)));

            container.simulate('keydown', { keyCode: 38 }); //Back Up

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.input.all('*').item(0)));
        },

        "No op when ↑ is pressed while focused on first item": function () {
            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.input.all('*').item(0)));

            container.simulate('keydown', { keyCode: 38 }); //Up

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.input.all('*').item(0)));
        },

        "No op when ↓ is pressed on the last visible item": function () {
            container.simulate('keydown', { keyCode: 40 }); // Down to item 2.
            container.simulate('keydown', { keyCode: 40 }); // Down to last item.

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.input.all('*').item(2)));

            container.simulate('keydown', { keyCode: 40 }); // Nada.

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.input.all('*').item(2)));

            container.simulate('keydown', { keyCode: 38 }); //Up to item 2
            container.simulate('keydown', { keyCode: 38 }); //Up to first item

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.input.all('*').item(0)));
        },

        "First item is selected when hidden item is attempted to be navigated to": function () {
            container.prepend('<div class="listing-item hide">3</div>');

            container.simulate('keydown', { keyCode: 38 }); // Up to hidden item

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(this.input.all('*').item(1))); //Get first non-hidden item

            this.input.all('*').item(0).remove(); //Remove the node we added
        },

        "`select` event is triggered when enter is pressed": function () {
            container.simulate('keydown', { keyCode: 13 });

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(calledWith.target));
        },

        "tabindex of last item is retained when container is tabbed off of": function () {
            Y.Assert.areSame(0, this.input.all('*').item(0).get('tabIndex'));

            container.simulate('keydown', { keyCode: 9, shiftKey: true }); //Shift+tab off of container
            container.simulate('keydown', { keyCode: 9}); //tab back to container

            Y.Assert.areSame(0, this.input.all('*').item(0).get('tabIndex'));
            Y.Assert.areSame(1, this.input.all('[tabindex]').size());
        }
    }));

    return suite;
}).run();
