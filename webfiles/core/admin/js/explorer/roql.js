/*global primaryConnectObjects*/
CodeMirror.defineMode("sql", function(config, parserConfig) {
    "use strict";

    var atoms         = parserConfig.atoms || {"false": true, "true": true, "null": true},
        builtin       = parserConfig.builtin || {},
        keywords      = parserConfig.keywords || {},
        operatorChars = parserConfig.operatorChars || /^[*+\-%<>!=&|~^]/,
        support       = parserConfig.support || {},
        dateSQL       = parserConfig.dateSQL || {"date" : true, "time" : true, "timestamp" : true};

    function tokenBase(stream, state){
        var ch = stream.next();
        if(ch.charCodeAt(0) > 47 && ch.charCodeAt(0) < 58) {
            // numbers
            stream.match(/^[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?/);
            return "number";
        }
        if(ch === '"' || ch === "'") {
            // strings
            state.tokenize = tokenLiteral(ch);
            return state.tokenize(stream, state);
        }
        if(/^[\(\),\;\[\]]/.test(ch)) {
            // no highlightning
            return null;
        }
        if(ch === "#" || (ch === "-" && stream.eat("-") && stream.eat(" "))) {
            // 1-line comments
            stream.skipToEnd();
            return "comment";
        }
        if(ch === "/" && stream.eat("*")) {
            // multi-line comments
            state.tokenize = tokenComment;
            return state.tokenize(stream, state);
        }
        if(operatorChars.test(ch)) {
            // operators
            stream.eatWhile(operatorChars);
            return null;
        }

        stream.eatWhile(/^[\$._\w\d]/);
        var word = stream.current().toLowerCase();
        // dates (standard SQL syntax)
        if(dateSQL.hasOwnProperty(word) && (stream.match(/^( )+'[^']*'/) || stream.match(/^( )+"[^"]*"/)))
            return "number";
        if(atoms.hasOwnProperty(word))
            return "atom";
        if(builtin.hasOwnProperty(word))
            return "builtin";
        if(keywords.hasOwnProperty(word))
            return "keyword";

        for(var table in atoms){
            if(word.length > table.length && beginsWith(word, table) && (word[table.length] === '.' || word[table.length] === '$')){
                return 'atom';
            }
        }
        return null;
    }

    function beginsWith(haystack, needle){
        return (haystack.substr(0, needle.length) === needle);
    }

    // 'string', with char specified in quote escaped by '\'
    function tokenLiteral(quote) {
        return function(stream, state) {
            var escaped = false, ch;
            while ((ch = stream.next()) != null) {
                if (ch == quote && !escaped) {
                    state.tokenize = tokenBase;
                    break;
                }
                escaped = !escaped && ch == "\\";
            }
            return "string";
        };
    }
    function tokenComment(stream, state) {
        while (true) {
            if (stream.skipTo("*")) {
                stream.next();
                if (stream.eat("/")) {
                    state.tokenize = tokenBase;
                    break;
                }
            }
            else{
                stream.skipToEnd();
                break;
            }
        }
        return "comment";
    }

    function pushContext(stream, state, type) {
        state.context = {
            prev: state.context,
            indent: stream.indentation(),
            col: stream.column(),
            type: type
        };
    }

    function popContext(state) {
        state.indent = state.context.indent;
        state.context = state.context.prev;
    }

    return {
        startState: function(){
            return {tokenize: tokenBase, context: null};
        },

        token: function(stream, state){
            if(stream.sol()){
                if(state.context && state.context.align == null)
                    state.context.align = false;
            }
            if(stream.eatSpace())
                return null;

            var style = state.tokenize(stream, state);
            if(style === "comment")
                return style;

            if(state.context && state.context.align == null)
                state.context.align = true;

            var tok = stream.current();
            if (tok === "(")
                pushContext(stream, state, ")");
            else if (tok === "[")
                pushContext(stream, state, "]");
            else if (state.context && state.context.type === tok)
                popContext(state);
            return style;
        },

        indent: function(state, textAfter) {
            var cx = state.context;
            if(!cx)
                return CodeMirror.Pass;
            if(cx.align)
                return cx.col + (textAfter.charAt(0) == cx.type ? 0 : 1);
            return cx.indent + config.indentUnit;
        }
    };
});

(function() {
    "use strict";
    function set(str) {
        var obj = {}, words = str.split(" ");
        for (var i = 0; i < words.length; ++i)
            obj[words[i]] = true;
        return obj;
    }

    CodeMirror.defineMIME("text/x-roql", {
        name: "sql",
        keywords: set("abs add after all alter and as asc before begin by case cast changes char check column conflict constraint count curadminuser curadminusername curinterface curinterfacename curlanguage curlanguagename current_date current_time current_timestamp date_add date_diff date_trunc desc describe distinct each else end exists for from full group having if ifnull ignore in instr inner into is isnull join key left length like limit lower ltrim match max min no not notnull null nullif of offset on or order outer pragma primary query regexp right round row select set substr table temp then to trim union unique upper using values when where"),
        atoms: set("false true null unknown" + " " + primaryConnectObjects),
        operatorChars: /^[*+\-%<>!=&|^]/,
        dateSQL: set("date time timestamp")
    });
}());