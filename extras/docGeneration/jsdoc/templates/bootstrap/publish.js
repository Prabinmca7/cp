// This script licensed under the MIT.
// http://orgachem.mit-license.org
//
// Original script is licensed under the MIT.
// jsdoc-toolkit project:
// http://code.google.com/p/jsdoc-toolkit/

/**
 * @fileoverview Script for a document publication.
 * @author orga.chem.job@gmail.com (Orga Chem)
 */

/**
 * Heavily modified to fit Customer Portal's needs by tracy.livengood@oracle.com
 */

/** Called automatically by JsDoc Toolkit. */
function publish(symbolSet) {
	publish.conf = {	// trailing slash expected for dirs
		name:				"neo-jsdoctpl-bootstrap",
		ext:				 ".html",
		outDir:			JSDOC.opt.d || SYS.pwd+"../out/jsdoc/",
		templatesDir: JSDOC.opt.t || SYS.pwd+"../templates/jsdoc/",
		symbolsDir:	"symbols/",
		srcDir:			"symbols/src/"
	};
	
  	// overwrite to a better Link module
  	eval(include('extends/Link.js'));

	// is source output is suppressed, just display the links to the source file
	if (JSDOC.opt.s && defined(Link) && Link.prototype._makeSrcLink) {
		Link.prototype._makeSrcLink = function(srcFilePath) {
			return "&lt;"+srcFilePath+"&gt;";
		}
	}
	
	// create the folders and subfolders to hold the output
	IO.mkPath((publish.conf.outDir+"symbols/src").split("/"));
	IO.mkPath((publish.conf.outDir+"js").split("/"));
	IO.mkPath((publish.conf.outDir+"css").split("/"));
	IO.mkPath((publish.conf.outDir+"img").split("/"));
		
	// used to allow Link to check the details of things being linked to
	Link.symbolSet = symbolSet;

	// create the required templates
	try {
		var wrapTemplate = new JSDOC.JsPlate(publish.conf.templatesDir+"wrap.tmpl");
		var classTemplate = new JSDOC.JsPlate(publish.conf.templatesDir+"class.tmpl");
		var sidebarTemplate = new JSDOC.JsPlate(publish.conf.templatesDir+"sidebar.tmpl");
		var namespacesTemplate = new JSDOC.JsPlate(publish.conf.templatesDir+"namespaces.tmpl");
		var sourceTemplate = new JSDOC.JsPlate(publish.conf.templatesDir+"source.tmpl");
		var landingTemplate = new JSDOC.JsPlate(publish.conf.templatesDir+"landing.tmpl");
	}
	catch(e) {
		print("Couldn't create the required templates: "+e);
		quit();
	}
	
	// some ustility filters
	function hasNoParent($) {return ($.memberOf == "")}
	function isaFile($) {return ($.is("FILE"))}
	function isaClass($) {return ($.is("CONSTRUCTOR") || $.isNamespace)}
	
	//Get a list of all the classes in the symbolSet
	var symbols = symbolSet.toArray(),
		classes = smartSort(symbols.filter(isaClass));

	//Generate the landing page
	Link.base = '';
	publish.classesIndex = namespacesTemplate.process(classes); // kept in memory
	var output = landingTemplate.process('');
	IO.saveFile(publish.conf.outDir, 'index' + publish.conf.ext, output);

	//For each of the classes, generate a class page and wrap it in the template
	Link.base = "../";
	publish.classesIndex = sidebarTemplate.process(classes); // kept in memory
	for (var i = 0, l = classes.length; i < l; i++) {
		var symbol = classes[i];
		
		symbol.events = symbol.getEvents();	 // 1 order matters
		symbol.methods = symbol.getMethods(); // 2
		
		Link.currentSymbol= symbol;
		var output = wrapTemplate.process(classTemplate.process(symbol));
		IO.saveFile(publish.conf.outDir+"symbols/", symbol.alias + publish.conf.ext, output);
	}

	//For every source code file, generate a source file page and wrap it in the template
	Link.base = "../../";
	publish.classesIndex = sidebarTemplate.process(classes); // kept in memory
	var files = JSDOC.opt.srcFiles;
	for (var i = 0, l = files.length; i < l; i++) {
		var file = files[i];
		var srcDir = publish.conf.outDir + "symbols/src/";
		var name = file.replace(/\.\.?[\\\/]/g, "").replace(/[\\\/]/g, "_");
		name = name.replace(/\:/g, "_");
		var output = wrapTemplate.process(sourceTemplate.process({ file: file, source: escapeHTML(IO.readFile(file)) }));
		IO.saveFile(srcDir, name + publish.conf.ext, output);
	}

	//Copy over the static assets (Twitter Bootstrap)
	var paths = [
		'jquery-1.7.2.min.js',
  		'accordion.js',
		'google-code-prettify/prettify.js',
		'bootstrap/js/bootstrap.min.js',
		'common.css',
		'bootstrap/css/bootstrap.min.css',
		'bootstrap/css/bootstrap-responsive.min.css',
		'google-code-prettify/prettify.css',
		'template.css',
		'prettify.css',
		'bootstrap/img/glyphicons-halflings-white.png',
		'bootstrap/img/glyphicons-halflings.png',
		'img/classicons.png',
		'img/class.png',
		'img/interface.png',
		'img/namespace.png',
	];

	//Setup the directories
	IO.makeDir(publish.conf.outDir + 'js');
	IO.makeDir(publish.conf.outDir + 'css');
	IO.makeDir(publish.conf.outDir + 'img');

	//Write out all of the assets
	var path, i, output;
	for(i = 0; i < paths.length; i++) {
		path = paths[i];
		extension = path.substr(path.lastIndexOf('.')+1, path.length);
		outputPath = publish.conf.outDir + ((extension === 'png') ? 'img' : extension + '/');
		IO.copyFile(publish.conf.templatesDir + 'static/' + paths[i], outputPath);
	}
}


