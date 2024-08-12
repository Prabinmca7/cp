UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'OkcsProductCategoryImageDisplay_0'
}, function(Y, widget, baseSelector){
    var suite = new Y.Test.Suite({
        name: "standard/okcs/OkcsProductCategoryImageDisplay"
    });

    suite.add(new Y.Test.Case({
        name: "Image Loading",

        "Image is loaded successfully": function() {
            widget.data.js.slug = 'android';

            var img = Y.one(baseSelector + ' img');
            widget._loadImage(img);

            this.wait(function() {
                Y.Assert.areSame(widget.data.attrs.image_path + '/android.png', img.getAttribute('src'));
                Y.Assert.areSame('Image of android', img.getAttribute('alt'));
            }, 1000);
        },

        "Fallback image is loaded on error": function() {
            widget.data.js.slug = 'walrus-shoes';

            var img = Y.one(baseSelector + ' img');
            widget._loadImage(img);

            this.wait(function() {
                Y.Assert.areSame(widget.data.attrs.image_path + '/default.png', img.getAttribute('src'));
                Y.Assert.areSame('Default image', img.getAttribute('alt'));
            }, 1000);
        }
    }));

    return suite;
}).run();
