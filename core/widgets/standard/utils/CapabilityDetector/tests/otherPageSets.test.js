window._testCapabilities = {};
UnitTest.addSuite({
    type: UnitTest.Type.WidgetNoJS,
    namespaces: ['RightNow.Text']
}, function(Y) {
    var capabilityDetectorTests = new Y.Test.Suite("standard/utils/CapabilityDetector");

    capabilityDetectorTests.add(new Y.Test.Case({
        name: "Failure Functionality",

        "Verify that message is displayed when XHR is not found": function() {
            var instanceID = 'rn_CapabilityDetector_0',
                localXMLHttpRequest = window.XMLHttpRequest;

            Y.one('#' + instanceID + '_MessageContainer').set('innerHTML', '');

            window.XMLHttpRequest = null;
            Y.Assert.isTrue(RightNowTesting._xhrTestFails());
            RightNowTesting._runJSTests();
            Y.Assert.isTrue(RightNow.Text.beginsWith(Y.Lang.trim(Y.one('#' + instanceID + '_MessageContainer').getHTML()), 'Your browser will not offer you the best experience in this format'));
            Y.Assert.isTrue(Y.Lang.trim(Y.one('#' + instanceID + '_MessageContainer').getHTML()).indexOf('ci/redirect/pageSet/basic/unitTest/rendering') > -1);

            window.XMLHttpRequest = localXMLHttpRequest;
        },

        "Verify that success message is displayed when XHR is found": function() {
            var instanceID = 'rn_CapabilityDetector_0',
                localXMLHttpRequest = window.XMLHttpRequest;

            Y.one('#' + instanceID + '_MessageContainer').set('innerHTML', '');

            Y.Assert.isFalse(RightNowTesting._xhrTestFails());
            RightNowTesting._runJSTests();
            Y.Assert.isTrue(RightNow.Text.beginsWith(Y.Lang.trim(Y.one('#' + instanceID + '_MessageContainer').getHTML()), 'Your browser will offer you a better experience with a different format'));
            Y.Assert.isTrue(Y.Lang.trim(Y.one('#' + instanceID + '_MessageContainer').getHTML()).indexOf('ci/redirect/pageSet/mobile/unitTest/rendering') > -1);
        }
    }));
    return capabilityDetectorTests;
});
UnitTest.run();
