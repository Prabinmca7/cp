UnitTest.addSuite({
    type: UnitTest.Type.Widget,
    instanceID: 'LogoutLink_0',
}, function(Y, widget, baseSelector) {
    var logoutLinkTests = new Y.Test.Suite({
        name: "standard/login/LogoutLink",
    });
        
    logoutLinkTests.add(new Y.Test.Case(
    {
        name: "Event Handling and Operation",
        
        testLogoutLink: function() {
            var logoutLink = Y.one(baseSelector + '_LogoutLink'),
                resume = this.resume;
            
            RightNow.Url.navigate = function(url) {
                resume(function() {
                    Y.Assert.areSame((widget.data.attrs.redirect_url || window.location.pathname) + '/session/abc', url);
                });
            };
            RightNow.Event.subscribe("evt_logoutResponse", function(type, args){
                args[0].response.session = '/session/abc';
            }, this);
            Y.one(logoutLink).simulate('click');
            this.wait();
        }
    }));
    return logoutLinkTests;
});
UnitTest.run();
