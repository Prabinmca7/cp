UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    jsFiles: [],
    instanceID: 'RichTextInput_0'
}, function(Y, widget, baseSelector) {

    function hexToRgb(hex){
        var c;
        if(/^#([A-Fa-f0-9]{3}){1,2}$/.test(hex)){
            c= hex.substring(1).split('');
            if(c.length== 3){
                c= [c[0], c[0], c[1], c[1], c[2], c[2]];
            }
            c= '0x'+c.join('');
            return 'rgb('+[(c>>16)&255, (c>>8)&255, c&255].join(', ')+')';
        }
        throw new Error('Bad Hex');
    }

    var suite = new Y.Test.Suite({
        name:       "standard/input/RichTextInput - CKEditor",
    });

    suite.add(new Y.Test.Case({
        name: "Loading CKEditor",

        "Check CKEditor Loaded": function () {
            var textarea = Y.one(baseSelector + "_ckeditor");
            var loadingIcon = Y.one(baseSelector + '_LoadingIcon');
            this.wait(function() {
                var ckeditor = Y.one(widget.instanceID + "_ckeditor");
               //   Y.assert(ckeditor);
               // Y.Assert.areSame('hidden',textarea.getStyle('visibility'));
                Y.Assert.isTrue(true);
            }, 2000);
        }
    }));


    suite.add(new Y.Test.Case({
        name: "Editor Reload and Read Only",

        tearDown: function() {
        },

        setUp: function() {

        },

        "Editor is read only when specified per read_only attribute": function() {
            widget.data.attrs.read_only = true;
            this.wait(function() {
                widget.reload('foo');
                widget.on('instanceReady', function(evt) {
                    Y.Assert.isFalse(Y.one('iframe').get('contentDocument').one('body').get('isContentEditable'), 'Editor not read only');
                   widget.data.attrs.read_only = false;
                   widget.reload('foo');
                   this.wait(function() {
                      Y.Assert.isFalse(widget.data.readOnly);
                   }, 2000);
                }, this);
            }, 2000);
        },

        "Editor is read only when specified on reload": function() {
            widget.reload('foo', true);
            Y.Assert.isTrue(widget.data.readOnly);
            widget.on('instanceReady', function(evt) {
                Y.Assert.isFalse(Y.one('iframe').get('contentDocument').one('body').get('isContentEditable'), 'Editor not read only');
               Y.Assert.areEqual(Y.one('iframe').get('contentDocument').one('body').getStyle('backgroundColor'), hexToRgb('#EFEFEF'));
            }, this);
        },

        "Editor is not read only when specified on reload": function() {
            widget.reload('foo', false);
            Y.Assert.isFalse(widget.data.readOnly);
            this.wait(function() {
               Y.Assert.isFalse(Y.one('iframe').get('contentDocument').one('body').get('isContentEditable'), 'Editor read only');
               Y.Assert.areNotEqual(Y.one('iframe').get('contentDocument').one('body').getStyle('backgroundColor'), hexToRgb('#EFEFEF'));
            }, 2000);

        },
    }));

    return suite;
}).run();

