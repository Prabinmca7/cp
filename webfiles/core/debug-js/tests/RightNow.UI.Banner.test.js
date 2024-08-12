UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.UI.js'],
    namespaces: ['RightNow.UI'],
    preloadFiles: ['/euf/core/debug-js/RightNow.Text.js']
}, function(Y){
    var rightnowUITests = new Y.Test.Suite("RightNow.UI banner");

    rightnowUITests.add(new Y.Test.Case({
        name: "Banner",

        setUp: function() {
            this.parent = Y.one(document.body);
            RightNow.Interface.setMessagebase(function() {
                return {
                    "SUCCESS_S_LBL": "Success: %s",
                    "ERROR_PCT_S_LBL":"Error: %s",
                    "WARNING_S_LBL":"Warning: %s",
                    "INFORMATION_S_LBL":"Information: %s"
                };
            });
        },

        tearDown: function() {
            Y.one(document.body).all('.rn_BannerAlert').remove();
            this.parent = null;
        },

        assertProperInsertion: function(content) {
            var el = this.parent.all('> *').slice(-1).item(0);
            Y.assert(el.hasClass('rn_BannerAlert'));
            Y.Assert.areSame((content || 'bananas').toLowerCase(), el.one('.rn_AlertMessage').getHTML().toLowerCase());
        },

        "Inserted in body": function() {
            RightNow.UI.displayBanner('bananas');
            this.assertProperInsertion('Success: bananas');
        },

        "HTML is allowed in the message": function() {
            var content = '<strong>TENN</strong>';
            RightNow.UI.displayBanner(content);
            this.assertProperInsertion('Success: ' + content);
        },

        "Focuses on the banner when option is set": function() {
            RightNow.UI.displayBanner('bananas', { focus: true });
            this.wait(function() {
                Y.Assert.areSame(Y.Node.getDOMNode(this.parent.one('.rn_Alert')), document.activeElement);
            }, 600);
        },

        "Doesn't focus on the box if told not to do so": function() {
            RightNow.UI.displayBanner('bananas', { focus: false });
            this.wait(function() {
                Y.Assert.areNotSame(Y.Node.getDOMNode(this.parent.one('.rn_Alert')), document.activeElement);
            }, 600);
        },

        "Default: Success type box": function() {
            RightNow.UI.displayBanner('bananas');
            Y.assert(this.parent.one('.rn_BannerAlert').hasClass('rn_SuccessAlert'));
            this.assertProperInsertion('Success: bananas');
        },

        "Can be an Error type box": function() {
            RightNow.UI.displayBanner('bananas', { type: 'error' });
            Y.assert(this.parent.one('.rn_BannerAlert').hasClass('rn_ErrorAlert'));
            this.assertProperInsertion('Error: bananas');
        },

        "Can be an Info type box": function() {
            RightNow.UI.displayBanner('bananas', { type: 'INFO' });
            Y.assert(this.parent.one('.rn_BannerAlert').hasClass('rn_InfoAlert'));
            this.assertProperInsertion('Information: bananas');
        },

        "Can be a Success type box": function() {
            RightNow.UI.displayBanner('bananas', { type: 'success' });
            Y.assert(this.parent.one('.rn_BannerAlert').hasClass('rn_SuccessAlert'));
            this.assertProperInsertion('Success: bananas');
        },

        "Can be a Warning type box": function() {
            RightNow.UI.displayBanner('bananas', { type: 'WARNING' });
            Y.assert(this.parent.one('.rn_BannerAlert').hasClass('rn_WarningAlert'));
            this.assertProperInsertion('Warning: bananas');
        },

        "No close button": function() {
            RightNow.UI.displayBanner('bananas', { close: false });
            Y.assert(!this.parent.one('a.rn_CloseLink'));
        },

        "Close label is customizable": function() {
            RightNow.UI.displayBanner('bananas', { parent: this.parent, close: true, closeLabel: 'follow your arrow' });
            Y.Assert.areSame('follow your arrow', this.parent.one('a.rn_CloseLink').get('text'));
        },

        "Event: fires `click` when clicked": function() {
            var target = RightNow.UI.displayBanner('bananas', { parent: this.parent, close: true }),
                clicked = false;

            target.on('click', function () {
                clicked = true;
            });

            this.parent.one('.rn_BannerAlert').simulate('click');
            Y.assert(clicked);
        },

        "Event: fires `close` when closing": function() {
            var target = RightNow.UI.displayBanner('bananas', { parent: this.parent, close: true }),
                closed = false;

            target.on('click', function () {
                closed = true;
            });

            this.parent.one('a.rn_CloseLink').simulate('click');
            Y.assert(closed);
        },

        "Event: fires `blur` when closing": function() {
            var target = RightNow.UI.displayBanner('bananas', { parent: this.parent, close: true }),
                blurred = false;

            target.on('blur', function () {
                blurred = true;
            });

            this.parent.one('a.rn_CloseLink').simulate('blur');
            if (!Y.UA.ie) {
                Y.assert(blurred);
            }
        },

        testFocusLastElement: function() {
            Y.one('body').append(Y.Node.create('<div class="outer"><div class="inner"><button class="button">button</button></div></div>'));

            // Shouldn't Focus
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: false});
            this.assertFocusedItem();

            // No focus item was set, so still don't focus
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true});
            this.assertFocusedItem();

            // Outer Element - focusElement
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, focusElement: Y.one('.outer')});
            this.assertFocusedItem('outer');

            // Inner Element - focusElement
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, focusElement: Y.one('.inner')});
            this.assertFocusedItem('inner');

            // Invalid Element - focusElement - no baseClass set, so don't focus
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, focusElement: null});
            this.assertFocusedItem();

            // Invalid Element - focusElement - baseClass set, fall back to baseClass focus
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, focusElement: null, baseClass: 'outer'});
            this.assertFocusedItem('button');

            // Outer Element - focusElement overrides baseClass
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, focusElement: Y.one('.outer'), baseClass: 'inner'});
            this.assertFocusedItem('outer');

            // Outer Element - focus Button since it's a naturally focusable element
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, baseClass: 'outer'});
            this.assertFocusedItem('button');

            // Inner Element - focus Button since it's a naturally focusable element
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, baseClass: 'inner'});
            this.assertFocusedItem('button');

            // Invalid Element - baseElement - don't focus
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, baseClass: null});
            this.assertFocusedItem();

            // Remove the naturally focusable item
            Y.one('.button').destroy();

            // Outer Element - give 'inner' child a tab index and focus it
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, baseClass: 'outer'});
            this.assertFocusedItem('inner');
            Y.Assert.areSame(Y.one('.inner').get('tabIndex'), 0);
            Y.one('.inner').set('tabIndex', null);

            // Inner Element - give 'inner' element a tab index and focus it
            RightNow.UI.displayBanner(this.successMessage, {close: 1, focus: true, baseClass: 'inner'});
            this.assertFocusedItem('inner');
            Y.Assert.areSame(Y.one('.inner').get('tabIndex'), 0);
        },

        assertFocusedItem: function(className) {
            this.wait(function() {
                Y.Assert.areSame(document.activeElement.className, className ? className : '');
                document.activeElement.blur();
            }, 10);
        }
    }));

    return rightnowUITests;
});
UnitTest.run();
