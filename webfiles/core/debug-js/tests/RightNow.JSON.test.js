UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    namespaces: ['RightNow.JSON']
}, function(Y){
    var rightnowJSONTests = new Y.Test.Suite("RightNow.JSON");

    rightnowJSONTests.add(new Y.Test.Case(
    {
        name: "JSONFunctions",
        
        _should: {
            error: {
                testParseError: true
            }
        },
        
        testParse: function() 
        {
            Y.Assert.areSame(RightNow.JSON.parse("\"foo\""), "foo");
            Y.Assert.areSame(RightNow.JSON.parse("1"), 1);
            
            var arrayParse = RightNow.JSON.parse("[\"one\",\"two\",3]");
            Y.Assert.isArray(arrayParse);
            Y.Assert.areSame(arrayParse[0], "one");
            Y.Assert.areSame(arrayParse[1], "two");
            Y.Assert.areSame(arrayParse[2], 3);
            
            var objectParse = RightNow.JSON.parse("{\"one\":1,\"two\":\"two\",\"three\":{\"four\":\"five\"}}");
            Y.Assert.isObject(objectParse);
            Y.Assert.isObject(objectParse.three);
            Y.Assert.areSame(objectParse.one, 1);
            Y.Assert.areSame(objectParse.two, "two");
            Y.Assert.areSame(objectParse.three.four, "five");
        },
        
        testParseError: function()
        {
            RightNow.JSON.parse("aseg{][{[");
        },
        
        testStringify: function() 
        {
            Y.Assert.areSame(RightNow.JSON.stringify("foo"), "\"foo\"");
            Y.Assert.areSame(RightNow.JSON.stringify(1), "1");
            Y.Assert.areSame(RightNow.JSON.stringify(["one", "two", 3]), "[\"one\",\"two\",3]");
            Y.Assert.areSame(RightNow.JSON.stringify({a: 1, b: "two", c:{four:"five"}}), '{"a":1,"b":"two","c":{"four":"five"}}');
        }
    }));

    rightnowJSONTests.add(new Y.Test.Case(
    {
        name : "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, RightNow.JSON);
        }
    }));
    return rightnowJSONTests;
});
UnitTest.run();
