UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    jsFiles: ['/euf/core/debug-js/RightNow.UI.AbuseDetection.js']
}, function(Y){
    var rightnowUIAbuseDetectionTests = new Y.Test.Suite({
        name: "RightNow.UI.AbuseDetection",

        setUp: function()
        {
            RightNow.UI = RightNow.UI || {};
            var testExtender = {
                AD: RightNow.UI.AbuseDetection
            };
            for(var item in this.items){
                Y.mix(this.items[item], testExtender);
            }
        }
    });

    rightnowUIAbuseDetectionTests.add(new Y.Test.Case(
    {
        name : "miscPublicMembers",

        testMiscPublicMembers: function()
        {
            Y.Assert.isObject(this.AD.options);
            Y.Assert.isFunction(this.AD.Default);
            Y.Assert.isFalse(this.AD.isRetry());
        }
    }));

    rightnowUIAbuseDetectionTests.add(new Y.Test.Case(
    {
        name : "miscPublicFunctions",
        
        _should:{
            error:{
                testGetChallengeProviderParameterFailureOne: true,
                testGetChallengeProviderParameterFailureTwo: true,
                testGetChallengeProviderEvalFailure: true
            }
        },
        
        testGetChallengeProvider: function()
        {
            this.AD.getChallengeProvider({challengeProvider: 'window.foo = "bar";'});
            Y.Assert.areSame(foo, "bar");
        },
        
        testGetChallengeProviderParameterFailureOne: function()
        {
            this.AD.getChallengeProvider();
        },
        
        testGetChallengeProviderParameterFailureTwo: function()
        {
            this.AD.getChallengeProvider({foo: 'bar'});
        },
        
        testGetChallengeProviderEvalFailure: function()
        {
            this.AD.getChallengeProvider({challengeProvider: 'asdf'});
        },

        testGetDialogCaption: function()
        {
            Y.Assert.areSame(this.AD.getDialogCaption(), "");
            Y.Assert.areSame(this.AD.getDialogCaption(false), "");
            Y.Assert.areSame(this.AD.getDialogCaption({}), undefined);
            Y.Assert.areSame(this.AD.getDialogCaption({dialogCaption: "foo"}), "foo");
        },
        
        testDoesResponseIndicateAbuse: function()
        {
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse());
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse(false));
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse(null));
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse("asdf"));
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse({}));
            
            var responseObject = {responseText: "asdf"};
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse(responseObject));
            responseObject = {responseText: "CHALLENGE REQUIRED"};
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse(responseObject));
            responseObject = {responseText: "CHALLENGE INCORRECT"};
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse(responseObject));
            responseObject = {responseText: "=CHALLENGE REQUIRED="};
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse(responseObject));
            responseObject = {responseText: "=CHALLENGE INCORRECT="};
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse(responseObject));
            responseObject = {responseText: "a==CHALLENGE REQUIRED=="};
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse(responseObject));
            responseObject = {responseText: "b==CHALLENGE INCORRECT=="};
            Y.Assert.isFalse(this.AD.doesResponseIndicateAbuse(responseObject));
            
            responseObject = {responseText: "==CHALLENGE INCORRECT=="};
            Y.Assert.isTrue(this.AD.doesResponseIndicateAbuse(responseObject));
            Y.Assert.isTrue(this.AD.isRetry());
            responseObject = {responseText: "==CHALLENGE REQUIRED=="};
            Y.Assert.isTrue(this.AD.doesResponseIndicateAbuse(responseObject));
            Y.Assert.isFalse(this.AD.isRetry());
        }
    }));

    rightnowUIAbuseDetectionTests.add(new Y.Test.Case(
    {
        name : "abuseDetectionDefaultTests",
        
        setUp: function()
        {
            RightNow.Event = RightNow.Event || 
                             {createDelegate: function(context, callback) {
                                 return function() {
                                     return callback.apply(context, arguments);
                                 };
                             }};
            this.Default = new this.AD.Default();
        },
        
        testPublicMembers: function()
        {
            Y.Assert.areSame(this.Default._abuse, this.AD);
            Y.Assert.areSame(this.Default._abuseChallengeDivID, "rn_DefaultAbuseChallengeDiv");
            Y.Assert.areSame(this.Default._dialog, null);
            Y.Assert.areSame(this.Default._challengeProvider, null);
            Y.Assert.areSame(this.Default._requestObject, null);
        }
    }));

    rightnowUIAbuseDetectionTests.add(new Y.Test.Case(
    {
        name : "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, RightNow.UI.AbuseDetection);
        }
    }));

    return rightnowUIAbuseDetectionTests;
});
UnitTest.run();
