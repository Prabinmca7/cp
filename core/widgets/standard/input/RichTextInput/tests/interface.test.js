UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    jsFiles: [
	'/euf/core/thirdParty/js/ORTL/ortl.js'],
    instanceID: 'RichTextInput_0'
}, function(Y, widget, baseSelector) {
    function setContent (content) {
        Y.one('.rn_RichTextInput iframe').get('contentDocument').one('body').setHTML(content);
    }

    function getContent () {
        return Y.one('.rn_RichTextInput iframe').get('contentDocument').one('body').getHTML();
    }

    var suite = new Y.Test.Suite({
        name:       "standard/input/RichTextInput - Public Interface",
        tearDown:   function () { widget.reload(); }
    });

    suite.add(new Y.Test.Case({
        name: "Moving the widget and injecting new content",

        setUp: function () {
            setContent('bananas');
        },

        moveWidget: function () {
            var widgetEl = Y.one(baseSelector);
            this._parent = widgetEl.get('parentNode');
            Y.one(document.body).append(this._parent);
        },

        replaceWidget: function () {
            this._parent.insert(Y.one(baseSelector), 0);
        },

        submit: function() {
            return widget.onValidate('blah', [ { data: { error_location: 'bananas' } } ]);
        },

        "Current content is blanked out": function () {
            // min length needs to be 0 (e.g. disabled)
            widget.data.js.constraints.minLength = 0;
            widget.reload();
            this.wait( function() {
                var result = this.submit();
                Y.Assert.areSame('', Y.Lang.trim(result.data.value));
            }, 2000);
        },

        "Current content is replaced by new HTML content": function () {
            var input = 'somebody sweet <strong>to talk to</strong>';
            widget.reload(input);
            this.wait( function() {
                Y.Assert.areSame('<p>' + input + '</p>', getContent());
            }, 3000);
        },

        "Able to move the widget thru the dom": function () {
            this.moveWidget();

            widget.reload('snow queen');
            this.wait( function() {
                Y.Assert.areSame('<p>snow queen</p>', getContent());
            }, 3000);

            this.replaceWidget();
        },

        "Reloaded editor still has autogrow behavior": function () {
            // Skip phantomjs and early version of IE since they
            // don't support 'scrollHeight'
            // Skip firefox since it seems to consistently fail in CC
            // Just use chrome!
            if (Y.UA.chrome) {
                function height () {
                    return parseInt(Y.one(baseSelector).getComputedStyle('height'), 10);
                }

                this.moveWidget();

                var originalHeight = height();
                widget.reload("<br><br><br><br><br><br><br><br><br><br><br><br><br><br><br>Shallows<br>Durian<br>");
                this.wait( function() {
                    // Should jump from something like ~100px to ~200px
                    Y.Assert.isTrue(height() - originalHeight > 100);
                }, 1000);

                this.replaceWidget();
            }
        },

        "Reloaded editor has its error indicators removed": function () {
            this.wait( function() {
                Y.one(baseSelector + '_Label').addClass('rn_ErrorLabel');
                Y.one(baseSelector + '_Editor').one('iframe').addClass('rn_ErrorField');
            }, 2000);


            widget.reload();

            this.wait( function() {
                Y.assert(!Y.one(baseSelector + '_Label').hasClass('rn_ErrorLabel'));
                Y.assert(!Y.one(baseSelector + '_Editor').one('iframe').hasClass('rn_ErrorField'));
            }, 3000);
        }
    }));

    suite.add(new Y.Test.Case({
        name: "#getValue",

        "Returns HTML": function () {
            // Defaults to markdown
            widget.reload('<strong>Find it of use</strong>');
            this.wait( function() {
                var result = widget.getValue();
                Y.Assert.areSame("<p><strong>Find it of use</strong></p>", Y.Lang.trim(result.text));
            }, 5000);
        },
    }));

    suite.add(new Y.Test.Case({
        name: "#setLabel",

        tearDown: function() {
            widget.setLabel(widget.data.attrs.label_input);
            widget.updatedLabel = undefined;
        },

        "The label is set": function() {
            widget.setLabel('bananas label');
            Y.Assert.areSame('bananas label', Y.Lang.trim(Y.one(baseSelector + ' label .rn_LabelInput').get('text')));
        },

        "The label cannot be falsey": function() {
            var label = Y.one(baseSelector + '_Label'),
                labelText = label.get('text');

            widget.setLabel(0);
            Y.Assert.areSame(labelText, label.get('text'));
            widget.setLabel('');
            Y.Assert.areSame(labelText, label.get('text'));
            widget.setLabel(false);
            Y.Assert.areSame(labelText, label.get('text'));
            widget.setLabel(null);
            Y.Assert.areSame(labelText, label.get('text'));
            widget.setLabel(undefined);
            Y.Assert.areSame(labelText, label.get('text'));
        }
    }));

    suite.add(new Y.Test.Case({
        name: "#getLabel",

        tearDown: function() {
            widget.setLabel(widget.data.attrs.label_input);
            widget.updatedLabel = undefined;
        },

        "The default attribute label is returned": function() {
            Y.Assert.areSame(widget.data.attrs.label_input, widget.getLabel());
        },

        "The set label is returned": function() {
            var label = 'avenue';
            widget.setLabel(label);
            Y.Assert.areSame(label, Y.Lang.trim(widget.getLabel()));
        }

    }));

    return suite;
});
setTimeout(function(){UnitTest.run();}, 3000);
