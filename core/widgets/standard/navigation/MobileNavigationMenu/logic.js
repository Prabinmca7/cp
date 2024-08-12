RightNow.Widgets.MobileNavigationMenu = RightNow.Widgets.extend({
    constructor: function() {
        this._currentlyShowing = false;
        this._submenu = this.Y.one("#" + this.data.attrs.submenu);
        if(this._submenu) {
            this._button = this.Y.one(this.baseSelector + "_Link");
            this._button.on("click", this._onClick, this);
            //ensure only one menu across multiple widgets shows at a time
            RightNow.Event.subscribe("evt_navigationMenuShow", function(evt, args) {
                if(args[0].w_id !== this.instanceID && this._currentlyShowing) {
                    this._onClick(null, true);
                }
            }, this);
            this._eo = new RightNow.Event.EventObject(this);
        }
        else {
            RightNow.UI.addDevelopmentHeaderError('Element specified by submenu attribute does not exist.');
        }
    },

    /**
    * Toggles the display of the menu.
    * @param {Object} clickEvent Dom click event (or null if triggered programmatically)
    * @param {Boolean=} loseAll whether all sub-menu levels should be closed; optional
    */
    _onClick: function(clickEvent, closeAll) {
        this._initMenu();

        if (this._subMenuShowing) {
            for (var i = 0; i < this._contentStack.length; i++) {
                this._contentStack[i].setStyle("display", "none");
            }
            this._submenu.setStyle("display", "block");
            this._button.set("innerHTML", this.data.attrs.label_button);
            this._subMenuShowing = false;
            if (!closeAll) return;
        }

        var cssFunction, displayProperty;

        if (this._currentlyShowing) {
            cssFunction = "removeClass";
            displayProperty = "none";
        }
        else {
            displayProperty = "block";
            cssFunction = "addClass";
            RightNow.Event.fire("evt_navigationMenuShow", this._eo);
            // Ensure the menu still displays correctly even if the dom was changed to something the default CSS wasn't expecting
            var panelTop = parseInt(this._submenu.getStyle("top"), 10),
                buttonBottom = parseInt(this._button.get("offsetHeight"), 10) + parseInt(this._button.get("offsetTop"), 10);
            if(Math.abs(panelTop - buttonBottom) > 5) {
                this._submenu.setStyle("top", (buttonBottom + 5) + "px");
            }
            if(this._firstInput) {
                this._firstInput.focus();
            }
        }

        this._currentlyShowing = !this._currentlyShowing;
        this._panel.setStyle("display", displayProperty);
        this._button[cssFunction](this.data.attrs.css_class);
    },

    /**
    * Toggles the display of sub-menuing content.
    * @param {Object} evt Click event
    * @param {Object} itemToToggle HTMLElement item to toggle
    */
    _toggleMenu: function(evt, itemToToggle) {
        if (!itemToToggle.get("id")) {
            this._idGenerator = this._idGenerator || 1;
            itemToToggle.set("id", this.baseDomID + "_SubMenu" + this._idGenerator);
            this._idGenerator++;
        }
        this._submenu.setStyle("display", "none");
        this._panel.appendChild(itemToToggle);
        itemToToggle.setStyle("top", this._submenu.getStyle("top"))
                    .setStyle("display", "block")
                    .removeClass("rn_Hidden")
                    .addClass("rn_PanelContent")
                    .addClass("rn_Menu")
                    .addClass("rn_MobileNavigationMenu");
        this._button.set("innerHTML", RightNow.Interface.getMessage("BACK_LBL"));
        this._subMenuShowing = true;

        for (var i = 0, alreadyInStack; i < this._contentStack.length; i++) {
            if(this._contentStack[i].get("id") === itemToToggle.get("id")) {
                alreadyInStack = true;
                break;
            }
        }
        if (!alreadyInStack) {
            this._contentStack.push(itemToToggle);
        }
    },

    /**
    * Constructs the menu.
    */
    _initMenu: function() {
        if (!this._initialized) {
            //construct panel and add it to the dom
            this._panel = this.Y.Node.create("<div class='rn_Panel'></div>");
            this._panel.appendChild(this._submenu);
            this._submenu.setStyle("display", "block")
                    .removeClass("rn_Hidden")
                    .addClass("rn_PanelContent")
                    .addClass("rn_Menu")
                    .addClass("rn_MobileNavigationMenu");
            this.Y.one(document.body).get("firstChild").insert(this._panel, "before");

            var parentMenuAltHtml = "<span class='rn_ParentMenuAlt'> " + this.data.attrs.label_parent_menu_alt + "</span>";

            this._submenu.all("ul.rn_Submenu").each(function(subMenu) {
                subMenu.previous()
                    .append(parentMenuAltHtml)
                    .on("click", this._toggleMenu, this, subMenu);
            }, this);
            this._contentStack = [];

            var firstInput = this._submenu.one("input");
            if (firstInput && firstInput.focus) {
                this._firstInput = firstInput;
            }
            this._initialized = true;
        }
    }
});
