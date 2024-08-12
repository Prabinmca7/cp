RightNow.Widgets.OkcsProductCategorySearchFilter = RightNow.SearchConsumer.extend({
    overrides: {
        constructor: function() {
            this.parent();
            this._source_id = this.data.attrs.source_id;
            this._resetOkcsFilterRequest();
            if(this.data.attrs.view_type === 'explorer') {
                this._currentSelectedID = '';
                this._hasMoreLink = false;
                this._currentDisplayedCategoryCount = 100;
                this._requestInProgress = false;
                this.Y.one(this.baseSelector).delegate('click', this._expandCategory, 'a.rn_CategoryExplorerCollapsed', this);
                this.Y.one(this.baseSelector).delegate('click', this._collapseCategory, 'a.rn_CategoryExplorerExpanded', this);
                this.Y.one(this.baseSelector).delegate('click', this._categoryClick, 'a.rn_CategoryExplorerLink', this);
                this.Y.one(this.baseSelector).delegate('click', this._categoryClick, 'a.rn_CategoryExplorerLinkSelected', this);
                this.Y.one(this.baseSelector).delegate('click', this._getMoreProdCat, 'a.rn_GetMoreProdCatLink', this);
            }
            else {
                this.Y.augment(this, RightNow.ProductCategory);
                this._hasMoreLink = false;
                this.moreCategMap = new Object();
                this.isMoreLinkClicked = false;
                var filterType = this.data.attrs.filter_type === 'Product' ? '_Product' : '_Category';
                this._displayFieldVisibleText = this.Y.one(this.baseSelector + "_ButtonVisibleText");
                this.displayField = this.Y.one(this.baseSelector + filterType + "_Button");
                this.initializeEventObject();
                this.initializeTreeView(this.data.js.hierData, this.data.attrs.filter_type);
            }
            this.searchSource().on('response', this._handleResponse, this);
            this.searchSource().on('reset', this._resetOkcsFilterRequest, this);
            this.searchSource().on('collect', this.collectFilters, this);
            if(this.searchSource().options.params === undefined){
                this.searchSource().options.params = {};
            }
            this.searchSource().options.params = this.Y.mix(this.searchSource().options.params, {"productCategoryApiVersion": this.data.js.productCategoryApiVersion}, true);

            var productParam = RightNow.Url.getParameter('productRecordID');
            if(this.Y.one('[data-id="' + productParam + '"][data-type="Product"]')){
                this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').removeClass('rn_CategoryExplorerLink');
                this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').addClass('rn_CategoryExplorerLinkSelected');
            }
            var categoryParam = RightNow.Url.getParameter('categoryRecordID');
            if(this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]')){
                this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').removeClass('rn_CategoryExplorerLink');
                this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').addClass('rn_CategoryExplorerLinkSelected');
            }

            this._selectedProductRecordID = RightNow.Url.getParameter('productRecordID');
            this._selectedCategoryRecordID = RightNow.Url.getParameter('categoryRecordID');
            this._isCategorySelected = RightNow.Url.getParameter('isCategorySelected');
            this._isProductSelected = RightNow.Url.getParameter('isProductSelected');
            this._currentSelectedID = RightNow.Url.getParameter('currentSelectedID');

            if(this.data.attrs.filter_type === 'Product') {
                this.searchSource().on('collect', this._updateProductRecordID, this)
                               .on('collect', this._updateIsProductSelected, this)
                               .on('collect', this._updateCurrentSelectedID, this);
            } 
            else if(this.data.attrs.filter_type === 'Category') {
                this.searchSource().on('collect', this._updateCategoryRecordID, this)
                               .on('collect', this._updateIsCategorySelected, this)
                               .on('collect', this._updateCurrentSelectedID, this);
            }

            if (this.data.attrs.toggle_selection && this.data.attrs.item_to_toggle) {
                this._toggle = this.Y.one('#' + this.data.attrs.toggle);
                this._itemToToggle = this.Y.one('#' + this.data.attrs.item_to_toggle);
                this._itemToToggle.addClass(this.data.attrs.toggle_state === 'collapsed' ? this.data.attrs.collapsed_css_class : this.data.attrs.expanded_css_class);

                //trick to get voiceover to announce state to screen readers.
                if (this._toggle) {
                    if(this.data.attrs.filter_type === 'Product') {
                        this._alertId = 'rn_productTypeAlert';
                    }
                    else {
                        this._alertId = 'rn_categoryTypeAlert';
                    }
                    this._toggle.appendChild(this.Y.Node.create("<span id=" + this._alertId + " class='rn_ScreenReaderOnly' role='alert' aria-live='assertive'></span>"));
                }

                this._toggle.on("click", this._onToggle, this);
            }
        }
    },

    /**
    * Toggles the display of the element.
    * @param clickEvent Event Click event
    */
    _onToggle: function(clickEvent) {
        var target = clickEvent.target, cssClassToAdd, cssClassToRemove;
        this._currentlyShowing = this._itemToToggle.hasClass(this.data.attrs.expanded_css_class) ||
            this._itemToToggle.getComputedStyle("display") !== "none";

        if(this._currentlyShowing) {
            cssClassToAdd = this.data.attrs.collapsed_css_class;
            cssClassToRemove = this.data.attrs.expanded_css_class;
            RightNow.UI.hide(this._itemToToggle);
            this._updateAriaAlert(this.data.attrs.label_collapsed);
        }
        else {
            cssClassToAdd = this.data.attrs.expanded_css_class;
            cssClassToRemove = this.data.attrs.collapsed_css_class;
            RightNow.UI.show(this._itemToToggle);
            this._updateAriaAlert(this.data.attrs.label_expanded);
        }
        var firstElement = this.Y.one(this.baseSelector + ' a');
        if(firstElement)
        {
            firstElement.focus();
        }
        this._currentlyShowing = !this._currentlyShowing;
    },

    /**
    * Constructs an Explorerview widget for the first time with initial data returned
    * from the server.
    * @param {Object} category data
    */
    _buildExplorer : function(categoryData) {
        var explorerDiv = this.Y.one(this.baseSelector + "_Tree"),
            categoryType = this.data.attrs.filter_type === 'Product' ? 'PRODUCT' : 'CATEGORY',
            parentCount = 0,
            parentCategory,
            category = [];

        var getMoreCatLink = this.Y.one(this.baseSelector + ">a.rn_GetMoreProdCatLink");
        if (getMoreCatLink !== null) {
            if (!categoryData.hasMore) {
                getMoreCatLink.setStyle('display', 'none');
            }
            else {
                getMoreCatLink.setStyle('display', 'inline');
            }
        }
        categoryData = categoryData.items;
        if (categoryData !== undefined) {
            for (var key in categoryData) {
                if (categoryData[key].externalType === categoryType) {
                    categoryData[key].depth = 0;
                    categoryData[key].type = this.data.attrs.filter_type;
                    category[category.length] = categoryData[key];
                    parentCategory = categoryData[key];
                    parentCount++;
                }
            }
            if(parentCount === 1) {
                this._renderFirstLevelChildren(parentCategory, categoryType, explorerDiv);
            }
            else {
                explorerDiv.get('childNodes').remove();
                var explorer = this._createCategoryListNode(category, this.data.attrs.filter_type);
                if(explorer){
                    if(!explorer.hasChildNodes())
                        explorer = this.Y.Node.create('<div class="rn_NoCategoriesMsg">' + this.data.js.noDataFoundMessage + '</div>');
                    explorerDiv.append(explorer);
                    explorerDiv.addClass('rn_HiddenExplorer');
                    explorer.replaceClass('rn_CategoryExplorerListHidden', 'rn_CategoryExplorerList');
                }
            }
        }
        return false;
    },

    /** This method renders first level children
    *   @param {Object} parentCategory parent category
    *   @param {String} categoryType category type
    *   @param {Object} explorerDiv explorer container
    */
    _renderFirstLevelChildren: function(parentCategory, categoryType, explorerDiv) {
        var eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: parentCategory.referenceKey, limit: this.data.js.limit }});
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response, args){
                var children = [];
                this._hasMoreLink = response.hasMore;
                response = response.items;
                for (var key in response) {
                    if (response[key].externalType === categoryType) {
                        response[key].depth = 0;
                        response[key].type = this.data.attrs.filter_type;
                        children[children.length] = response[key];
                    }
                }
                if(this._hasMoreLink) {
                    var moreChildCategory = {
                                                'referenceKey' : 'MoreLink',
                                                'name' : this.data.js.moreLinkLabel,
                                                'selectedClass' : 'rn_CategoryExplorerLink',
                                                'type' : this.data.attrs.filter_type,
                                                'depth' : '0'
                                            };
                    children.push(moreChildCategory);
                }
                var newContent = new EJS({text: this.getStatic().templates.explorerView}).render({children: children, widgetInstanceID: this.baseDomID, parentCategory: parentCategory, expandLevelIcon: this.data.attrs.label_expand_icon, collapseLevelIcon: this.data.attrs.label_collapse_icon});
                explorerDiv.set("innerHTML", newContent);
            },
            json: true,
            scope: this
        });
    },

    /** This method return a list of category nodes
    *   @param {list} categories category list
    *   @param {String} categoryType Product or Category
    *   @param {int} depth maximum depth of product or category
    *   @param {boolean} appendList Flag to determine if new category list is to be created or category list is to be appended
    */
    _createCategoryListNode : function(categories, categoryType, depth, appendList) {
        if(categories === undefined || categories === null) {
            return null;
        }
        var nodeList = new this.Y.NodeList(this.Y.Array.map(categories, function (category) {
                                                return this._createCategoryNode(category, categoryType, depth);
                                            }, this));
        if(this._hasMoreLink) {
            nodeList.push(this._createCategoryNode({externalType: categoryType.toUpperCase(), hasChildren: false, referenceKey: 'MoreLink', name: this.data.js.moreLinkLabel}, categoryType, depth));
            this._hasMoreLink = false;
        }
        if(!appendList)
            return this.Y.Node.create('<ul class="rn_CategoryExplorerListHidden"></ul>').append(nodeList);
        else
            return nodeList;
    },

    /** This method category a node element.
    * @param {object} category object
    * @param {String} categoryType Product or Category
    * @param {int} depth maximum depth of product or category
    */
    _createCategoryNode : function(category, categoryType, depth) {
        var currentCategoryType = (category.externalType === 'PRODUCT' ? 'Product' : 'Category');
        var item = this.Y.Node.create('<b>'), 
            id = this.baseDomID + "_" + category.referenceKey;
        if(currentCategoryType === categoryType) {
            if (!category.hasChildren)
                var categoryLink = this.Y.Node.create('<a href="javascript:void(0)" class="rn_LeafNode rn_CategoryExplorerLink" id="' + id + '" data-id="' + category.referenceKey + '" data-depth="' + depth + '" data-type="' + categoryType + '">' + category.name + '</a>');
            else
                var categoryLink = this.Y.Node.create('<a href="javascript:void(0)" class="rn_CategoryExplorerLink" id="' + id + '" data-id="' + category.referenceKey + '" data-depth="' + depth + '" data-type="' + categoryType + '">' + category.name + '</a>');
            if (!category.hasChildren)
                item = this.Y.Node.create('<li class="rn_CategoryExplorerItem"><div class="rn_CategoryExplorerLeaf"></div><a role="button" id="' + id + '_Collapsed" class="rn_CategoryExplorerCollapsedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly">' + this.data.attrs.label_expand_icon + '</span></a></li>');
            else
                item = this.Y.Node.create('<li class="rn_CategoryExplorerItem"><a role="button" id="' + id + '_Expanded" class="rn_CategoryExplorerExpandedHidden" href="javascript:void(0)"><span class="rn_ScreenReaderOnly">' + this.data.attrs.label_expand_icon + '</span></a><a role="button" id="' + id + '_Collapsed" class="rn_CategoryExplorerCollapsed" href="javascript:void(0)"><span class="rn_ScreenReaderOnly">' + this.data.attrs.label_collapse_icon + '</span></a></li>');
            item.append(categoryLink);
        }
        return category.children ? item.append(this._createCategoryListNode(category.children)) : item;
    },

    /**
     * Event handler when a node is expanded.
     * Requests the next sub-level of items from the server.
     * @param {object} event Event
     * @param {object} category The node that's expanding
     */
    _expandCategory: function(event, category) {
        var collapsedLink = event.target,
            selectedCategoryID = collapsedLink.getAttribute('id').replace("_Collapsed", ""),
            expandedLink = this.Y.one("#" + selectedCategoryID + "_Expanded"),
            categoryLink = this.Y.one("#" + selectedCategoryID);
        
        collapsedLink.setAttribute('class', 'rn_CategoryExplorerCollapsedHidden');
        expandedLink.setAttribute('class', 'rn_CategoryExplorerExpanded');
        if (categoryLink.next()) {
            categoryLink.next().setAttribute('class', 'rn_CategoryExplorerList');
            this.Y.all('.rn_CategoryExplorerExpanded').on("click", this._collapseCategory, this);
            return false;
        }
        //only allow one node at-a-time to be expanded
        if (this._nodeBeingExpanded || (categoryLink.expanded && !this.data.js.linkingOn)) return;
        this._nodeBeingExpanded = true;
        if (!categoryLink.dynamicLoadComplete || this.data.js.linkingOn) {
            RightNow.Event.fire("evt_pageLoading");
            var eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: categoryLink.getAttribute('data-id'), limit: this.data.js.limit, offset: 0}});
            RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
                successHandler: function(response,args){
                    this._hasMoreLink = response.hasMore;
                    response = response.items;
                    if(this.Y.one(this.baseSelector + "_Error"))
                        this.Y.one(this.baseSelector + "_Tree").removeChild(this.Y.one(this.baseSelector + "_Error"));
                    if(!response) {
                        RightNow.Event.fire("evt_pageLoaded");
                        collapsedLink.setAttribute('class', 'rn_CategoryExplorerCollapsed');
                        expandedLink.setAttribute('class', 'rn_CategoryExplorerExpandedHidden');
                        return;
                    }
                    response = [{"data": {"data_type" : categoryLink.getAttribute('data-type').toString(), "hier_data" : response, "label" : categoryLink.getHTML().toString(), "linking_on" : 0, "linkingProduct" : 0, "value" : categoryLink.getAttribute('data-id').toString(), "reset" : false, "level" : parseInt(categoryLink.getAttribute('data-depth').toString(), 10) + 1}}];
                    this._getSubLevelResponse(null, response, true);
                    if(categoryLink.getAttribute('data-type').toString() === 'Product'){
                        var productParam = RightNow.Url.getParameter('productRecordID');
                        if(this.Y.one('[data-id="' + productParam + '"][data-type="Product"]')){
                            this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').removeClass('rn_CategoryExplorerLink');
                            this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').addClass('rn_CategoryExplorerLinkSelected');
                        }
                    }
                    else if(categoryLink.getAttribute('data-type').toString() === 'Category'){
                        var categoryParam = RightNow.Url.getParameter('categoryRecordID');
                        if(this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]')){
                            this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').removeClass('rn_CategoryExplorerLink');
                            this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').addClass('rn_CategoryExplorerLinkSelected');
                        }
                    }
                    RightNow.Event.fire("evt_pageLoaded");
                },
                json: true,
                scope: this
            });
        }
        this._nodeBeingExpanded = false;
        return false;
    },

    /** Method called to collapse a category
    * @param {object} event object
    * @param {object} category object
    */
    _collapseCategory: function(event, category) {
        var expandedLink = event.target,
            categoryID = expandedLink.getAttribute('id').replace("_Expanded", ""),
            categoryLink = this.Y.one("#" + categoryID),
            collapsedLink = this.Y.one("#" + categoryID + "_Collapsed");
        expandedLink.setAttribute('class', 'rn_CategoryExplorerExpandedHidden');
        collapsedLink.setAttribute('class', 'rn_CategoryExplorerCollapsed');
        categoryLink.next().setAttribute('class', 'rn_CategoryExplorerListHidden');
    },
    
    /** Method called to get more child categories
    * @param (object) event Click event object
    */
    _getMoreChildCategories: function(event) {
        var nodeList = event.target._node.parentElement.parentElement;
        var offset = nodeList.childElementCount - 1;
        var categoryLink = nodeList.previousElementSibling;
        if(!categoryLink) {
            categoryLink = nodeList.children[0].children[2];
            offset = nodeList.childElementCount;
        }
        var eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: categoryLink.getAttribute('data-id'), limit: this.data.js.limit, offset: offset}});
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response,args){
                this._hasMoreLink = response.hasMore;
                response = response.items;
                if(!response) {
                    RightNow.Event.fire("evt_pageLoaded");
                    return;
                }
                response = [{"data": {"data_type" : categoryLink.getAttribute('data-type').toString(), "hier_data" : response, "label" : categoryLink.text, "linking_on" : 0, "linkingProduct" : 0, "value" : categoryLink.getAttribute('data-id').toString(), "reset" : false, "level" : parseInt(categoryLink.getAttribute('data-depth').toString(), 10) + 1}}];
                var nodeList = this.Y.one(categoryLink).siblings().item(2)._node;
                if(nodeList.childNodes.length !== nodeList.children.length)
                    nodeList.removeChild(nodeList.children.item(nodeList.children.length - 1));
                else
                    nodeList.removeChild(nodeList.childNodes.item(nodeList.childNodes.length - 1));
                this._getSubLevelResponse(null, response, false);
                if(categoryLink.getAttribute('data-type').toString() === 'Product'){
                    var productParam = RightNow.Url.getParameter('productRecordID');
                    if(this.Y.one('[data-id="' + productParam + '"][data-type="Product"]')){
                        this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').removeClass('rn_CategoryExplorerLink');
                        this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').addClass('rn_CategoryExplorerLinkSelected');
                    }
                }
                else if(categoryLink.getAttribute('data-type').toString() === 'Category'){
                    var categoryParam = RightNow.Url.getParameter('categoryRecordID');
                    if(this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]')){
                        this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').removeClass('rn_CategoryExplorerLink');
                        this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').addClass('rn_CategoryExplorerLinkSelected');
                    }
                }
                RightNow.Event.fire("evt_pageLoaded");
            },
            json: true,
            scope: this
        });
    },
    
    /** Method called to get more top level products and categories
    * @param (object) event Click event object
    */
    _getMoreProdCat: function(event) {
        RightNow.Event.fire("evt_pageLoading");
        var eventObject = new RightNow.Event.EventObject(this, {data: { getMoreProdCatFlag: 'getMoreProdCat', contentType: this.data.js.contentType, offset: this._currentDisplayedCategoryCount, productCategoryApiVersion: this.data.js.productCategoryApiVersion}});
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response,args){
                if(!response) {
                    RightNow.Event.fire("evt_pageLoaded");
                    return;
                }
                this._currentDisplayedCategoryCount += 100;
                if(!response.hasMore){
                    this.Y.one(this.baseSelector + ">a.rn_GetMoreProdCatLink").setStyle('display', 'none');
                }
                response = response.items;
                var i = 0;
                while (i < response.length) {
                    if(response[i].externalType !== this.data.attrs.filter_type.toUpperCase()) {
                        response.splice(i, 1);
                    }
                    else {
                        i++;
                    }
                }
                var nodeList = this.Y.one(this.baseSelector + ">div.rn_CategoryExplorer>div.rn_CategoryExplorerContent>div.rn_CategoryExplorerContentDiv>ul.rn_CategoryExplorerList");
                var categoryLink = nodeList._node.children.item(0).children.item(2);
                if (response.length !== 0) {
                    var categoryListNode = this._createCategoryListNode(response, categoryLink.getAttribute('data-type'), 0, true);
                    nodeList.appendChild(categoryListNode);
                }
                if(categoryLink.getAttribute('data-type').toString() === 'Product'){
                    var productParam = RightNow.Url.getParameter('productRecordID');
                    if(this.Y.one('[data-id="' + productParam + '"][data-type="Product"]')){
                        this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').removeClass('rn_CategoryExplorerLink');
                        this.Y.one('[data-id="' + productParam + '"][data-type="Product"]').addClass('rn_CategoryExplorerLinkSelected');
                    }
                }
                else if(categoryLink.getAttribute('data-type').toString() === 'Category'){
                    var categoryParam = RightNow.Url.getParameter('categoryRecordID');
                    if(this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]')){
                        this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').removeClass('rn_CategoryExplorerLink');
                        this.Y.one('[data-id="' + categoryParam + '"][data-type="Category"]').addClass('rn_CategoryExplorerLinkSelected');
                    }
                }
                RightNow.Event.fire("evt_pageLoaded");
            },
            json: true,
            scope: this
        });
    },

    /** Method called to apply or remove category filter.
    * @param {object} event object
    * @param {object} category object
    * @param {string} categoryType product or category
    */
    _categoryClick: function(event, category, categoryType) {
        if (!(this.data.attrs.view_type === 'tree' && this._source_id === 'OKCSSearch' && !this.data.attrs.search_on_select)) {
            RightNow.Event.fire("evt_pageLoading");
        }
        if(event && event.target.getAttribute('data-id') === 'MoreLink') {
            this._getMoreChildCategories(event);
            return false;
        }
        this.searchSource().initialFilters = {};
        var productParam = RightNow.Url.getParameter('productRecordID');
        var categoryParam = RightNow.Url.getParameter('categoryRecordID');
        this._selectedProductRecordID = '';
        this._selectedCategoryRecordID = '';
        if (categoryType !== undefined) {
            this._currentSelectedID = category === 0 ? '' : category;
            if(categoryType === 'Product') {
                this._isProductSelected = true;
                this._selectedProductRecordID = this._currentSelectedID;
            }
            else {
                this._isCategorySelected = true;
                this._selectedCategoryRecordID = this._currentSelectedID;
            }

            RightNow.ActionCapture.record('okcsa-browse', categoryType === 'Product' ? 'product' : 'category', category);
            if (!(this.data.attrs.view_type === 'tree' && this._source_id === 'OKCSSearch' && !this.data.attrs.search_on_select)) {
                this.searchSource().fire("collect").fire("search", new RightNow.Event.EventObject(this, {data: {sourceCount: 1}}));
            }
            return false;
        }

        var node = event.target,
            categorySelected = node.hasClass('rn_CategoryExplorerLink') ? true : false,
            categoryType = node.getAttribute('data-type'),
            categoryNodes = this.Y.all("a[data-type='" + categoryType + "']"),
            categoryID = node.getAttribute('data-id');

        categoryNodes.removeClass('rn_CategoryExplorerLinkSelected');
        categoryNodes.addClass('rn_CategoryExplorerLink');

        if (categoryID !== 0) {
            if(categoryType === 'Product') {
                this.productSelected = categorySelected ? true : false;
                this.categorySelected = false;
                if(productParam === null) {
                    this._isProductSelected = categorySelected;
                    this._selectedProductRecordID = categoryID;
                }
                else if(productParam !== categoryID && productParam !== null) {
                    productParam = categoryID;
                    this._isProductSelected = categorySelected;
                    this._selectedProductRecordID = categoryID;
                } 
                else {
                    productParam = null;
                    this._selectedProductRecordID = '';
                    this._isProductSelected = false;
                }
            }
            else {
                this.productSelected = false;
                this.categorySelected = categorySelected ? true : false;
                if(categoryParam === null) {
                    this._isCategorySelected = categorySelected;
                    this._selectedCategoryRecordID = categoryID;
                }
                else if(categoryParam !== categoryID && categoryParam !== null) {
                    categoryParam = categoryID;
                    this._isCategorySelected = categorySelected;
                    this._selectedCategoryRecordID = categoryID;
                }
                else {
                    categoryParam = null;
                    this._selectedCategoryRecordID = '';
                    this._isCategorySelected = false;
                }
            }
            this._currentSelectedID = categoryID;
            if(this._source_id === 'OKCSBrowse')
            {
                if(productParam !== null) {
                    RightNow.Url.addParameter(window.location.href,'productRecordID',productParam);
                    RightNow.Url.addParameter(window.location.href,'isProductSelected','true');
                    if (!('pushState' in window.history))
                        this._selectedProductRecordID = productParam;
                    this._isProductSelected = true;
                }
                if(categoryParam !== null) {
                    RightNow.Url.addParameter(window.location.href,'categoryRecordID',categoryParam);
                    RightNow.Url.addParameter(window.location.href,'isCategorySelected','true');
                    this._isCategorySelected = true;
                    if (!('pushState' in window.history))
                        this._selectedCategoryRecordID = categoryParam;
                }

                this.searchSource().fire("collect").fire("search", new RightNow.Event.EventObject(this, {data: {sourceCount: 1}}));
            }
        }
        if(this._source_id === 'OKCSSearch') {
            var node = this.Y.one("a[data-id='" + categoryID + "']"),
                selectedClass = node.hasClass('rn_CategoryExplorerLinkSelected') ? 'rn_CategoryExplorerLink' : 'rn_CategoryExplorerLinkSelected';
            node.setAttribute('class', selectedClass);
        }
        if (this.data.attrs.toggle_selection && this.data.attrs.item_to_toggle)
            this._onToggle(this);

        return false;
    },

    /**
     * Event handler when returning from ajax data request
     * @param {string} type Event name
     * @param {array} args Event arguments
     * @param {boolean} createNewNode Flag to determine if new node is to be created to populate the response
     */
    _getSubLevelResponse: function(type, args, createNewNode) {
        var evtObj = args[0];
        if(evtObj.data.value !== undefined) {
            var selectedNode = this.Y.one("a[data-id='" + evtObj.data.value + "']"),
                categoryType = selectedNode.getAttribute('data-type'),
                parentNode = selectedNode.get('parentNode'),
                hierarchicalData = evtObj.data.hier_data,
                depth = evtObj.data.level - 1;
            if (hierarchicalData !== undefined) {
                var categoryListNode = this._createCategoryListNode(hierarchicalData, categoryType, depth, !createNewNode);
                if(categoryListNode){
                    categoryListNode.setAttribute('class', 'rn_CategoryExplorerList');
                    if(createNewNode) {
                        parentNode.appendChild(categoryListNode);
                    }
                    else {
                        parentNode = parentNode._node.lastElementChild;
                        for(i = 0; i < categoryListNode._nodes.length; i++) {
                            parentNode.appendChild(categoryListNode._nodes[i]);
                        }
                    }
                }
            }
            return false;
        }
         if (this._restorationHierArray) {
            //If this._restorationHierArray is set, then prod/cat linking and history management are both in use.
            //Use this._restorationHierArray to restore the value and select the node.
            var hierArray = this._restorationHierArray;
            this._restorationHierArray = null;
            this._expandAndCreateNodes(hierArray);
            tempNode = this._tree.getNodeByProperty("hierValue", parseInt(hierArray[hierArray.length - 1], 10));
            if (tempNode)
                this._selectNode({node: tempNode});
        }
    },

    /**
     * Event handler when report has been updated
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    _onReportResponse: function(type, args) {
        var data = RightNow.Event.getDataFromFiltersEventResponse(args, this.data.js.searchName);
        this._getFiltersRequest.cachedWidgetHier = {};
        if(data[0] && data[0].length) {
            if(typeof data[0] === "string")
                data[0] = data[0].split(",");
            var finalData = RightNow.Lang.arrayFilter(data[0]);
            var fromHistoryManager = args && args[0] && args[0].data && args[0].data.fromHistoryManager;
            this._expandAndCreateNodes(finalData, fromHistoryManager);
            this._eo.filters.data[0] = finalData;
            this._lastSearchValue = finalData.slice(0);
            if(this._eo.filters.data.reconstructData) {
                this._eo.filters.data.level = this._eo.filters.data.reconstructData.level;
                this._eo.filters.data.label = this._eo.filters.data.reconstructData.label;
            }
        }
        else {
            //always set back to empty array since search eventbus may have inadvertantly set it to null...
            this._eo.filters.data[0] = [];
            if(this._tree) {
                //going from some selection back to no selection
                this._currentIndex = this._noValueNodeIndex;
                this._displaySelectedNodesAndClose();
            }
        }
    },
    
    
    /**
    * Displays the hierarchy of the currently selected node up to its root node,
    * hides the panel, and focuses on the selection button (if directed).
    * @param {Boolean} focus Whether or not the button should be focused
    */
    displaySelectedNodesAndClose: function(focus) {
        this._eo.data.hierChain = this.tree.get('valueChain');
        RightNow.Event.fire("evt_productCategoryFilterSelected", this._eo);
        delete this._eo.data.hierChain;

        RightNow.ProductCategory.prototype.displaySelectedNodesAndClose.call(this, focus);
    },

    /**
    * Selected a node by clicking on its label
    * (as opposed to expanding it via the expand image).
    * @param {object} node The node
    */
    selectNode: function(node) {
        if(node.value === this.baseSelector + "_MoreLink") {
            // Invocation to categories call to fetch more children
            this.isMoreLinkClicked = true;
            var depth = node.depth - 1;
            this.Y.one("#" + node.currentTarget._tree.getNodeByIndex(node.details[0].index).labelElId).addClass("rn_moreLink ygtvloading ");
            this.moreLeafNode = node;
            this.moreLeafNode.currentTarget._tree.getNodeByIndex(this.moreLeafNode.index);
            if(this.moreCategMap[node.valueChain[depth]]['offset'] === undefined) {
                this.moreCategMap[node.valueChain[depth]]['offset'] = this.data.js.limit;
            }
            else {
                this.moreCategMap[node.valueChain[depth]]['offset'] += this.data.js.limit;
            }
            this.getSubLevelRequest(node);
        }
        else {
            this.selectNode._selectedWidget = this.data.info.w_id;
            this.tree.collapseAll();
            RightNow.ProductCategory.prototype.selectNode.call(this, node);
            var nodeValue = this._source_id === 'OKCSSearch' ? node.valueChain.join('.') : node.value;
            this._categoryClick(null, nodeValue, this.data.attrs.filter_type);
        }
    },

    /**
     * Called when a node with unloaded children is to be expanded.
     * @param  {object} expandingNode The node that's expanding
     */
    getSubLevelRequest: function (expandingNode) {
        var eventObject;
        if(expandingNode.value === this.baseSelector + "_MoreLink") {
            // Categories call to fetch more children
            var refKey = expandingNode.valueChain[expandingNode.depth - 1];
            this.moreCategMap[refKey]['childrenRendered'] = true;
            eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: refKey , offset: this.moreCategMap[refKey]['offset'], limit: this.data.js.limit}});
        }
        else {
            var refKey = expandingNode.valueChain[expandingNode.depth];
                if(this.moreCategMap[refKey] === undefined) {
                    this.moreCategMap[refKey] = new Object();
                    if(this.moreCategMap[refKey]['childrenRendered'] === undefined)
                        this.moreCategMap[refKey]['childrenRendered'] = false;
                }
                if(this.moreCategMap[refKey]['childrenRendered'])
                    return;
            this.moreCategMap[refKey]['parentNode'] = new Object();
            this.moreCategMap[refKey]['parentNode'] = expandingNode;
            eventObject = new RightNow.Event.EventObject(this, {data: { categoryId: expandingNode.value , offset: 0, limit: this.data.js.limit}});
        }
        var data_type = this._dataType;
        RightNow.Ajax.makeRequest(this.data.attrs.get_okcs_data_ajax, eventObject.data, {
            successHandler: function(response,args){
                response = this._convertData(response);
                response = [{"data": {"data_type" : data_type, "current_root" : this.moreCategMap[refKey]['parentNode'].value, "hier_data" : response, "label" : expandingNode.label, "linking_on" : 0, "linkingProduct" : 0, "value" : expandingNode.hierValue, "reset" : false, "level" : expandingNode.depth + 1}}];
                this.getSubLevelResponse(null, response);
            },
            json: true,
            scope: this
        });
        // Remove link_map from this._eo so this widget does not misinform the Event Bus
        // or other widgets about the link_map on subsequent requests.
        if(this._eo.data.link_map)
            delete this._eo.data.link_map;
        return false;
    },
    
    /** This method is called when response event is fired..
    * @param {object} obj response object
    * @param {object} evt Event
    */
    _handleResponse: function(obj, evt) {
        if(this.data.attrs.view_type === 'explorer') {
            if (evt[0].data.category !== undefined) {
                this.data.js.contentType = evt[0].data.selectedChannel;
                this._currentDisplayedCategoryCount = 100;
                this._buildExplorer(evt[0].data.category);
            }
            else {
                var categoryReferenceKey = evt[0].data.categoryRecordID
                    categorySelected = evt[0].data.isCategorySelected;
                if (this.categorySelected) {
                    var node = this.Y.one("a[data-id='" + this._selectedCategoryRecordID + "']");
                    if(node && node.getAttribute('data-type') === 'Category'){
                        node.removeClass('rn_CategoryExplorerLink');
                        node.addClass('rn_CategoryExplorerLinkSelected');
                        this._setScreenReaderProductCategory(node);
                    }
                    RightNow.Event.fire("evt_pageLoaded");
                    this._requestInProgress = false;
                }
                else if (this.productSelected) {
                    var node = this.Y.one("a[data-id='" + this._selectedProductRecordID + "']");
                    if(node && node.getAttribute('data-type') === 'Product'){
                        node.removeClass('rn_CategoryExplorerLink');
                        node.addClass('rn_CategoryExplorerLinkSelected');
                        this._setScreenReaderProductCategory(node);
                    }
                    RightNow.Event.fire("evt_pageLoaded");
                    this._requestInProgress = false;
                }
                else if (categorySelected === undefined) {
                    this.Y.all('.rn_CategoryExplorerLinkSelected[data-type="Category"]').removeClass('rn_CategoryExplorerLinkSelected');
                }
            }
        }
        else if (evt[0].data.category !== undefined) {
            var categories = this._convertData(evt[0].data.category),
                category = { id : 0, label : this.data.attrs.label_all_values };
            categories.unshift(category);
            this.data.js.hierData[0] = categories;
            this._displayFieldVisibleText.setHTML(this.data.attrs.label_nothing_selected);
            this.tree = false;
            this.buildTree(true);
        }
    },

    /**
     * Inserts the given child node data for the node with the given value
     * @param  {Array} hierData    Child data
     * @param  {string|number} currentRoot ID / value of the parent node
     */
    insertChildrenForNode: function (hierData, currentRoot) {
        this.insertChildHierarchyData(hierData, currentRoot);
    },

    /**
     * Builds and inserts child nodes for the node with the given value.
     * @param  {Array} hierData          Child node data
     * @param  {number|string} valueOfParentNode ID / value of parent node
     * @return {boolean} Whether the operation succeeded
     */
    insertChildHierarchyData: function (hierData, valueOfParentNode) {
        var parent = valueOfParentNode ? this._getNodeByValue(valueOfParentNode) : this._getRoot();
        if(this.isMoreLinkClicked) {
            parent.dynamicLoadComplete = false;
            this.Y.one(".ygtvcell.ygtvln.ygtvfocus a.ygtvspacer").detachAll("blur");
            this.moreLeafNode.currentTarget._tree.removeNode(this.moreLeafNode.currentTarget._tree.getNodeByIndex(this.moreLeafNode.index),destroy = true);
            this.moreLeafNode = null;
            this.isMoreLinkClicked = false;
        }
        if (!parent || parent.dynamicLoadComplete) return false;

        this.tree._insertNodes(hierData, parent, []);

        return true;
    },

    /**
     * Gets a node by its ID / value.
     * @param  {string} value Node value
     * @return {Object} Y.apm.MenuNode
     */
    _getNodeByValue: function (value) {
        return this.tree._tree.getNodeByProperty('hierValue', value);
    },

    /**
     * Event handler when returning from ajax data request
     * @param {string} type Event name
     * @param {array} args Event arguments
     */
    getSubLevelResponse: function(type, args) {
        var evtObj = args[0];

        // delete link_map if we have not already so that we don't send stale data
        if(this.data.js.link_map)
            delete this.data.js.link_map;

        var hierLevel = evtObj.data.level,
            hierData = evtObj.data.hier_data,
            currentRoot = evtObj.data.current_root;

        if(!evtObj.data.reset_linked_category && this.getSubLevelRequestEventObject._origRequest &&
            this.getSubLevelRequestEventObject._origRequest[this.dataType]) {
            currentRoot = this.getSubLevelRequestEventObject._origRequest[this.dataType];
        }
        else if(evtObj.data.reset_linked_category) {
            //prod linking : data's being completely reset
            this.tree.clear(this.data.attrs.label_all_values);
            this.dialog = null;

            //since the data's being reset, reset the button's label
            this.dropdown.set('triggerText', this.data.attrs.label_nothing_selected);
            this.Y.all(this.baseSelector + "_TreeDescription").setHTML(this.data.attrs.label_nothing_selected);
        }

        if (hierLevel < 7) {
            //add the new nodes to the currently selected node
            this.insertChildrenForNode(hierData, currentRoot);
        }
        if (hierData.length === 0) {
            //leaf node was expanded : display and close
            this.displaySelectedNodesAndClose();
        }
        if (this._restorationHierArray) {
            //If this._restorationHierArray is set, then prod/cat linking and history management are both in use.
            //Use this._restorationHierArray to restore the value and select the node.
            var hierArray = this._restorationHierArray;
            this._restorationHierArray = null;
            this.tree.expandAndCreateNodes(hierArray);
            var tempNode = this.tree.getNodeByValue(hierArray[hierArray.length - 1]);
            if (tempNode)
                this.selectNode({node: tempNode});
        }
    },

    /**
     * Returns the filter's value in response to a collect event.
     * @return {object} EventObject
     */
    collectFilters: function() {
        return this._eo;
    },

    /**
     * Function for setting screenreader label for selected product/category
     * @param {object} node DOM node to set screen reader label
     */
    _setScreenReaderProductCategory: function(node) {
        var categoryType = node.getAttribute('data-type');
        this.Y.all("a[type='" + categoryType + "'] .rn_ScreenReadProductSelected").remove();
        this.Y.all("a[type='" + categoryType + "'] .rn_ScreenReadCategorySelected").remove();
        var categoryName = node.getHTML();

        if(node.hasClass('rn_CategoryExplorerLinkSelected')){
            if(categoryType === 'Category')
                node.append('<span class="rn_ScreenReadCategorySelected rn_ScreenReaderOnly">' + RightNow.Text.sprintf(this.data.attrs.label_category_selected, categoryName) + '</span>');
            else if(categoryType === 'Product')
                node.append('<span class="rn_ScreenReadProductSelected rn_ScreenReaderOnly">' + RightNow.Text.sprintf(this.data.attrs.label_product_selected, categoryName) + '</span>');
        }
    },

    /**
    * This function converts okcs data into tree structure
    * @param {object} results response object
    * @return {object} Category data into tree structure
    */
    _convertData: function(results) {
        var convertedObject = [];
        for(var i = 0, j = 0; i < results.items.length; i++) {
        this._hasMoreLink = results.hasMore;
            var validCategoryFlag = ((results.items[i].externalType === 'PRODUCT' && this.data.attrs.filter_type === "Product") ||
                                    ((!results.items[i].externalType || results.items[i].externalType === 'CATEGORY') && this.data.attrs.filter_type === 'Category')) ? true : false;
            if(validCategoryFlag) {
                var category = {
                    id : results.items[i].referenceKey,
                    label : results.items[i].name,
                    hasChildren : results.items[i].hasChildren,
                    selected : false
                };
                convertedObject[ j++ ] = category;
            }
        }
        if(this._hasMoreLink) {
            moreCategory = { id : this.baseSelector + "_MoreLink", label : this.data.js.moreLinkLabel }
            convertedObject[ j++ ] = moreCategory;
        }
        return convertedObject;
    },

    /**
    * This funciton reset okcs filters.
    */
    _resetOkcsFilterRequest: function() {
        this.searchSource().initialFilters = {};
        this._isProductSelected = false;
        this._isCategorySelected = false;
        this._selectedProductRecordID = '';
        this._selectedCategoryRecordID = '';
        this._currentSelectedID = '';
    },

    /**
    * Collects isProductSelected filter
    * @return {object} Event object
    */
    _updateIsProductSelected: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._isProductSelected, key: 'isProductSelected', type: 'isProductSelected'}
        });
    },

    /**
    * Collects isCategorySelected filter
    * @return {object} Event object
    */
    _updateIsCategorySelected: function() {
        return new RightNow.Event.EventObject(this, {
            data: {value: this._isCategorySelected, key: 'isCategorySelected', type: 'isCategorySelected'}
        });
    },

    /**
    * Collects productRecordID filter
    * @return {object} Event object
    */
    _updateProductRecordID: function() {
        var productKey = this._source_id === 'OKCSSearch' ? 'product' : 'productRecordID';
        return new RightNow.Event.EventObject(this, {
            data: {value: this._selectedProductRecordID, key: productKey, type: productKey}
        });
    },

    /**
    * Collects categoryRecordID filter
    * @return {object} Event object
    */
    _updateCategoryRecordID: function() {
        var categoryKey = this._source_id === 'OKCSSearch' ? 'category' : 'categoryRecordID';
        return new RightNow.Event.EventObject(this, {
            data: {value: this._selectedCategoryRecordID, key: categoryKey, type: categoryKey}
        });
    },

    /**
    * Collects currentSelectedID filter
    * @return {object} Event object
    */
    _updateCurrentSelectedID: function() {
        if(this._currentSelectedID)
            return new RightNow.Event.EventObject(this, {
                data: {value: this._currentSelectedID, key: 'currentSelectedID', type: 'currentSelectedID'}
            });
    },
    
    /**
     * Sets the event object for subsequent searches.
     */
    initializeEventObject: function() {
        this._eo = new RightNow.Event.EventObject(this, {
            data: this.Y.merge(this.data.js.filter, {
                data_type:      this.dataType = this.data.attrs.filter_type,
                new_page:       this.data.attrs.search_results_url,
                linking_on:     this.data.js.linkingOn,
                hm_type:        this.data.js.hm_type,
                cache:          [],
                value:          this.dataType,
                key:            this.dataType,
                type:           this.dataType
            })
        });

        if (this.dataType === "Product") {
            // Set namespace global for hier menu list linking display
            RightNow.UI.Form.currentProduct = this.data.js.initial[this.data.js.initial.length - 1];
        }
    },

    /**
     * Updates the text for the ARIA alert div that appears above browse header
     * @param {String} text The text to update the div with
     */
    _updateAriaAlert: function(text) {
        this._ariaAlert = this.Y.one('#' + this._alertId);
        if(this._ariaAlert) {
            this._ariaAlert.set('innerHTML','<b>' + text + '</b>');
        }
     }
});
