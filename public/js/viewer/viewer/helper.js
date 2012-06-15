$.fn.selectRange = function(start, end)
{
    return this.each(function()
    {
        if (this.setSelectionRange)
        {
            this.focus();
            this.setSelectionRange(start, end);
        }
        else if (this.createTextRange)
        {
            var range = this.createTextRange();
            range.collapse(true);
            range.moveEnd('character', end);
            range.moveStart('character', start);
            range.select();
            delete range;
        }
    });
};

function isArrayBuffer(v)
{
    return typeof v == 'object' && v != null && ('byteLength' in v);
}

var PDFCache = function()
{
    var data = {};
    this.set = function(name, pdf)
    {
        data[name] = pdf;
    }
    
    this.get = function(name)
    {
        return data[name];
    }
}

var Cache = function cacheCache(size)
{
    var data = [];
    this.push = function cachePush(view)
    {
        var i = data.indexOf(view);
        if (i >= 0)
        {
            data.splice(i);
        }
        data.push(view);
        if (data.length > size)
        {
            data.shift().update();
        }
    };
    
    this.remove = function cacheRemove(view)
    {
        var i = data.indexOf(view);
        if (i >= 0)
        {
            data.splice(i);
        }
        if (data.length > size)
        {
            data.shift().update();
        }
    };
};

if(!Name)
{
    var Name = (
        function NameClosure()
        {
            function Name(name)
            {
                this.name = name;
            }

            Name.prototype = {};

            return Name;
        }
    )();
}

var loadedPages = [];

var garbageBin;
(function($)
{
    if (typeof(garbageBin) === 'undefined')
    {
        //Here we are creating a 'garbage bin' object to temporarily 
        //store elements that are to be discarded
        garbageBin = document.createElement('div');
        garbageBin.style.display = 'none'; //Make sure it is not displayed
        document.body.appendChild(garbageBin);
    }
    
    $.discard = function discardElement(element)
    {
        //The way this works is due to the phenomenon whereby child nodes
        //of an object with it's innerHTML emptied are removed from memory

        //Move the element to the garbage bin element
        garbageBin.appendChild(element);
        //Empty the garbage bin
        garbageBin.innerHTML = "";
        delete element;
    }
})(jQuery);

function clear$Cache()
{ 
 for (var n in $.cache) {
        var noElements = true;
        var o = $.cache[n];
        for (var z in o) {
            noElements = false;
            break;
        }
        if (noElements) {
            delete $.cache[n];
            delete noElements;
            delete o;
            continue;
        }
        
        delete noElements;
        
        if(o.data && o.data.canvasWidth)
        {
            delete $.cache[n];
            delete o;
            continue;
        }
        
        if(o.handle && o.handle.elem && o.handle.elem.parentNode==null)
        {
            delete $.cache[n];
            delete o;
            continue;
        }
        
        delete o;
    }
};

function updateViewarea()
{
    var oldLoadedPages = $.extend(true,[],loadedPages);
    var visiblePages = XOJView.getVisiblePages();
    
    loadedPages = [];
    var minPage = -1;
    for (var i = 0; i < visiblePages.length; i++)
    {
        var page = visiblePages[i];
        if (XOJView.pages[page.id - 1].draw())
        {
            //cache.push(page.view);
        }
        loadedPages.push(page.id);
        if(minPage===-1 || page.id<minPage)
        {
            minPage=page.id;
        }
        var idx = oldLoadedPages.indexOf(page.id);
        delete page;
        if(idx!=-1)
        {
            oldLoadedPages.splice(idx,1);
        }
        delete idx;
    }

    if(minPage>1)
    {
        var idx = oldLoadedPages.indexOf(minPage-1);
        if(idx!=-1)
        {
            oldLoadedPages.splice(idx,1);
        }
        delete idx;
    }
    delete minPage;
    
    for(id in oldLoadedPages)
    {
        XOJView.pages[oldLoadedPages[id] - 1].remove()
    }
    delete oldLoadedPages;
    clear$Cache();
    
    if (!visiblePages.length)
    {
        delete visiblePages;
        return;
    }

    updateViewarea.inProgress = true; // used in "set page"
    var currentId = XOJView.page;
    var firstPage = visiblePages[0];
    
    delete visiblePages;
    
    if(!(arguments.length>0 && arguments[0]==true))
    {
        XOJView.page = firstPage.id;
    }
    updateViewarea.inProgress = false;

    var kViewerTopMargin = 52;
    var pageNumber = currentId;
    var pdfOpenParams = '#page=' + pageNumber;
    pdfOpenParams += '&zoom=' + Math.round(XOJView.currentScale * 100);
    var currentPage = XOJView.pages[pageNumber - 1];
    var topLeft = currentPage.getPagePoint(window.pageXOffset,
    window.pageYOffset - firstPage.y - kViewerTopMargin);
    pdfOpenParams += ',' + Math.round(topLeft.x) + ',' + Math.round(topLeft.y);

    if(selectLayerCurrentValue != "")
    {
        pdfOpenParams += '&layer=' + selectLayerCurrentValue;
    }

    document.getElementById('viewBookmark').href = pdfOpenParams;
    
    delete currentId;
    delete firstPage;
    delete kViewerTopMargin;
    delete pageNumber;
    delete pdfOpenParams;
    delete currentPage;
    delete topLeft;
}

