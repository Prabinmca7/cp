UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.UI.js'],
    yuiModules: ['panel'],
    namespaces: ['RightNow.UI.Dialog']
}, function(Y){
    var suite = new Y.Test.Suite({
            name: "RightNow.UI.Dialog",
            setUp: function() {
                RightNow.Interface.setMessagebase(function(){return {"WARNING_LBL":"Warning","INFORMATION_LBL":"Information","HELP_LBL":"Help","ERROR_REQUEST_ACTION_COMPLETED_MSG":"There was a problem with the request and the action could not be completed.","OK_LBL":"OK","DIALOG_LBL":"dialog","DIALOG_PLEASE_READ_TEXT_DIALOG_MSG_MSG":"dialog, please read above text for dialog message","BEG_DIALOG_PLS_DISMISS_DIALOG_BEF_MSG":"Beginning of dialog, please dismiss dialog before continuing","END_DIALOG_PLS_DISMISS_DIALOG_BEF_MSG":"End of dialog, please dismiss dialog before continuing","CLOSE_CMD":"Close","BACK_LBL":"Back"};});
            }
        }),
        dialogCounter = 1,
        tearDown = function() {
            if(Y.UA.ie)
                Y.later(1100, this.dialog, 'destroy');
            else
                this.dialog.destroy();

            dialogCounter++;
        };

    suite.add(new Y.Test.Case(
    {
        name : "messageDialogTests",
        tearDown: tearDown,

        /**
         * Note: this test must be run first. If it is run after the other tests, delayed dialogs are displayed and can steal the focus
         * between this test simulating the click and checking the actual result. Blame whoever put a 400ms delay in `RightNow.UI.js`
         * dialog display.
         */
        "focusElement is focused when the dialog is closed": function() {
            var a = Y.Node.create('<a href="#">clickme</a>');
            Y.one(document.body).append(a);

            this.dialog = RightNow.UI.Dialog.messageDialog('bananas', { focusElement: a });
            Y.one('#rnDialog' + dialogCounter + ' .yui3-widget-ft button').simulate('click');
            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(a));

            a.remove();
        },

        "focus should be returned to the last active element when the dialog is closed": function() {
            var a = Y.Node.create('<a href="#">clickme</a>');
            Y.one(document.body).append(a);
            a.focus();

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(a));

            this.dialog = RightNow.UI.Dialog.actionDialog("Test Title", Y.Node.create('<div>Hey!</div>'));
            this.dialog.show();

            var dialogButton = Y.one('#rnDialog' + dialogCounter + ' .yui3-widget-ft button');

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(dialogButton));
            dialogButton.simulate('click');

            Y.Assert.areSame(document.activeElement, Y.Node.getDOMNode(a));

            a.remove();
        },

        "Basic messageDialog has the correct default properties": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog("Bananas!");
            var dialogNode = Y.one('#rnDialog' + dialogCounter);
            Y.Assert.isObject(dialogNode);
            Y.Assert.areSame("Information", dialogNode.one('#rn_Dialog_' + dialogCounter + '_Title').getHTML());
            Y.Assert.areSame("Bananas!", dialogNode.one('#rn_Dialog_' + dialogCounter + '_Message').getHTML());
            Y.Assert.isTrue(dialogNode.one('.yui3-widget-ft button').getHTML().indexOf("OK") > 0);
            Y.assert(dialogNode.one('.rn_AlertContent'));
            Y.Assert.areSame("20em", this.dialog.get('width'));
        },

        "Help messageDialog has a help title and icon": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog("dialog", {icon: "HELP", width:"250px"});
            var dialogNode = Y.one('#rnDialog' + dialogCounter);
            Y.Assert.isObject(dialogNode);
            Y.Assert.areSame("Help", dialogNode.one('#rn_Dialog_' + dialogCounter + '_Title').getHTML());
            Y.Assert.areSame("dialog", dialogNode.one('#rn_Dialog_' + dialogCounter + '_Message').getHTML());
            Y.Assert.isTrue(dialogNode.one('.yui3-widget-ft button').getHTML().indexOf("OK") > 0);
            Y.assert(dialogNode.one('.rn_HelpContent'));
            Y.Assert.areSame("250px", this.dialog.get('width'));
        },

        "Warning messageDialog has a warning title and icon": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog("dialog", {icon: "WARN"});
            var dialogNode = Y.one('#rnDialog' + dialogCounter);
            Y.Assert.isObject(dialogNode);
            Y.Assert.areSame("Warning", dialogNode.one('#rn_Dialog_' + dialogCounter + '_Title').getHTML());
            Y.Assert.areSame("dialog", dialogNode.one('#rn_Dialog_' + dialogCounter + '_Message').getHTML());
            Y.Assert.isTrue(dialogNode.one('.yui3-widget-ft button').getHTML().indexOf("OK") > 0);
            Y.assert(dialogNode.one('.rn_WarningContent'));
        },

        "A custom title may be specified": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog("dialog", {title: "Custom Title"});
            var dialogNode = Y.one('#rnDialog' + dialogCounter);
            Y.Assert.isObject(dialogNode);
            Y.Assert.areSame("Custom Title", dialogNode.one('#rn_Dialog_' + dialogCounter + '_Title').getHTML());
            Y.Assert.areSame("dialog", dialogNode.one('#rn_Dialog_' + dialogCounter + '_Message').getHTML());
            Y.Assert.isTrue(dialogNode.one('.yui3-widget-ft button').getHTML().indexOf("OK") > 0);
            Y.assert(dialogNode.one('.rn_AlertContent'));
        },

        "Button enabling and disabling works properly": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog("dialog");
            var buttons = this.dialog.getButtons();

            RightNow.UI.Dialog.disableDialogButtons(this.dialog);
            Y.Assert.areSame('true', buttons.get('disabled') + '');
            RightNow.UI.Dialog.enableDialogButtons(this.dialog);
            Y.Assert.areSame('false', buttons.get('disabled') + '');
        },

        "Key listeners function properly": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog("dialog");
            var dialogNode = Y.one('#rnDialog' + dialogCounter),
                callback = {
                    called: false,
                    reset: function() { this.called = false; },
                    invoke: function() { this.called = true; }
                },
                keyListener = RightNow.UI.Dialog.addDialogEnterKeyListener(this.dialog, callback.invoke, callback);
            Y.Assert.isObject(dialogNode);

            dialogNode.simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(callback.called);
            callback.reset();

            RightNow.UI.Dialog.disableDialogKeyListener(this.dialog, keyListener);
            dialogNode.simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(!callback.called);

            RightNow.UI.Dialog.enableDialogKeyListener(this.dialog, keyListener);
            dialogNode.simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(callback.called);
            callback.reset();
        },

        "A selector can be passed to #addDialogEnterKeyListener, but since message dialog only has text, the callback isn't called": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog("dialog");

            var called = false,
                dialogNode = Y.one('#rnDialog' + dialogCounter);

            RightNow.UI.Dialog.addDialogEnterKeyListener(this.dialog, function() {
                called = true;
            }, null, 'input');

            dialogNode.simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(!called);
        },

        "* Selector can be passed to #addDialogEnterKeyListener": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog("dialog");

            var called = false,
                dialogNode = Y.one('#rnDialog' + dialogCounter);

            RightNow.UI.Dialog.addDialogEnterKeyListener(this.dialog, function(evt, arg) {
                called = evt === 'keyPressed' && arg[1].target.get('tagName') === 'DIV';
            }, null, '*');

            dialogNode.simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(called);
        },

        "Enable/Disable dialog controls works for buttons and enter key press": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog("dialog");
            var dialogNode = Y.one('#rnDialog' + dialogCounter),
                buttons = this.dialog.getButtons(),
                callback = {
                    called: false,
                    reset: function() { this.called = false; },
                    invoke: function() { this.called = true; }
                },
                keyListener = RightNow.UI.Dialog.addDialogEnterKeyListener(this.dialog, callback.invoke, callback);

            RightNow.UI.Dialog.disableDialogControls(this.dialog, keyListener);
            Y.Assert.areSame('true', buttons.get('disabled') + '');
            dialogNode.simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(!callback.called);

            RightNow.UI.Dialog.enableDialogControls(this.dialog, keyListener);
            Y.Assert.areSame('false', buttons.get('disabled') + '');
            dialogNode.simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(callback.called);
        },

        "`exitCallback` is called when the dialog is closed": function() {
            var called = false,
                callback = function() {
                    called = true;
                };

            this.dialog = RightNow.UI.Dialog.messageDialog('banana', { exitCallback: callback });
            Y.one('#rnDialog' + dialogCounter + ' .yui3-widget-ft button').simulate('click');
            Y.assert(called);
        },

        "`exitCallback` is called with a context when the dialog is closed": function() {
            var context,
                test = this,
                callback = function() {
                    context = test === this;
                };

            this.dialog = RightNow.UI.Dialog.messageDialog('banana', { exitCallback: { fn: callback, scope: this }});
            Y.one('#rnDialog' + dialogCounter + ' .yui3-widget-ft button').simulate('click');
            Y.assert(context);
        },

        "Null is returned when receiving inadequate parameters": function() {
            Y.Assert.isNull(RightNow.UI.Dialog.messageDialog());
            Y.Assert.isNull(RightNow.UI.Dialog.enableDialogKeyListener());
            Y.Assert.isNull(RightNow.UI.Dialog.enableDialogKeyListener("one"));
            Y.Assert.isNull(RightNow.UI.Dialog.disableDialogKeyListener());
            Y.Assert.isNull(RightNow.UI.Dialog.disableDialogKeyListener("one"));
            Y.Assert.isNull(RightNow.UI.Dialog.addDialogEnterKeyListener());
            Y.Assert.isNull(RightNow.UI.Dialog.addDialogEnterKeyListener("one"));
            dialogCounter--; // Didn't create a dialog for this test.
        },

        "Screen reader hint with no dialogDescription": function() {
            this.dialog = RightNow.UI.Dialog.messageDialog('Bananas!');

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(1, buttons.size());
            Y.Assert.areSame('Information dialog. Bananas! ', buttons.item(0).one('span.rn_ScreenReaderOnly').getHTML());
        },

        "Screen reader hint use dialogDescription": function() {
            var description = Y.Node.create('<div>hey</div>').set('id', 'hey' + dialogCounter);
            Y.one(document.body).append(description);

            this.dialog = RightNow.UI.Dialog.messageDialog('Bananas!', {dialogDescription: description.get('id')});

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(1, buttons.size());
            Y.Assert.areSame('Information dialog. hey ', buttons.item(0).one('span.rn_ScreenReaderOnly').getHTML());

            description.remove();
        }
    }));

    suite.add(new Y.Test.Case(
    {
        name : "actionDialogTests",
        tearDown: tearDown,

        "Basic default options": function() {
            var content = document.createElement("div");
            content.innerHTML = "Dialog";
            this.dialog = RightNow.UI.Dialog.actionDialog("Title", content);
            var dialogNode = Y.one('#rnDialog' + dialogCounter),
                dialogParentNode = dialogNode.get('parentNode');

            Y.Assert.isObject(dialogNode);
            Y.Assert.isObject(dialogParentNode);
            Y.assert(dialogNode.ancestor('.yui3-panel-hidden'));
            this.dialog.show();
            Y.assert(!dialogNode.ancestor('.yui3-panel-hidden'));
            Y.Assert.areSame("Title", dialogNode.one('#rn_Dialog_' + dialogCounter + '_Title').getHTML());
            Y.Assert.areSame("Dialog", dialogNode.one('.yui3-widget-bd').one('*').getHTML());

            var buttonNodes = dialogNode.all('.yui3-widget-ft button');
            Y.Assert.areSame(1, buttonNodes.size());
            var html = buttonNodes.item(0).getHTML();
            Y.Assert.areSame('OK', html.substring(html.length - 2));
            Y.Assert.areSame('Title dialog, please read above text for dialog message ', buttonNodes.item(0).one('span.rn_ScreenReaderOnly').getHTML());
        },

        "Custom buttons and hide on enter key press": function() {
            var content = document.createElement("div"),
                customButtons = [{ text: "Yes", isDefault: true},
                                 { text: "No", isDefault: false}];
            content.innerHTML = "Dialog";
            this.dialog = RightNow.UI.Dialog.actionDialog("Title", content, {buttons: customButtons, hideOnEnterKeyPress: true});
            var dialogNode = Y.one('#rnDialog' + dialogCounter),
                dialogParentNode = dialogNode.get('parentNode');

            this.dialog.show();
            Y.Assert.isObject(dialogNode);
            Y.Assert.isObject(dialogParentNode);
            var buttonNodes = dialogNode.all('.yui3-widget-ft button');
            Y.Assert.areSame(2, buttonNodes.size());
            var html = buttonNodes.item(0).getHTML();
            Y.Assert.areSame('Yes', html.substring(html.length - 3));
            Y.Assert.areSame('Title dialog, please read above text for dialog message ', buttonNodes.item(0).one('span.rn_ScreenReaderOnly').getHTML());
            Y.Assert.areSame("No", buttonNodes.item(1).getHTML());

            dialogNode.simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(dialogNode.ancestor('.yui3-panel-hidden'));
        },

        "No close is honored": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('Title', Y.Node.create('<div>banana</div>'), { close: false });
            var dialogNode = Y.one('#rnDialog' + dialogCounter),
                button = dialogNode.one('.yui3-widget-hd button');
            Y.Assert.isNull(button);
        },

        "CSS class is properly applied": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('Title', Y.Node.create('<div>banana</div>'), { cssClass: 'BANANAS' });
            Y.assert(Y.one('#rnDialog' + dialogCounter).hasClass('BANANAS'));
        },

        "Width is properly applied": function() {
            var width = '100em';
            this.dialog = RightNow.UI.Dialog.actionDialog('Title', Y.Node.create('<div>banana</div>'), { width: width });
            Y.Assert.areSame(width, this.dialog.get('width'));
        },

        "dialogDescription is used for aria-describedby": function() {
            var description = Y.Node.create('<div>hey</div>').set('id', 'hey' + dialogCounter);
            Y.one(document.body).append(description);
            this.dialog = RightNow.UI.Dialog.actionDialog('Title', Y.Node.create('<div><input type="text" id="bananas"/></div>'), { dialogDescription: description.get('id') });

            Y.Assert.areSame('hey' + dialogCounter, Y.one('#rnDialog' + dialogCounter).getAttribute('aria-describedby'));
            Y.Assert.areSame('OK', this.dialog.getButtons().item(0).getHTML());
            description.remove();
        },

        "Null is returned when receiving inadequate parameters": function() {
            Y.Assert.isNull(RightNow.UI.Dialog.actionDialog());
            Y.Assert.isNull(RightNow.UI.Dialog.actionDialog("title"));
            Y.Assert.isNull(RightNow.UI.Dialog.actionDialog("title", {}));
            dialogCounter--; // Didn't create a dialog for this test.
        },

        "No screen reader with input element": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('Title', Y.Node.create('<div><input type="text" id="bananas"/></div>'), {buttons: [{text: 'bow'}, {text: 'ser'}]});

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            Y.Assert.areSame('bow', buttons.item(0).getHTML());
            Y.Assert.areSame('ser', buttons.item(1).getHTML());
        },

        "Input element with label and screen reader text": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('Input Form',
                Y.Node.create('<div><label for="bananas">Hi</label><input type="text" id="bananas"/></div>'),
                {buttons: [{text: 'bowser'}, {text: 'mario'}]}
            );

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            var firstLabel = this.dialog.getStdModNode('body').one('label[for="bananas"]');
            Y.Assert.isNotNull(firstLabel);
            if (Y.UA.ie)
                Y.Assert.areSame('Input Form dialog ', firstLabel.one('span.rn_ScreenReaderOnly').getHTML());
            else
                Y.Assert.isNull(firstLabel.one('span.rn_ScreenReaderOnly'));
            Y.Assert.isNull(buttons.item(0).one('span.rn_ScreenReaderOnly'));
            Y.Assert.isNull(buttons.item(1).one('span.rn_ScreenReaderOnly'));
        },

        "Input element with label and screen reader of header and footer dialog set correctly": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('Input Form',
                Y.Node.create('<div><label for="bananas">Hi</label><input type="text" id="bananas"/></div>'),
                {buttons: [{text: 'bowser'}, {text: 'mario'}]}
            );
            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            var headerLabel = this.dialog.getStdModNode('header').one('span.rn_ScreenReaderOnly').getHTML();
            Y.Assert.areSame("Beginning of dialog, please dismiss dialog before continuing", headerLabel);
            var footerLabel = this.dialog.getStdModNode('footer').one('span:last-child').getHTML();
            Y.Assert.areSame("End of dialog, please dismiss dialog before continuing", footerLabel);
        },

        "Multiple input elements with label and screen reader text": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('Multiple Input Form',
                Y.Node.create('<div><label for="bananas">Hi</label><input type="text" id="bananas"/><label for="bananas2">Hello</label><input type="text" id="bananas2"/></div>'),
                {buttons: [{text: 'bowser'}, {text: 'mario'}]}
            );

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            var firstLabel = this.dialog.getStdModNode('body').one('label[for="bananas"]');
            var secondLabel = this.dialog.getStdModNode('body').one('label[for="bananas2"]');
            Y.Assert.isNotNull(firstLabel);
            if (Y.UA.ie)
                Y.Assert.areSame('Multiple Input Form dialog ', firstLabel.one('span.rn_ScreenReaderOnly').getHTML());
            else
                Y.Assert.isNull(firstLabel.one('span.rn_ScreenReaderOnly'));
            Y.Assert.isNull(secondLabel.one('span.rn_ScreenReaderOnly'));
            Y.Assert.isNull(buttons.item(0).one('span.rn_ScreenReaderOnly'));
            Y.Assert.isNull(buttons.item(1).one('span.rn_ScreenReaderOnly'));
        },

        "Radio button element with legend and no dialog description": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('Radio Button Form',
                Y.Node.create('<div><legend><span>Question Text</span></legend><input type="radio" id="bananas"/><label for="bananas">Hi</label></div>'),
                {buttons: [{text: 'bowser'}, {text: 'mario'}]}
            );

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            var legend = this.dialog.getStdModNode('body').one('legend');
            var firstLabel = this.dialog.getStdModNode('body').one('label[for="bananas"]');
            Y.Assert.isNotNull(legend);
            Y.Assert.isNotNull(firstLabel);
            Y.Assert.isNull(legend.one('span.rn_ScreenReaderOnly'));
            if (Y.UA.ie)
                Y.Assert.areSame("Radio Button Form dialog ", firstLabel.one('span.rn_ScreenReaderOnly').getHTML());
            else
                Y.Assert.isNull(firstLabel.one('span.rn_ScreenReaderOnly'));
            Y.Assert.isNull(buttons.item(0).one('span.rn_ScreenReaderOnly'));
            Y.Assert.isNull(buttons.item(1).one('span.rn_ScreenReaderOnly'));
        },

        "Select element with label and screen reader text": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('Select Form',
                Y.Node.create('<div><label for="bananas">Hi</label><select id="bananas"><option value="99"/></select></div>'),
                {buttons: [{text: 'bowser'}, {text: 'mario'}]}
            );

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            var firstLabel = this.dialog.getStdModNode('body').one('label[for="bananas"]');
            Y.Assert.isNotNull(firstLabel);
            if (Y.UA.ie)
                Y.Assert.areSame('Select Form dialog ', firstLabel.one('span.rn_ScreenReaderOnly').getHTML());
            else
                Y.Assert.isNull(firstLabel.one('span.rn_ScreenReaderOnly'));
            Y.Assert.isNull(buttons.item(0).one('span.rn_ScreenReaderOnly'));
            Y.Assert.isNull(buttons.item(1).one('span.rn_ScreenReaderOnly'));
        },

        "Screen reader hint with no dialogDescription": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'), {buttons: [{text: 'bow'}, {text: 'ser'}]});

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            var html = buttons.item(0).getHTML();
            Y.Assert.areSame('bow', html.substring(html.length - 3));
            Y.Assert.areSame('title dialog, please read above text for dialog message ', buttons.item(0).one('span.rn_ScreenReaderOnly').getHTML());
            Y.Assert.areSame('ser', buttons.item(1).getHTML());
        },

        "Screen reader hint use dialogDescription": function() {
            var description = Y.Node.create('<div>hey</div>').set('id', 'hey' + dialogCounter);
            Y.one(document.body).append(description);

            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'), {dialogDescription: description.get('id'), buttons: [{text: 'bow'}, {text: 'ser'}]});

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            var html = buttons.item(0).getHTML();
            Y.Assert.areSame('bow', html.substring(html.length - 3));
            Y.Assert.areSame('title dialog. hey ', buttons.item(0).one('span.rn_ScreenReaderOnly').getHTML());
            Y.Assert.areSame('ser', buttons.item(1).getHTML());

            description.remove();
        },

        "Screen reader hint use dialogDescription with second button as default button": function() {
            var description = Y.Node.create('<div>hey</div>').set('id', 'hey' + dialogCounter);
            Y.one(document.body).append(description);

            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'), {dialogDescription: description.get('id'), buttons: [{text: 'bow'}, {text: 'ser', isDefault: true}]});

            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            Y.Assert.areSame('bow', buttons.item(0).getHTML());
            Y.Assert.isNull(buttons.item(0).one('span.rn_ScreenReaderOnly'));
            Y.Assert.isTrue(buttons.item(1).getHTML().indexOf("ser") > 0);
            Y.Assert.isNotNull(buttons.item(1).one('span.rn_ScreenReaderOnly'));

            description.remove();
        },

        "By default, #addDialogEnterKeyListener fires when ENTER is hit on any element in the dialog": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', Y.Node.create('<div id="bananas' + dialogCounter + '" tabindex="0"><input type="text"><a href="javascript:void(0);">clickme</a></div>'));
            var calledFor,
                callback = function(evt, args) {
                    calledFor = args[1];
                },
                hitEnter = function(selector) {
                    Y.one(selector).focus().simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
                };
            RightNow.UI.Dialog.addDialogEnterKeyListener(this.dialog, callback);

            this.dialog.show();

            hitEnter('#rnDialog' + dialogCounter);
            Y.Assert.areSame('rnDialog' + dialogCounter, calledFor.target.get('id'));

            hitEnter('#bananas' + dialogCounter);
            Y.Assert.areSame('bananas' + dialogCounter, calledFor.target.get('id'));

            hitEnter('#bananas' + dialogCounter + ' input');
            Y.Assert.areSame('INPUT', calledFor.target.get('tagName'));

            hitEnter('#bananas' + dialogCounter + ' a');
            Y.Assert.areSame('A', calledFor.target.get('tagName'));
        },

        "But #addDialogEnterKeyListener doesn't fire when ENTER is hit on any button element in the dialog": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', Y.Node.create('<button>the sea of your</button>'));

            var called = false,
                callback = function() {
                    called = true;
                };

            RightNow.UI.Dialog.addDialogEnterKeyListener(this.dialog, callback);

            Y.one('#rnDialog' + dialogCounter + ' button').simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(!called);

            RightNow.UI.Dialog.addDialogEnterKeyListener(this.dialog, callback, null, 'button');

            Y.one('#rnDialog' + dialogCounter + ' button').simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(!called);
        },

        "Selector passed to #addDialogEnterKeyListener is honored": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', Y.Node.create('<a href="javascript:void(0)">lovesick teenagers</a><input type="text">'));

            var called = false,
                callback = function() {
                    called = true;
                };

            RightNow.UI.Dialog.addDialogEnterKeyListener(this.dialog, callback, null, 'input');

            Y.one('#rnDialog' + dialogCounter + ' a').simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(!called);

            Y.one('#rnDialog' + dialogCounter + ' input').simulate('keydown', { keyCode: RightNow.UI.KeyMap.ENTER });
            Y.assert(called);
        },

        "A panel inside a dialog is correctly masked on hide": function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', Y.Node.create('<div class="testPanel"></div>'));
            var panel = new Y.Panel({
                srcNode: this.dialog.get('boundingBox').one('.testPanel'),
                visible: false,
                render: this.dialog.get('boundingBox')
            });

            this.dialog.show();
            panel.show();
            panel.hide();
            Y.assert(!Y.one('.yui3-widget-mask').get('hidden'));
            this.dialog.destroy();
        }
    }));

    suite.add(new Y.Test.Case({
        name: 'Backwards compatibility',
        tearDown: tearDown,

        'Dialogs have an id property': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'));
            Y.assert(this.dialog.id);
            Y.assert(/rnDialog(\d)+/.test(this.dialog.id));
            this.dialog.destroy();
        },

        'Getting props via `cfg` passes thru to the Panel getter': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'), { width: 300 });
            Y.Assert.areSame(300, this.dialog.cfg.getProperty('width'));
            Y.assert(this.dialog.cfg.getProperty('zIndex'));
        },

        'Setting props via `cfg` passes thru to the Panel setter': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'));
            this.dialog.cfg.setProperty('width', 300);
            Y.Assert.areSame(300, this.dialog.get('width'));
        },

        '#setFooter sets the content of the footer': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'));
            var foot = '<div>bananas</div>';
            this.dialog.setFooter(foot);
            Y.Assert.areSame(0, this.dialog.getStdModNode('footer').getHTML().toLowerCase().indexOf(foot));
        },

        '#setHeader sets the content of the header': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'));
            var head = '<div>bananas</div>';
            this.dialog.setHeader(head);
            Y.Assert.areSame(0, this.dialog.getStdModNode('header').getHTML().toLowerCase().indexOf(head));
        },

        '#setBody sets the content of the body': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'));
            var body = '<div>bananas</div>';
            this.dialog.setBody(body);
            Y.Assert.areSame(0, this.dialog.getStdModNode('body').getHTML().toLowerCase().indexOf(body));
        },

        'Buttons can be retrieved': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'), {buttons: [{text: 'bow'}, {text: 'ser'}]});
            var buttons = this.dialog.getButtons();
            Y.Assert.areSame(2, buttons.size());
            var html = buttons.item(0).getHTML();
            Y.Assert.areSame('bow', html.substring(html.length - 3));
            Y.Assert.areSame('title dialog, please read above text for dialog message ', buttons.item(0).one('span.rn_ScreenReaderOnly').getHTML());
            Y.Assert.areSame('ser', buttons.item(1).getHTML());
        },

        'Buttons can be disabled and enabled': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'), { close: false });
            this.dialog.disableButtons();
            var buttons = this.dialog.getButtons();
            Y.Assert.areSame('true', buttons.get('disabled') + '');
            this.dialog.enableButtons();
            Y.Assert.areSame('false', buttons.get('disabled') + '');
        },

        'Second buttons can be enabled': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'), { close: false, buttons: [{text: 'bow'}, {text: 'ser'}] });
            this.dialog.disableButtons();
            var buttons = this.dialog.getButtons();
            Y.Assert.areSame('true,true', buttons.get('disabled') + '');
            this.dialog.enableSecondButton();
            Y.Assert.areSame('true,false', buttons.get('disabled') + '');
        },

        'cancel handler is called': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'));
            var callbackArg;
            this.dialog.cancelEvent.subscribe(function(arg) {
                callbackArg = arg;
            }, 'hey');
            this.dialog.show();
            Y.one('#' + this.dialog.getButton('close').get('id')).simulate('click');
            Y.Assert.areSame('hey', callbackArg);
        },

        'show handler is called': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'));
            var callbackArg;
            this.dialog.showEvent.subscribe(function(arg) {
                callbackArg = arg;
            }, 'hey');
            this.dialog.show();
            Y.Assert.areSame('hey', callbackArg);
        },

        'hide handler is called': function() {
            this.dialog = RightNow.UI.Dialog.actionDialog('title', document.createElement('div'));
            var callbackArg;
            this.dialog.hideEvent.subscribe(function(arg) {
                callbackArg = arg;
            }, 'hey');
            this.dialog.show();
            Y.assert(!callbackArg);
            this.dialog.hide();
            Y.Assert.areSame('hey', callbackArg);
        }
    }));

    suite.add(new Y.Test.Case(
    {
        name : "privateMembersHiddenTest",

        "Private members are private": function() {
            UnitTest.recursiveMemberCheck(Y, RightNow.UI.Dialog);
        }
    }));

    return suite;
});
UnitTest.run();