RightNow.Widgets.Paginator = RightNow.SearchFilter.extend({
    overrides: {
        constructor: function() {
            this.parent();

            this._currentPage = this.data.js.currentPage;

            for(var i = this.data.js.startPage; i <= this.data.js.endPage; i++)
            {
                var pageLinkID = this.baseSelector + "_PageLink_" + i;
                if(this.Y.one(pageLinkID))
                    this.Y.one(pageLinkID).on("click", this._onPageChange, this, i);
            }
            this._instanceElement = this.Y.one(this.baseSelector);
            this._cloneForwardAndBackwardButton();

            this.Y.one(this.baseSelector).delegate("click", this._onDirection, ".rn_NextPage", this, true);
            this.Y.one(this.baseSelector).delegate("click", this._onDirection, ".rn_PreviousPage", this, false);

            this._eo = new RightNow.Event.EventObject(this, {
                filters: {
                    report_id: this.data.attrs.report_id,
                    per_page: this.data.attrs.per_page,
                    page: this._currentPage
                }
            });

            this.searchSource(this.data.attrs.report_id).on("response", this._onReportChanged, this);
        }
    },

    /**
    * Event Handler fired when a page link is selected
    *
    * @param {Object} evt Event object
    * @param {Int} pageNumber Number of the page link clicked on
    */
    _onPageChange: function(evt, pageNumber)
    {
        evt.preventDefault();

        if(this._currentlyChangingPage || !pageNumber || pageNumber === this._currentPage)
            return;

        this._currentlyChangingPage = true;
        pageNumber = (pageNumber < 1) ? 1 : pageNumber;
        this._eo.filters.page = this._currentPage = pageNumber;
        if (RightNow.Event.fire("evt_switchPagesRequest", this._eo)) {
            this.searchSource().fire("appendFilter", this._eo)
                .fire("search", this._eo);
        }
    },

    /**
    * Event Handler fired when a direction button is clicked
    *
    * @param {Object} evt Event object
    * @param {Bool} isForward Indicator of button's direction
    */
    _onDirection: function(evt, isForward)
    {
        evt.preventDefault();
        if(this._currentlyChangingPage)
            return;

        this._currentlyChangingPage = true;
        if(isForward)
            this._currentPage++;
        else
            this._currentPage--;
        this._eo.filters.page = this._currentPage;

        if (RightNow.Event.fire("evt_switchPagesRequest", this._eo)) {
            this.searchSource().fire("appendFilter", this._eo)
                .fire("search", this._eo);
        }
    },

    /**
    * Event handler received when report data has changed
    *
    * @param {String} type Event type
    * @param {Object} args Arguments passed with event
    */
    _onReportChanged: function(type, args)
    {
        var newData = args[0];
        newData = newData.data;
        if(args[0].filters.report_id == this.data.attrs.report_id)
        {
            this._currentPage = newData.page;
            var totalPages = newData.total_pages;

            if(totalPages < 2 || newData.truncated)
            {
                RightNow.UI.hide(this._instanceElement);
            }
            else
            {
                //update all of the page links
                var pagesContainer = this.Y.one(this.baseSelector + " ul");
                if(pagesContainer)
                {
                    pagesContainer.set('innerHTML', "");

                    var startPage, endPage;
                    if(totalPages > this.data.attrs.maximum_page_links)
                    {
                        var split = Math.round(this.data.attrs.maximum_page_links / 2);
                        if(this._currentPage <= split)
                        {
                            startPage = 1;
                            endPage = this.data.attrs.maximum_page_links;
                        }
                        else
                        {
                            var offsetFromMiddle = this._currentPage - split;
                            var maxOffset = offsetFromMiddle + this.data.attrs.maximum_page_links;
                            if(maxOffset <= newData.total_pages)
                            {
                                startPage = 1 + offsetFromMiddle;
                                endPage = maxOffset;
                            }
                            else
                            {
                                startPage = newData.total_pages - (this.data.attrs.maximum_page_links - 1);
                                endPage = newData.total_pages;
                            }
                        }
                    }
                    else
                    {
                        startPage = 1;
                        endPage = totalPages;
                    }

                    pagesContainer.appendChild(this._backButton);

                    for(var i = startPage, link, titleString; i <= endPage; i++)
                    {
                        if(i === this._currentPage)
                        {
                            var currentPageTitle = RightNow.Text.sprintf(this.data.attrs.label_current_page, i, totalPages)
                            link = this.Y.Node.create("<span/>").addClass("rn_CurrentPage")
                                .set('innerHTML', i).set('title', currentPageTitle).set('aria-label', currentPageTitle).setAttribute('tabindex', '0');
                        }
                        else if (this._shouldShowPageNumber(i, this._currentPage, endPage))
                        {
                            link = this.Y.Node.create("<a/>").set('id', this.baseDomID + "_PageLink_" + i)
                                .set('href', this.data.js.pageUrl + i)
                                .set('innerHTML', i + '<span class="rn_ScreenReaderOnly">' + RightNow.Text.sprintf(this.data.attrs.label_page, i, totalPages) + '</span>');
                            titleString = this.data.attrs.label_page;
                            if(titleString)
                            {
                                titleString = titleString.replace(/%s/, i).replace(/%s/, newData.total_pages);
                                link.set('title', titleString);
                            }
                        }
                        else if (this._shouldShowHellip(i, this._currentPage, endPage))
                        {
                            link = this.Y.Node.create("<span/>")
                                    .set('class', 'rn_PageHellip')
                                    .set('innerHTML', "&hellip;");
                        }
                        else {
                            continue;
                        }
                        pagesContainer.appendChild(this.Y.Node.create("<li/>").append(link));
                        link.on("click", this._onPageChange, this, i);
                    }

                    pagesContainer.appendChild(this._forwardButton);

                    RightNow.UI.show(this._instanceElement);
                }
            }
            //update the back button
            if(this._backButton)
            {
                if(newData.page > 1)
                    this._backButton.removeClass("rn_Hidden").set('href', this.data.js.pageUrl + (this._currentPage - 1));
                else
                    RightNow.UI.hide(this._backButton);
            }
            //update the forward button
            if(this._forwardButton)
            {
                if(newData.total_pages > newData.page)
                    this._forwardButton.removeClass("rn_Hidden").set('href', this.data.js.pageUrl + (this._currentPage + 1));
                else
                    RightNow.UI.hide(this._forwardButton);
            }
            this._cloneForwardAndBackwardButton();
        }
        this._currentlyChangingPage = false;
    },

    /**
     * Determines if a hellip should be displayed.
     * @param {integer} pageNumber Page number to check
     * @param {integer} currentPage Current/clicked page number
     * @param {integer} endPage Last page number in the pagination
     * @return {bool} True if the hellip should be displayed
     */
    _shouldShowHellip: function(pageNumber, currentPage, endPage) {
        return Math.abs(pageNumber - currentPage) === ((currentPage === 1 || currentPage === endPage) ? 3 : 2);
    },

    /**
     * Determines if the given page number should be displayed.
     * The pagination pattern followed here is:
     *     1 ... 4 5 6 ... 12.
     * if, for example, 5 is the current/clicked page out of a total of 12 pages.
     * @param {integer} pageNumber Page number to check
     * @param {integer} currentPage Current/clicked page number
     * @param {integer} endPage Last page number in the pagination
     * @return {bool} True if the page number should be displayed.
     */
    _shouldShowPageNumber: function(pageNumber, currentPage, endPage) {
        return pageNumber === 1 || (pageNumber === endPage) || (Math.abs(pageNumber - currentPage) <= ((currentPage === 1 || currentPage === endPage) ? 2 : 1));
    },

    /**
     * Clones the current prev and next nodes to current instance variable
     */
    _cloneForwardAndBackwardButton: function() {
        this._forwardButton = this.Y.one(this.baseSelector + " .rn_NextPage").cloneNode(true);
        this._backButton = this.Y.one(this.baseSelector + " .rn_PreviousPage").cloneNode(true);
    }
});