var thumbnailTimer;

function updateThumbViewArea()
{
    // Only render thumbs after pausing scrolling for this amount of time
    // (makes UI more responsive)
    var delay = 50; // in ms

    if (thumbnailTimer)
    {
        clearTimeout(thumbnailTimer);
    }

    thumbnailTimer = setTimeout(function(){
        var visibleThumbs = XOJView.getVisibleThumbs();
        for (var i = 0; i < visibleThumbs.length; i++)
        {
            var thumb = visibleThumbs[i];
            XOJView.thumbnails[thumb.id - 1].draw();
        }
        delete visibleThumbs;
        clearTimeout(thumbnailTimer);
    }, delay);
}

var selectLayerLastPage = 0;
var selectLayerLastScale = 0;
var selectLayerLastValue = -1;
var selectLayerCurrentValue = -1;
var selectLayerLastName = '';
var selectLayerWasEdit = false;

function selectLayer(value)
{
    var page = document.getElementById('pageNumber').value;
    var pageView = XOJView.pages[page-1];
    
    $(pageView.div).children('canvas.pagelayer').hide();
    $(pageView.div).children('div.textLayer').children('div.layer_textlayer').hide();
    
    if(value!=="")
    {
        try
        {
            var id = parseInt(value);
            
            if(pageView.layer[id].name)
            {
                selectLayerLastName = pageView.layer[id].name;
            }
            else if(value!='')
            {
                if(arguments.length<2 || arguments[1]!=true)
                {
                    selectLayerLastName = '';
                }
            }
            
            if(arguments.length<2 || arguments[1]!=true)
            {
                selectLayerLastValue = id;
            }
            
            var editLayerId = pageView.getEditLayer();
            if(editLayerId>-1 && editLayerId==id)
            {
                selectLayerWasEdit = true;
                $('#editcontrols').show();
            }
            else
            {
                selectLayerWasEdit = false;
                XOJView.resetEdit(true);
            }
            
            delete editLayerId;
            
            $('canvas#' + pageView.layer[id].id).show();
            $('div.textlayer_page' + page + '_layer' + value).show();
            
            delete id;
        }
        catch(e)
        {
            
        }
    }
    else
    {
        XOJView.resetEdit(true);
        selectLayerWasEdit = false;
        selectLayerLastName = '';
    }
    
    delete page;
    delete pageView;
    
    selectLayerCurrentValue = value;
    
    XOJView.selectEditCursor();
    updateViewarea(true);
}

function updateLayerSelect(page)
{
    if(page==-1)
    {
        page = selectLayerLastPage;
    }
    
    if(firstLoadSelectedLayerPage>-1 && firstLoadSelectedLayerPage!=page)
    {
        return;
    }
    
    // Layer management
    var pageView = XOJView.pages[page-1];

    if(!pageView || !pageView.layerLoaded)
    {
        delete pageView;
        return;
    }
    
    if(!(arguments.length>1 && arguments[1]) && selectLayerLastPage==page && selectLayerLastScale==pageView.scale && firstLoadSelectedLayer==-1)
    {
        return;
    }
    
    selectLayerLastPage = page;
    selectLayerLastScale = pageView.scale;

    var select = $('#layerSelect');

    select.empty();

    $('<option />').val('').html('Layer ausw&auml;hlen').appendTo(select);

    var counter = 0;
    var selectValue = '';
    for(key in pageView.layer)
    {
        counter++;

        var name = '-';
        if(pageView.layer[key].name)
        {
            name = pageView.layer[key].name;
            
            if(name==selectLayerLastName)
            {
                selectValue = key;
            }
        }

        if(pageView.layer[key].time)
        {
            name += ' (Letzte &Auml;nderung: ' + pageView.layer[key].time + ')';
        }

        $('<option />').val(key).html(counter + ': ' + name).appendTo(select);
        delete name;
    }
    
    if(selectLayerWasEdit)
    {
        selectLayerWasEdit = false;
        XOJView.edit();
        return;
    }
    else if(selectValue=="")
    {
        if(pageView.layer[selectLayerLastValue])
        {
            if(selectLayerLastName=='' && (!pageView.layer[selectLayerLastValue].name || pageView.layer[selectLayerLastValue].name==''))
            {
                selectValue = selectLayerLastValue;
            }
        }
    }
    
    if(firstLoadSelectedLayerPage==-1 || firstLoadSelectedLayerPage == page)
    {
        if(firstLoadSelectedLayer>-1)
        {
            if(pageView.layer[firstLoadSelectedLayer])
            {
                selectValue = firstLoadSelectedLayer;
            }
            
            firstLoadSelectedLayer = -1;
        }
        firstLoadSelectedLayerPage = -1;
    }

    select.children('option[value="' + selectValue + '"]').attr('selected', true);
    document.getElementById('pageNumber').value = page;
    selectLayer(selectValue.toString(), true);
    
    delete pageView;
    delete select;
}

