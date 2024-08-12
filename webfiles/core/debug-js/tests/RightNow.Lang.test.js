UnitTest.addSuite({
    type: UnitTest.Type.Framework,
    namespaces: ['RightNow.Lang']
}, function(Y){
    var rightnowLangTests = new Y.Test.Suite("RightNow.Lang");
    
    rightnowLangTests.add(new Y.Test.Case(
    {       
        name : "arrayFilterTests",
        
        _should:{
            error:{
                testNonFunctionCallback: true
            }
        },

        testStringFilter: function()
        {
            var onlyStrings = function(item){return typeof item === "string";};
            var originalArray = [null, 0, "one", "", "0", false, "three"];
            var filteredArray = RightNow.Lang.arrayFilter(originalArray, onlyStrings);
            Y.Assert.areSame(filteredArray[0], undefined);
            Y.Assert.areSame(filteredArray[1], undefined);
            Y.Assert.areSame(filteredArray[2], "one");
            Y.Assert.areSame(filteredArray[3], "");
            Y.Assert.areSame(filteredArray[4], "0");
            Y.Assert.areSame(filteredArray[5], undefined);
            Y.Assert.areSame(filteredArray[6], "three");
        },
        
        testEmptyCallback: function()
        {
            var originalArray = [null, 0, "one", "", "0", false, true];
            var filteredArray = RightNow.Lang.arrayFilter(originalArray);
            Y.Assert.areSame(filteredArray[0], undefined);
            Y.Assert.areSame(filteredArray[1], undefined);
            Y.Assert.areSame(filteredArray[2], "one");
            Y.Assert.areSame(filteredArray[3], undefined);
            Y.Assert.areSame(filteredArray[4], undefined);
            Y.Assert.areSame(filteredArray[5], undefined);
            Y.Assert.areSame(filteredArray[6], true);
        },
        
        testNonFunctionCallback: function()
        {
            RightNow.Lang.arrayFilter([], true);
        }
    }));

    rightnowLangTests.add(new Y.Test.Case(
    {
        name : "cloneObjectTests",

        testSimpleClone: function()
        {
            var object = {one: "one", two: "two", three:"3", four:"4"};
            var newObject = RightNow.Lang.cloneObject(object);
            Y.Assert.areSame(newObject.one, "one");
            Y.Assert.areSame(newObject.two, "two");
            Y.Assert.areSame(newObject.three, "3");
            Y.Assert.areSame(newObject.four, "4");
            object.one = "1";
            object.four = "modified";
            Y.Assert.areSame(newObject.one, "one");
            Y.Assert.areSame(newObject.four, "4");
            newObject.two = [];
            newObject.three = "modified";
            Y.Assert.areSame(object.two, "two");
            Y.Assert.areSame(object.three, "3");
        },

        testArrayClone: function(){
            var array = ["1", 2, "three", null, undefined];
            var newArray = RightNow.Lang.cloneObject(array);
            Y.Assert.areSame(newArray[0], "1");
            Y.Assert.areSame(newArray[1], 2);
            Y.Assert.areSame(newArray[2], "three");
            Y.Assert.areSame(newArray[3], null);
            Y.Assert.areSame(newArray[4], undefined);
            array[0] = "one";
            newArray[1] = "two";
            Y.Assert.areSame(array[1], 2);
            Y.Assert.areSame(newArray[0], "1");
        },

        testSubObjectClone: function()
        {
            var object = {one: "one", subObj: {subOne: 1, subTwo: "two"}};
            var newObject = RightNow.Lang.cloneObject(object);
            Y.Assert.areSame(newObject.one, "one");
            Y.Assert.areSame(newObject.subObj.subOne, 1);
            Y.Assert.areSame(newObject.subObj.subTwo, "two");
            object.subObj.subOne = "one";
            newObject.subObj.subTwo = 2;
            Y.Assert.areSame(object.subObj.subTwo, "two");
            Y.Assert.areSame(newObject.subObj.subOne, 1);
        },

        testLengthPropertyClone: function() {
            var obj = {'length': {'foo': 'bar'}, 'banana': []},
                ret = RightNow.Lang.cloneObject(obj);
            Y.Assert.isObject(ret);
            Y.Assert.areSame('bar', ret['length'].foo);
            Y.Assert.isArray(ret.banana);
            Y.Assert.areSame(0, ret.banana.length);
        },
        
        testFunctionClone: function()
        {
            var object = {one: "one", func: function(){alert('func');}};
            var newObject = RightNow.Lang.cloneObject(object);
            Y.Assert.areSame(newObject.one, "one");
            Y.Assert.areSame(newObject.func, undefined);
        },
        
        testNullArgument: function()
        {
            var newObject = RightNow.Lang.cloneObject(null);
            Y.Assert.areSame(newObject, null);
        },
        
        testNonObject: function()
        {
            var newObject = RightNow.Lang.cloneObject("foo");
            Y.Assert.isObject(newObject);
        }
    }));

    rightnowLangTests.add(new Y.Test.Case(
    {
        name : "privateMembersHiddenTest",

        testPrivateMembers: function()
        {
            UnitTest.recursiveMemberCheck(Y, RightNow.Lang);
        }
    }));

    return rightnowLangTests;
});
UnitTest.run();