/** Just the first sentence (up to a full stop). Should not break on dotted variable names. */
function summarize(desc) {
	if (typeof desc != "undefined")
		return desc.match(/([\w\W]+?\.)[^a-z0-9_$]/i)? RegExp.$1 : desc;
}

/** Make a symbol sorter by some attribute. */
function makeSortby(attribute) {
	return function(a, b) {
		if (a[attribute] != undefined && b[attribute] != undefined) {
			a = a[attribute].toLowerCase();
			b = b[attribute].toLowerCase();
			if (a < b) return -1;
			if (a > b) return 1;
			return 0;
		}
	}
}

/** Pull in the contents of an external file at the given path. */
function include(path) {
	var path = publish.conf.templatesDir+path;
	return IO.readFile(path);
}

/** Find symbol {@link ...} strings in text and turn into html links */
function resolveLinks(str, from) {
	str = str.replace(/\{@link ([^} ]+) ?\}/gi,
		function(match, symbolName) {
			return new Link().toSymbol(symbolName);
		}
	);
	
	return str;
}
/**
 * Build output for displaying function parameters.
 * Output format is:
 * <pre>
 * ( Type1 param1, Type2 param2, [Type3 optionalParam])
 * </pre>
 * @param {JSDOC.DocTag[]} params Array has DocTag object of parameters.
 */
function makeSignature(params) {
	if (!params) return '( )';
	var signature = params.filter(function(docTag) {
				return docTag.name.indexOf('.') == -1; // don't show config params in signature
		}).map(function(docTag) {
        var result = createTypeLink(docTag.type) + ' ' + docTag.name;
        if (docTag.isOptional) result = '[' + result + ']';
        return result;
		}).join(', ');

	signature = '( ' + signature + ' )';
	return signature;
}

/**
 * Get parent symbols.
 * @param {JSDOC.Symbol}
 * @return {Array[JSDOC.Symbol]}
 */
function getParentSymbols(symbol) {
	var newSym = symbol;
	var result = [];
	while (newSym) {
    newSym = JSDOC.Parser.symbols.getSymbol(newSym.augments);
    if (!newSym) break;
    result.unshift(newSym);
  }
	return result;
}

/**
 * Get parent symbols.
 * @param {JSDOC.Symbol}
 * @return {Array[JSDOC.Symbol]}
 */
function getParentNamespaces(symbol) {
  var namespaces = symbol.alias.split('.').slice(0, -1);
  var symbols = [];
  while (namespaces.length) {
    symbols.push(JSDOC.Parser.symbols.getSymbol(namespaces.join('.')));
    namespaces.pop();
  }
  return symbols;
}


/**
 * @param {Array[JSDOC.Symbol]} symbols A symbols array.
 * @return {Array[JSDOC.Symbol]} The sorted symbols array.
 */
var smartSort = function(symbols) {
	return symbols.sort(makeSortWithCaseSensitiveBy('alias'));
};


/** Make a symbol sorter by some attribute. */
function makeSortWithCaseSensitiveBy(attribute) {
	return function(a, b) {
		if (a[attribute] != undefined && b[attribute] != undefined) {
			a = a[attribute];
			b = b[attribute];
			if (a < b) return -1;
			if (a > b) return 1;
			return 0;
		}
	}
}


/**
 * @link tag replace to the Symbol Link.
 * @param {String} desc Description text.
 * @return {String} The replaced description.
 */
function convInlineCodes(desc) {
  var result = desc.replace(/<pre>/ig, '<pre class="prettyprint linenums">');
  result = result.replace(/\{@link ([^} ]+) ?\}/gi, "<code>$1</code>");
	return result;
}


/**
 * Create formatted description.
 * @param {String} desc Description text.
 * @return {String} The replaced description.
 */
function createDescription(desc) {
  return convInlineCodes(resolveLinks(desc || 'No description.'));
}


/**
 * Create type definition from a complex type description.
 * @param {String} desc Description text.
 * @return {String} The replaced description.
 */
function createTypeLink(type) {
  var text = '';
  if (type) {
    var aliases = type.split('|');
    var result = [];
    aliases.forEach(function(alias) {
      result.push(new Link().toSymbol(alias).toString());
    });
    text = result.join('/');
  } else {
    text = 'unknown';
  }
  return '<span class="jsdoc-typedesc">' + text + '</span>';
}

function escapeHTML(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
