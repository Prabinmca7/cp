UnitTest.addSuite({
    type: UnitTest.Type.Admin,
    yuiModules: ['basic-markdown'],
    preloadFiles: ['/euf/core/admin/js/versions/components/markdown.js']
}, function(Y) {
    var testData = {
        // 0 -> Expected output
        // 1 -> Input
        underscoreItalics: [
            ['<em>bananas</em>', '_bananas_'],
            ['  <em>bananas read</em>  ', '  _bananas read_  ']
        ],
        starItalics: [
            ['<em>bananas</em>', '*bananas*'],
            ['  <em>bananas read</em>  ', '  _bananas read_  ']
        ],
        underscoreBolds: [
            ['<strong>bananas</strong>', '__bananas__'],
            ['  <strong>bananas read</strong>  ', '  __bananas read__  ']
        ],
        starBolds: [
            ['<strong>bananas</strong>', '**bananas**'],
            ['  <strong>bananas read</strong>  ', '  **bananas read**  ']
        ],
        autoLinks: [
            ["<a href='http://www.placesheen.com' target='_blank'>http://www.placesheen.com</a>", '<http://www.placesheen.com>'],
            [" <a href='https://www.placesheen.com' target='_blank'>https://www.placesheen.com</a>  ", ' <https://www.placesheen.com>  ']
        ],
        links: [
            ['<a href="http://www.placesheen.com" target="_blank">bananas</a>', '[bananas](http://www.placesheen.com)'],
            ['<a href="http://www.placesheen.com" target="_blank">bananas_napkins</a>', '[bananas_napkins](http://www.placesheen.com)'],
            ['  <a href="foood" target="_blank">bananas </a>', '  [bananas ](foood)']
        ],
        linksWithTitles: [
            ['<a href="http://www.placesheen.com" target="_blank" title="knowing & rowing">bananas</a>', '[bananas](http://www.placesheen.com "knowing & rowing")'],
            ['<a href="http://www.placesheen.com" target="_blank" title="it\'s">bananas_napkins</a>', '[bananas_napkins](http://www.placesheen.com "it\'s")'],
            ['  <a href="foood" target="_blank" title="earth is ">bananas </a>', '  [bananas ](foood "earth is ")']
        ],
        code: [
            ['<code>RightNow\\Api</code>', '`RightNow\\Api`'],
            [' <code>&lt;pre&gt; &lt;b&gt;bananas&lt;/b&gt;&lt;/pre&gt;</code> ', ' `<pre> <b>bananas</b></pre>` ']
        ],
        codeWithMarkdown: [
            ['<code>**not bold**</code>', '`**not bold**`'],
            ['<code>*not italic*</code>', '`*not italic*`'],
            ['<code>__not bold__</code>', '`__not bold__`'],
            [' <code>&lt;pre&gt; &lt;b&gt;_bananas_&lt;/b&gt;&lt;/pre&gt;</code> ', ' `<pre> <b>_bananas_</b></pre>` ']
        ],
        mixed: [
            ['<em>bananas</em> <strong>stronger bananas</strong> <em>wonder</em> <strong>hopes</strong>', '_bananas_ __stronger bananas__ *wonder* **hopes**'],
            ['<a href=\'http://www.placesheen.com\' target=\'_blank\'>http://www.placesheen.com</a> <strong>things</strong> <a href="keep" target="_blank">going</a>', '<http://www.placesheen.com> **things** [going](keep)'],
            ['<strong><a href="strong" target="_blank">link</a></strong>', '__[link](strong)__']
        ],
        escaped: [
            ['*not italic*', '\\*not italic\\*'],
            ['_not italic_', '\\_not italic\\_'],
            ['__not bold__', '\\_\\_not bold\\_\\_'],
            ['**not bold**', '\\*\\*not bold\\*\\*'],
            ['[here](http://www.placesheen.com)', '\\[here\\]\\(http://www.placesheen.com\\)'],
            ['`coding`', '\\`coding\\`']
        ]
    };

    var suite = new Y.Test.Suite({ name: "Test the basic markdown module" });

    suite.add(new Y.Test.Case({
        name: "Markdown to HTML conversion",

        runTests: function(tests) {
            Y.Array.each(tests, function(test) {
                Y.Assert.areSame(test[0], Y.MarkdownToHTML(test[1]));
            }, this);
        },

        "_Italics_ are converted": function() {
            this.runTests(testData.underscoreItalics);
        },

        "*Italics* are converted": function() {
            this.runTests(testData.starItalics);
        },

        "__Bolds__ are converted": function() {
            this.runTests(testData.underscoreBolds);
        },

        "**Bolds** are converted": function() {
            this.runTests(testData.starBolds);
        },

        "Automatic links are converted": function() {
            this.runTests(testData.autoLinks);
        },

        "Links are converted": function() {
            this.runTests(testData.links);
        },

        "Links with titles are converted": function() {
            this.runTests(testData.linksWithTitles);
        },

        "Inline code is converted": function() {
            this.runTests(testData.code);
        },

        "Markdown within inline code isn't converted": function() {
            this.runTests(testData.codeWithMarkdown);
        },

        "Integrated markdown is converted": function() {
            this.runTests(testData.mixed);
        },

        "Escaped magic chars aren't markdownized (but are unescaped)": function() {
            this.runTests(testData.escaped);
        }
    }));

    suite.add(new Y.Test.Case({
        name: "Not converted",

        scenarios: [
            '_gooood',
            '*hey',
            '__nono',
            'don\'t**',
            '# Hey',
            "## Hey ##",
            'no`pe'
        ],

        "Magic markdown chars are present but aren't markdown so they aren't converted": function() {
            Y.Array.each(this.scenarios, function(test) {
                Y.Assert.areSame(test, Y.MarkdownToHTML(test));
            });
        },

        "Falsy values are just returned": function() {
            Y.Array.each([null, 0, false, undefined, ''], function(input) {
                Y.Assert.areSame(input, Y.MarkdownToHTML(input));
            });
        }
    }));

    return suite;
}).run();
