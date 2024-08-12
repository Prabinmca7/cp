YUI.add('TreeViewAriaPlugin', function(Y) {
if (!Y.YUI2) return;
var YAHOO = Y.YUI2;
YAHOO.util.Event.onDOMReady(function(){
    if(!YAHOO.widget.TreeView || YAHOO.env.modules.treeviewariaplugin) return;

    var Dom = YAHOO.util.Dom,
        Event = YAHOO.util.Event,
        Lang = YAHOO.lang,
        UA = YAHOO.env.ua,

        TreeViewPrototype = YAHOO.widget.TreeView.prototype,
        fnTreeViewInit = TreeViewPrototype.init,
        fnTreeViewRender = TreeViewPrototype.render,
        NodePrototype = YAHOO.widget.Node.prototype,
        fnNodeLoadComplete = NodePrototype.loadComplete,

        m_tUseARIA = true,

        // Private constants for strings
        _ARIA_PREFIX = "aria-",
        _ROLE = "role",
        _PRESENTATION = "presentation",
        _TREE = "tree",
        _TREEITEM = "treeitem",
        _GROUP = "group",
        _SETSIZE = "setsize",
        _POSINSET = "posinset",
        _LEVEL = "level",
        _EXPANDED = "expanded";


    function setAriaRole (element, role) {
        element && element.setAttribute(_ROLE, role);
    }

    function setAriaProperty (element, property, value) {
        element && element.setAttribute((_ARIA_PREFIX + property), value);
    }

    function onCollapse (node) {
        if(node.hasChildren(true) || node.isDynamic()) {
            setAriaProperty(node.getLabelEl(), _EXPANDED, false);
        }
    }

    function onExpand (node) {
        if(node.hasChildren(true) || node.isDynamic()) {
            setAriaProperty(node.getLabelEl(), _EXPANDED, true);
        }
    }

    // TreeView ARIA plugin - augments YAHOO.widget.TreeView

    Lang.augmentObject(TreeViewPrototype, {
        _setUseARIA: function (p_bUseARIA) {

        },

        init: function() {
            fnTreeViewInit.apply(this, arguments);
            if(!m_tUseARIA)
                return;

            this.subscribe("expand", onExpand);
            this.subscribe("collapse", onCollapse);
        },

        render: function(){
            fnTreeViewRender.call(this);
            if(!m_tUseARIA)
                return;
            var isFirstNode = true, labelEl, thisNode;
            for(var node in this._nodes)
            {
                thisNode = this._nodes[node];
                if(typeof thisNode !== 'function' && this._nodes.hasOwnProperty(node))
                {
                    thisNode.addFunctionalAria();
                    thisNode.addPresentationRoles();
                    if(!this.currentFocus && isFirstNode)
                    {
                        labelEl = thisNode.getLabelEl();
                        labelEl.setAttribute("tabindex", 0);
                        thisNode._focusHighlightedItems.push(labelEl);
                        this.currentFocus = thisNode;
                        isFirstNode = false;
                    }
                    //sometimes render is called more than once
                    else if(thisNode === this.currentFocus)
                    {
                        labelEl = thisNode.getLabelEl();
                        labelEl.setAttribute("tabindex", 0);
                    }
                }
            }
            setAriaRole(this.root.getEl(), _TREE);
            //attempt to coerce JAWS into forms mode (only works in IE. Seems
            //to make no difference in FF)
            //this is also not necessary for JAWS 12 in IE
            setAriaRole(this.getEl(), _GROUP);
            setAriaRole(this.root.getChildrenEl(), _PRESENTATION);
        },

        _onKeyDownEvent: function(ev) {
            var target = YAHOO.util.Event.getTarget(ev),
                  node = this.getNodeByElement(target),
                  newNode = node,
                  KEY = YAHOO.util.KeyListener.KEY,
                  currentNode;
            switch(ev.keyCode) {
                case KEY.UP:
                    do {
                        if(newNode.previousSibling) {
                            currentNode = newNode.previousSibling;
                            while(currentNode && currentNode.expanded && currentNode.children.length) {
                                currentNode = currentNode.children[currentNode.children.length - 1];
                            }
                            newNode = currentNode;
                        }
                        else {
                            newNode = newNode.parent;
                        }
                    }
                    while(newNode && !newNode._canHaveFocus());
                    if (newNode)
                        newNode.focus();
                    YAHOO.util.Event.preventDefault(ev);
                    break;
                case KEY.DOWN:
                    do {
                        if(newNode.children.length && newNode.expanded) {
                            newNode = newNode.children[0];
                        }
                        else if(newNode.nextSibling) {
                            newNode = newNode.nextSibling;
                        }
                        else {
                            currentNode = newNode.parent;
                            while(currentNode) {
                                if(currentNode.nextSibling) {
                                    newNode = currentNode.nextSibling;
                                    break;
                                }
                                else {
                                    currentNode = currentNode.parent;
                                }
                            }
                        }
                    }
                    while(newNode && !newNode._canHaveFocus);
                    if(newNode)
                        newNode.focus();
                    YAHOO.util.Event.preventDefault(ev);
                    break;
                case KEY.LEFT:
                    node.collapse();
                    YAHOO.util.Event.preventDefault(ev);
                    break;
                case KEY.RIGHT:
                    node.expand();
                    YAHOO.util.Event.preventDefault(ev);
                    break;
                case KEY.ENTER:
                    if(node.href) {
                        if(node.target) {
                            window.open(node.href,node.target);
                        }
                        else {
                            window.location = node.href;
                        }
                    }
                    else {
                        node.toggle();
                    }
                    this.fireEvent('enterKeyPressed', node);
                    YAHOO.util.Event.preventDefault(ev);
                    break;
                case KEY.HOME:
                    newNode = this.getRoot();
                    if(newNode.children.length)
                        newNode = newNode.children[0];
                    if(newNode._canHaveFocus())
                        newNode.focus();
                    YAHOO.util.Event.preventDefault(ev);
                    break;
                case KEY.END:
                    newNode = newNode.parent.children;
                    newNode = newNode[newNode.length -1];
                    if(newNode._canHaveFocus())
                        newNode.focus();
                    YAHOO.util.Event.preventDefault(ev);
                    break;
                case 107:  //plus key
                    if(ev.shiftKey) {
                        node.parent.expandAll();
                    }
                    else {
                        node.expand();
                    }
                    break;
                case 109: //minus key
                    if(ev.shiftKey) {
                        node.parent.collapseAll();
                    }
                    else {
                        node.collapse();
                    }
                    break;
            }
        }
    }, "render", "init", "_setUseARIA", "_onKeyDownEvent");

    Lang.augmentObject(NodePrototype, {
        addPresentationRoles: function(){
            var topElem = this.getEl(),
                tableRows = Dom.getElementsByClassName('ygtvrow' , 'tr' , topElem),
                spacers = Dom.getElementsByClassName('ygtvspacer' , 'div' , topElem),
                tables = Dom.getElementsByClassName('ygtvtable' , 'table' , topElem),
                toggleChild = this.getToggleEl().firstChild;
            //there should only be one
            for(var table in tables)
            {
                if(typeof tables[table] !== 'function' && tables.hasOwnProperty(table))
                {
                    setAriaRole(tables[table], _PRESENTATION);
                    setAriaRole(tables[table].firstChild, _PRESENTATION);
                }
            }

            //there should only be one
            for(var row in tableRows)
            {
                if(typeof tableRows[row] !== 'function' && tableRows.hasOwnProperty(row))
                    setAriaRole(tableRows[row], _PRESENTATION);
            }
            //there could be many
            for(var spacer in spacers)
            {
                if(typeof spacers[spacer] !== 'function' && spacers.hasOwnProperty(spacer))
                    setAriaRole(spacers[spacer], _PRESENTATION);
            }
            setAriaRole(this.getContentEl(), _PRESENTATION);
            setAriaRole(this.getToggleEl(), _PRESENTATION);
            setAriaRole(this.getEl(), _PRESENTATION);
            setAriaRole(toggleChild, _PRESENTATION);
            toggleChild.setAttribute("tabindex", -1);
        },

        addFunctionalAria: function(){
            var ariaLevel = this.depth+1,
                toggleEl = this.getToggleEl(),
                labelEl = this.getLabelEl();
            if(!toggleEl || !labelEl)
                return;
            var setSize = this.parent.children.length;
            var positionInSet = this.getPositionInSet();
            setAriaRole(labelEl, _TREEITEM);
            labelEl.setAttribute("tabindex", -1);
            setAriaProperty(labelEl, _LEVEL, ariaLevel);
            setAriaProperty(labelEl, _SETSIZE, setSize);
            setAriaProperty(labelEl, _POSINSET, positionInSet);
            //we don't want JAWS announcing "closed" on leaves.
            if(this.expanded || this.hasChildren(true))
                setAriaProperty(labelEl, _EXPANDED, this.expanded);

            var group = this.getChildrenEl();
            setAriaRole(group, _GROUP);
        },

        loadComplete: function(){
            //if the tree was not a dynamicLoad tree this wouldn't be sufficient
            //the ARIA would have to be applied on expand and possibly collapse as well
            fnNodeLoadComplete.call(this);
            if(!m_tUseARIA)
                return;

            var thisChild;
            for(var child in this.children)
            {
                thisChild = this.children[child];
                if(typeof thisChild !== 'function' && this.children.hasOwnProperty(child))
                {
                    thisChild.addFunctionalAria();
                    thisChild.addPresentationRoles();
                }
            }
        },

        //
        // * Returns this node's position in the set of its siblings starting at 1
        // * @return integer
        //
        getPositionInSet: function() {
            var sib =  this.parent.children.slice(0);
            var i = 0;
            while (i < sib.length && sib[i] != this)
            {
                i++;
            }
            return i + 1;
        },

        /**
        * Removes the focus of previously selected Node
        * and sets tabindex to -1
        * @private
        */
        _removeFocus:function () {
            if (this._focusedItem) {
                Event.removeListener(this._focusedItem,'blur');
                this._focusedItem = null;
            }
            var el;
            while ((el = this._focusHighlightedItems.shift())) {  // yes, it is meant as an assignment, really
                el.setAttribute("tabindex", -1);
                Dom.removeClass(el,YAHOO.widget.TreeView.FOCUS_CLASS_NAME );
            }
        }
    }, true);
YAHOO.register("treeviewariaplugin", YAHOO.widget.TreeView, {version: "1", build: "1"});});
}, '2.7.0', {requires: ['yui2-yahoo', 'yui2-dom', 'yui2-event', 'yui2-treeview']});