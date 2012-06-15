// this is only for netbeans ;) the navigator has a problem with getter and setter
function extendXOJView() {
    XOJView.pageFirstSet = true;

    XOJView.setPage = function(val)
    {
        if(!XOJView.pageFirstSet && val==this.currentPageNumber)
        {
            return;
        }
        XOJView.pageFirstSet = false;
        XOJView.resetEdit();
        
        var pages = this.pages;
        var input = document.getElementById('pageNumber');
        if (!(0 < val && val <= pages.length))
        {
            var event = $.Event('pagechange');
            event.pageNumber = this.page;
            $(window).trigger(event);
            return;
        }

        this.currentPageNumber = val;
        var eventPC = $.Event('pagechange');
        eventPC.pageNumber = val;
        $(window).trigger(eventPC);

        // checking if the this.page was called from the updateViewarea function:
        // avoiding the creation of two "set page" method (internal and public)
        if (updateViewarea.inProgress)
        {
            return;
        }

        // Avoid scrolling the first page during loading
        if (this.loading && val == 1)
        {
            return;
        }

        pages[val - 1].scrollIntoView();
    };

    XOJView.getPage = function() {
        return this.currentPageNumber;
    };

    if (typeof XOJView.__defineGetter__ != 'undefined')
    {
        XOJView.__defineSetter__('page', function(val)
        {
            return $.proxy(XOJView.setPage, XOJView)(val);
        });
        XOJView.__defineGetter__('page', function()
        {
            return $.proxy(XOJView.getPage, XOJView)();
        });
    }
    else
    {
        // IE
        Object.defineProperty(XOJView, 'page', {
            get: function() {
                return $.proxy(XOJView.getPage, XOJView)();
            },
            set: function(val) {
                return $.proxy(XOJView.setPage, XOJView)(val);
            }
        });
    }
}