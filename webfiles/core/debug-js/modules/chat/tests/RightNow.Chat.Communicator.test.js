if (!Function.prototype.bind) {
    Function.prototype.bind = function(oThis) {
        if (typeof this !== 'function') {
            // closest thing possible to the ECMAScript 5
            // internal IsCallable function
            throw new TypeError('Function.prototype.bind - what is trying to be bound is not callable');
        }
        var aArgs   = Array.prototype.slice.call(arguments, 1),
            fToBind = this,
            fNOP    = function() {},
            fBound  = function() {
                return fToBind.apply(this instanceof fNOP
                    ? this
                    : oThis,
                    aArgs.concat(Array.prototype.slice.call(arguments)));
            };

        if (this.prototype) {
            // Function.prototype doesn't have a prototype property
            fNOP.prototype = this.prototype;
        }
        fBound.prototype = new fNOP();

        return fBound;
    };
}
UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: [
        '/euf/core/debug-js/RightNow.Event.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.Communicator.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.Controller.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.Model.js',
        '/euf/core/debug-js/modules/chat/RightNow.Chat.UI.js',
        '/euf/core/debug-js/RightNow.Text.js'
    ],
    namespaces: [
        'RightNow.Event',
        'RightNow.Chat.Communicator',
        'RightNow.Chat.Controller.ChatCommunicationsController',
        'RightNow.Chat.UI',
        'RightNow.Chat.Model',
        'RightNow.Chat.Controller'
    ]
}, function(Y) {
    var rightnowChatCommunicatorTests = new Y.Test.Suite("RightNow.Chat.Communicator");

    rightnowChatCommunicatorTests.add(new Y.Test.Case(
    {
        name: 'CommunicatorFunctions',
        // @@@ 161024-000154 Check whether https is taken into account when set...
        testInitializeHttps: function()
        {
           var connectionData = 
           {
              useHttps: true,
              chatServerHost: "some.chat.host",
              chatServerPort: "8084",
              dbName: "dbname"
           };
           RightNow.Chat.Communicator.initialize(connectionData);
           Y.Assert.areSame("https://some.chat.host:8084/Chat/chat/dbname", RightNow.Chat.Communicator._baseUrl);
        }
    }));
    return rightnowChatCommunicatorTests;
}).run();