(function() {


  /**
   *
   */
  var Selection = (function() {

  	var hasRange = (typeof document.selection !== 'undefined' && typeof document.selection.createRange !== 'undefined');

    return {

      /**
       *
       */
      getSelectionRange: function(el) {

    	  var start,
    	      end,
    	      range,
            rangeLength,
            duplicateRange,
            textRange;

    	  el.focus();

    	  // Mozilla / Safari
    	  if (typeof el.selectionStart !== 'undefined') {

    	    start = el.selectionStart;
    	    end   = el.selectionEnd;

    	  // IE
    	  } else if (hasRange) {

    	    range = document.selection.createRange();
    	    rangeLength = range.text.length;

    	    if(range.parentElement() !== el) {
    	      throw('Unable to get selection range.');
    	    }

    	    // Textarea
    	    if (el.type === 'textarea') {

    	      duplicateRange = range.duplicate();
    	      duplicateRange.moveToElementText(el);
    	      duplicateRange.setEndPoint('EndToEnd', range);

    	      start = duplicateRange.text.length - rangeLength;

    	    // Text Input
    	    } else {

    	      textRange = el.createTextRange();
    	      textRange.setEndPoint("EndToStart", range);

    	      start = textRange.text.length;
    	    }

    	    end = start + rangeLength;

    	  // Unsupported type
    	  } else {
    	    throw('Unable to get selection range.');
    	  }

    	  return {
    	    start: start,
    	    end:   end
    	  };
      },


      /**
       *
       */
    	getSelectionStart: function(el) {
        return this.getSelectionRange(el).start;
      },


      /**
       *
       */
      getSelectionEnd: function(el) {
        return this.getSelectionRange(el).end;
      },


      /**
       *
       */
      setSelectionRange: function(el, start, end) {
        
        var value,
            range;

    	  el.focus();

    	  if (typeof end === 'undefined') {
    	    end = start;
    	  }

    	  // Mozilla / Safari
    	  if (typeof el.selectionStart !== 'undefined') {

    	    el.setSelectionRange(start, end);

    	  // IE
    	  } else if (hasRange) {

          value = el.value;
    	    range = el.createTextRange();
    	    end   -= start + value.slice(start + 1, end).split("\n").length - 1;
    	    start -= value.slice(0, start).split("\n").length - 1;
    	    range.move('character', start);
    	    range.moveEnd('character', end);
    	    range.select();

    	  // Unsupported
    	  } else {
    	    throw('Unable to set selection range.');
    	  }
      },


      /**
       *
       */
      getSelectedText: function(el) {
    	  var selection = this.getSelectionRange(el);
    	  return el.value.substring(selection.start, selection.end);
      },


      /**
       *
       */
      insertText: function(el, text, start, end, selectText) {

        end = end || start;

    		var textLength = text.length,
    		    selectionEnd  = start + textLength,
    		    beforeText = el.value.substring(0, start),
            afterText  = el.value.substr(end);

    	  el.value = beforeText + text + afterText;

    	  if (selectText === true) {
    	    this.setSelectionRange(el, start, selectionEnd);
    	  } else {
    	    this.setSelectionRange(el, selectionEnd);
    	  }
      },


      /**
       *
       */
      replaceSelectedText: function(el, text, selectText) {
    	  var selection = this.getSelectionRange(el);
    		this.insertText(el, text, selection.start, selection.end, selectText);
      },


      /**
       *
       */
      wrapSelectedText: function(el, beforeText, afterText, selectText) {
    	  var text = beforeText + this.getSelectedText(el) + afterText;
    		this.replaceSelectedText(el, text, selectText);
      }

    };
  })();


  /**
   * 
   */
  window.Selection = Selection;


})();

(function($) {


  $.fn.extend({

    /**
     *
     */
    getSelectionRange: function() {
	    return Selection.getSelectionRange(this[0]);
  	},


    /**
     *
     */
  	getSelectionStart: function() {
  	  return Selection.getSelectionStart(this[0]);
  	},


    /**
     *
     */
  	getSelectionEnd: function() {
  	  return Selection.getSelectionEnd(this[0]);
  	},


    /**
     *
     */
  	getSelectedText: function() {
	    return Selection.getSelectedText(this[0]);
  	},


    /**
     *
     */
  	setSelectionRange: function(start, end) {
      return this.each(function() {
        Selection.setSelectionRange(this, start, end);
      });
  	},


    /**
     *
     */
  	insertText: function(text, start, end, selectText) {
      return this.each(function() {
        Selection.insertText(this, text, start, end, selectText);
      });
  	},


    /**
     *
     */
  	replaceSelectedText: function(text, selectText) {
      return this.each(function() {
        Selection.replaceSelectedText(this, text, selectText);
      });
  	},


    /**
     *
     */
  	wrapSelectedText: function(beforeText, afterText, selectText) {
      return this.each(function() {
        Selection.wrapSelectedText(this, beforeText, afterText, selectText);
      });
  	}
  	
  });


})(jQuery);