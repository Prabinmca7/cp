CodeMirror.defineMode("sql", function(config, parserConfig) {
  "use strict";

  var atoms          = parserConfig.atoms || {"false": true, "true": true, "null": true},
      builtin        = parserConfig.builtin || {},
      keywords       = parserConfig.keywords || {},
      operatorChars  = parserConfig.operatorChars || /^[*+\-%<>!=&|~^]/,
      support        = parserConfig.support || {},
      hooks          = parserConfig.hooks || {},
      dateSQL        = parserConfig.dateSQL || {"date" : true, "time" : true, "timestamp" : true};

  function tokenBase(stream, state) {
    var ch = stream.next();

    // call hooks from the mime type
    if (hooks[ch]) {
      var result = hooks[ch](stream, state);
      if (result !== false) return result;
    }

    if ((ch == "0" && stream.match(/^[xX][0-9a-fA-F]+/))
      || (ch == "x" || ch == "X") && stream.match(/^'[0-9a-fA-F]+'/)) {
      // hex
      return "number";
    } else if (((ch == "b" || ch == "B") && stream.match(/^'[01]+'/))
      || (ch == "0" && stream.match(/^b[01]+/))) {
      // bitstring
      return "number";
    } else if (ch.charCodeAt(0) > 47 && ch.charCodeAt(0) < 58) {
      // numbers
      stream.match(/^[0-9]*\.?[0-9]+([eE][-+]?[0-9]+)?/);
      return "number";
    } else if (ch == "?" && (stream.eatSpace() || stream.eol() || stream.eat(";"))) {
      // placeholders
      return "variable-3";
    } else if (ch == '"' || ch == "'") {
      // strings
      state.tokenize = tokenLiteral(ch);
      return state.tokenize(stream, state);
    } else if (/^[\(\),\;\[\]]/.test(ch)) {
      // no highlightning
      return null;
    } else if (ch == "#" || (ch == "-" && stream.eat("-") && stream.eat(" "))) {
      // 1-line comments
      stream.skipToEnd();
      return "comment";
    } else if (ch == "/" && stream.eat("*")) {
      // multi-line comments
      state.tokenize = tokenComment;
      return state.tokenize(stream, state);
    }
    else if (operatorChars.test(ch)) {
      // operators
      stream.eatWhile(operatorChars);
      return null;
    } else if (ch == '{' &&
        (stream.match(/^( )*(d|D|t|T|ts|TS)( )*'[^']*'( )*}/) || stream.match(/^( )*(d|D|t|T|ts|TS)( )*"[^"]*"( )*}/))) {
      // dates (weird ODBC syntax)
      return "number";
    } else {
      stream.eatWhile(/^[\$._\w\d]/);
      var word = stream.current().toLowerCase();
      // dates (standard SQL syntax)
      if (dateSQL.hasOwnProperty(word) && (stream.match(/^( )+'[^']*'/) || stream.match(/^( )+"[^"]*"/)))
        return "number";
      if (atoms.hasOwnProperty(word)) return "atom";
      if (builtin.hasOwnProperty(word)) return "builtin";
      if (keywords.hasOwnProperty(word)) return "keyword";

      for(var table in atoms){
        if(word.length > table.length && beginsWith(word, table) && (word[table.length] === '.' || word[table.length] === '$')){
          return 'atom';
        }
      }

      return null;
    }
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
      } else {
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
    startState: function() {
      return {tokenize: tokenBase, context: null};
    },

    token: function(stream, state) {
      if (stream.sol()) {
        if (state.context && state.context.align == null)
          state.context.align = false;
      }
      if (stream.eatSpace()) return null;

      var style = state.tokenize(stream, state);
      if (style == "comment") return style;

      if (state.context && state.context.align == null)
        state.context.align = true;

      var tok = stream.current();
      if (tok == "(")
        pushContext(stream, state, ")");
      else if (tok == "[")
        pushContext(stream, state, "]");
      else if (state.context && state.context.type == tok)
        popContext(state);
      return style;
    },

    indent: function(state, textAfter) {
      var cx = state.context;
      if (!cx) return CodeMirror.Pass;
      if (cx.align) return cx.col + (textAfter.charAt(0) == cx.type ? 0 : 1);
      else return cx.indent + config.indentUnit;
    }
  };
});

(function() {
  "use strict";

  // `identifier`
  function hookIdentifier(stream) {
    var ch;
    while ((ch = stream.next()) != null) {
      if (ch == "`" && !stream.eat("`")) return "variable-2";
    }
    return null;
  }

  // variable token
  function hookVar(stream) {
    // variables
    // @@ and prefix
    if (stream.eat("@")) {
      stream.match(/^session\./);
      stream.match(/^local\./);
      stream.match(/^global\./);
    }

    if (stream.eat("'")) {
      stream.match(/^.*'/);
      return "variable-2";
    } else if (stream.eat('"')) {
      stream.match(/^.*"/);
      return "variable-2";
    } else if (stream.eat("`")) {
      stream.match(/^.*`/);
      return "variable-2";
    } else if (stream.match(/^[0-9a-zA-Z$\.\_]+/)) {
      return "variable-2";
    }
    return null;
  };

  // short client keyword token
  function hookClient(stream) {
    // \g, etc
    return stream.match(/^[a-zA-Z]\b/) ? "variable-2" : null;
  }

  function set(str) {
    var obj = {}, words = str.split(" ");
    for (var i = 0; i < words.length; ++i) obj[words[i]] = true;
    return obj;
  }

  CodeMirror.defineMIME("text/x-roql", {
    name: "sql",
    keywords: set("abort abs action add after all alter analyze and as asc attach autoincrement before begin between by cascade case cast changes char check coalesce collate column conflict constraint count cross curadminuser curadminusername curinterface curinterfacename curlanguage curlanguagename current_date current_time current_timestamp database default deferrable deferred desc detach distinct drop each else end escape except exclusive exists fail for foreign from full glob group having hex if ifnull ignore immediate in instr index indexed initially inner instead intersect into is isnull join key left length like limit lower ltrim match max min natural no not notnull null nullif objects of offset on or order outer plan pragma primary query quote raise random references regexp reindex release rename replace restrict right rollback round row rtrim savepoint select set show substr table tables temp temporary then to transaction trigger trim union unique upper using vacuum values view virtual when where"),
    builtin: set("bigint bit blob bool boolean char date datetime decimal double enum float float4 float8 int int1 int2 int3 int4 int8 integer long longblob longtext medium mediumblob mediumint mediumtext numeric precision signed text time timestamp tinyblob tinyint tinytext unsigned varbinary varchar varcharacter year"),
    atoms: set("false true null unknown" + " " + primaryConnectObjects),
    operatorChars: /^[*+\-%<>!=&|^]/,
    dateSQL: set("date time timestamp"),
    hooks: {
      "@":   hookVar,
      "`":   hookIdentifier,
      "\\":  hookClient
    }
  });
}());
