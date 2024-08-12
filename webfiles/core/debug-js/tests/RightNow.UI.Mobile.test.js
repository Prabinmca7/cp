UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.UI.Mobile.js','/euf/core/debug-js/RightNow.Text.js'],
    namespaces: ['RightNow.UI']
}, function (Y) {

    var suite = new Y.Test.Suite({
        name: "RightNow.UI.Mobile",

        setUp: function () {
            RightNow.Interface.setMessagebase(function() {
                return {
                    "DIALOG_PLEASE_READ_TEXT_DIALOG_MSG_MSG": "Please read",
                    "SUCCESS_S_LBL": "Success: %s",
                    "ERROR_PCT_S_LBL": "Error: %s",
                    "WARNING_S_LBL": "Warning: %s",
                    "INFORMATION_S_LBL": "Information: %s"
                };
            });
        }

    });

    suite.add(new Y.Test.Case({
        name: 'Mobile message dialog behavior',

        "Message dialogs' OK button has screen reader text containing the dialog title": function () {
            var dialog = RightNow.UI.Dialog.messageDialog('Electric', { title: 'Telephone'} );

            var button = dialog.getButton(0);
            var screenReaderContent = button.one('.rn_ScreenReaderOnly');

            Y.assert(screenReaderContent);
            Y.Assert.areSame("Telephone Please read", screenReaderContent.getHTML());

            dialog.hide();
            dialog.destroy();
        }
    }));

    suite.add(new Y.Test.Case({
        name: 'Mobile action dialog behavior',

        "Action dialogs' OK button has screen reader text containing the dialog title": function () {
            var title = 'goodbye';
            var dialog = RightNow.UI.Dialog.actionDialog(title, Y.Node.create('<div></div>'));

            dialog.show();

            var button = Y.one('.rn_Panel button');
            var screenReaderContent = button.one('.rn_ScreenReaderOnly');

            Y.assert(screenReaderContent);
            Y.Assert.areSame(title + " Please read", screenReaderContent.getHTML());

            dialog.hide();
            dialog.destroy();
        }
    }));


    suite.add(new Y.Test.Case({
        name: 'Mobile banner behavior',

        setUp: function() {
            this.parent = Y.one(document.body);
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
        }
    }));

    return suite;
}).run();
