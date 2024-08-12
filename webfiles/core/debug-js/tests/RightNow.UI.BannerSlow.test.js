UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.UI.js'],
    namespaces: ['RightNow.UI'],
    preloadFiles: ['/euf/core/debug-js/RightNow.Text.js']
}, function(Y){
    var rightnowUITests = new Y.Test.Suite("RightNow.UI banner slow");

    rightnowUITests.add(new Y.Test.Case({
        name: "Banner",

        setUp: function() {
            this.parent = Y.one(document.body);
        },

        tearDown: function() {
            Y.one(document.body).all('.rn_BannerAlert').remove();
            this.parent = null;
        },

        "Close button that closes when clicked": function() {
            RightNow.UI.displayBanner('bananas', { close: true });

            Y.assert(this.parent.one('> .rn_BannerAlert a.rn_CloseLink'));
            this.parent.one('> .rn_BannerAlert a.rn_CloseLink').simulate('click');
            this.wait(function() {
                Y.assert(!Y.one(document.body).all('.rn_BannerAlert').size());
            }, 6600 /* close timeout + transition duration + 5k */);
        },

        "Default: Auto-closes after a timeout": function() {
            RightNow.UI.displayBanner('bananas');
            Y.assert(!this.parent.one('a.rn_CloseLink'));
            this.wait(function() {
                Y.assert(!this.parent.all('.rn_BannerAlert').size());
            }, 6600 /* close timeout + transition duration + 5k */);
        },

        "Auto-closes after a specified timeout": function() {
            RightNow.UI.displayBanner('bananas', { close: 1000 });
            this.wait(function() {
                Y.assert(!this.parent.all('.rn_BannerAlert').size());
            }, 6600 /* close timeout + transition duration + 5k */);
        }
    }));

    return rightnowUITests;
});
UnitTest.run();
