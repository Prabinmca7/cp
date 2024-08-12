RightNow.Widgets.SearchSuggestions = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function(){
            this.parent();

            this.searchSource().on('response', this._onSuggestionResponse, this);
            this.base = this.Y.one(this.baseSelector);
        }
    },

    /**
     * Event handler for when search suggestions were found
     * @param {String} type Event name
     * @param {Object} args Event arguments
     */
    _onSuggestionResponse: function(type, args) {
        var eventObject = args[0],
            items = {products: {filter: this.data.js.productFilter, list: eventObject.data.related_prods || []},
                     categories: {filter: this.data.js.categoryFilter, list: eventObject.data.related_cats || []}};

        if(items.products.list.length || items.categories.list.length) {
            var parameterList = (this.data.attrs.add_params_to_url)
                    ? RightNow.Url.buildUrlLinkString(args[0].filters.allFilters, this.data.attrs.add_params_to_url)
                    : '',
                suggestionsListID = this.baseDomID + '_SuggestionsList',
                suggestionsList = this.Y.one('#' + suggestionsListID) || this.Y.Node.create('<div></div>'),
                links = [], object, list;

            for (var key in items) {
                if (items.hasOwnProperty(key)) {
                    object = items[key];
                    for (var i = 0; i < object.list.length; i++) {
                        list = object.list[i];
                        if(typeof list !== 'function') {
                            links.push({href: this.data.attrs.report_page_url + '/' + object.filter + '/' + list.id + parameterList, label: list.label});
                        }
                    }
                }
            }

            suggestionsList.set('innerHTML', new EJS({text: this.getStatic().templates.view}).render({listID: suggestionsListID, links: links}));

            if (this.base) {
                this.base.append(suggestionsList)
                         .removeClass('rn_Hidden');
            }
        }
        else {
            RightNow.UI.hide(this.base);
        }
    }
});
