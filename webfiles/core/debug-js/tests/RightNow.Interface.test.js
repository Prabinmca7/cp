UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    namespaces: ['RightNow.Interface']
}, function(Y){
    var rightnowInterfaceTests = new Y.Test.Suite("RightNow.Interface");

    rightnowInterfaceTests.add(new Y.Test.Case(
    {
        name : "messagebaseTests",

        setUp: function()
        {
            RightNow.Interface.setMessagebase(function(){return {"HOME_LBL":"Home", "FEATURED_SUPPORT_CATEGORIES_LBL":"Featured Support Categories","HELP_LBL":"Help"};});
        },

        testGetMessage: function()
        {
            Y.Assert.areSame(RightNow.Interface.getMessage("HOME_LBL"), "Home");
            Y.Assert.areSame(RightNow.Interface.getMessage("FEATURED_SUPPORT_CATEGORIES_LBL"), "Featured Support Categories");
            Y.Assert.areSame(RightNow.Interface.getMessage("HELP_LBL"), "Help");
            Y.Assert.areSame(RightNow.Interface.getMessage("SEE_ALL_POPULAR_ANSWERS_UC_LBL"), "");
        },

        testASTRGetMesssage: function()
        {
            var astrFunction = 'ASTRgetMessage';
            Y.Assert.areSame(RightNow.Interface[astrFunction]("foo"), "foo");
        },

        testMsg: function()
        {
            Y.Assert.areSame(RightNow.Interface.msg('Home'), 'Home');
            Y.Assert.areSame(RightNow.Interface.msg('Home', 'HOME_LBL'), 'Home');
            Y.Assert.areSame(RightNow.Interface.msg('Home', 'SOME_DEFINE_THAT_DOES_NOT_EXIST'), 'Home');
        }
    }));

    rightnowInterfaceTests.add(new Y.Test.Case(
    {
        name : "configbaseTests",

        setUp: function()
        {
            RightNow.Interface.setConfigbase(function(){return {"CP_HOME_URL":"home", "CP_DEPRECATED_CORE_ENABLED":0, "CP_FILE_UPLOAD_MAX_TIME":300};});
        },

        testGetMessage: function()
        {
            Y.Assert.areSame(RightNow.Interface.getConfig("CP_HOME_URL"), "home");
            Y.Assert.areSame(RightNow.Interface.getConfig("CP_DEPRECATED_CORE_ENABLED"), 0);
            Y.Assert.areSame(RightNow.Interface.getConfig("CP_FILE_UPLOAD_MAX_TIME"), 300);
            Y.Assert.areSame(RightNow.Interface.getConfig("WIDX_MODE"), "");
        }
    }));

    rightnowInterfaceTests.add(new Y.Test.Case(
    {
        name : "constantsTests",

        testConstants: function()
        {
            Y.Assert.isObject(RightNow.Interface.Constants);
        }
    }));

    rightnowInterfaceTests.add(new Y.Test.Case(
    {
        name : "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, RightNow.Interface);
        }
    }));

    return rightnowInterfaceTests;
});
UnitTest.run();
