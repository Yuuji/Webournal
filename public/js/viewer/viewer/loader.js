PDFJS.workerSrc = "/js/viewer/viewer/pdf.js";

extendXOJView();

var cache = new Cache(kCacheSize);
var pdfcache = new PDFCache();

$(window).bind('unload', function webViewerUnload(evt) {
    window.scrollTo(0, 0);
});

$(window).bind('scroll', function webViewerScroll(evt) {
    updateViewarea();
});

$(window).bind('transitionend', updateThumbViewArea);
$(window).bind('webkitTransitionEnd', updateThumbViewArea);

$(window).bind('resize', function webViewerResize(evt) {
    if (document.getElementById('pageWidthOption').selected ||
        document.getElementById('pageFitOption').selected)
    {
        XOJView.parseScale(document.getElementById('scaleSelect').value);
    }
    updateViewarea();
});

$(window).bind('hashchange', function webViewerHashchange(evt) {
    XOJView.setHash(document.location.hash.substring(1));
});

$(window).bind('change', function webViewerChange(evt) {
    var files = evt.target.files;
    if (!files || files.length == 0)
    {
        return;
    }

  // Read the local file into a Uint8Array.
  /*var fileReader = new FileReader();
  fileReader.onload = function webViewerChangeFileReaderOnload(evt) {
    var data = evt.target.result;
    var buffer = new ArrayBuffer(data.length);
    var uint8Array = new Uint8Array(buffer);

    for (var i = 0; i < data.length; i++)
      uint8Array[i] = data.charCodeAt(i);
    XOJView.load(uint8Array);
  };

  // Read as a binary string since "readAsArrayBuffer" is not yet
  // implemented in Firefox.
  var file = files[0];
  fileReader.readAsBinaryString(file);

  // URL does not reflect proper document location - hiding some icons.
  document.getElementById('viewBookmark').setAttribute('hidden', 'true');
  document.getElementById('download').setAttribute('hidden', 'true'); */
});

$(window).bind('scalechange', function scalechange(evt) {
    var customScaleOption = document.getElementById('customScaleOption');
    customScaleOption.selected = false;

    if (!evt.resetAutoSettings &&
        (document.getElementById('pageWidthOption').selected ||
        document.getElementById('pageFitOption').selected))
    {
        updateViewarea();
        updateLayerSelect(XOJView.page);
        return;
    }

    var options = document.getElementById('scaleSelect').options;
    var predefinedValueFound = false;
    var value = '' + evt.scale;
    for (var i = 0; i < options.length; i++)
    {
        var option = options[i];
        if (option.value != value)
        {
            option.selected = false;
            continue;
        }
        option.selected = true;
        predefinedValueFound = true;
    }

    if (!predefinedValueFound)
    {
        customScaleOption.textContent = Math.round(evt.scale * 10000) / 100 + '%';
        customScaleOption.selected = true;
    }

    updateViewarea(true);
    updateLayerSelect(XOJView.page);
});

$(window).bind('pagechange', function pagechange(evt) {
    var page = evt.pageNumber;
    if (document.getElementById('pageNumber').value != page)
    {
        document.getElementById('pageNumber').value = page;
    }
    document.getElementById('previous').disabled = (page <= 1);
    document.getElementById('next').disabled = (page >= XOJView.pages.length);

    updateLayerSelect(page);
});

$(window).bind('keydown', function keydown(evt) {
    var curElement = document.activeElement;
    var controlsElement = document.getElementById('controls');
    while (curElement)
    {
        if (curElement === controlsElement)
        {
            return; // ignoring if the 'controls' element is focused
        }
        curElement = curElement.parentNode;
    }
    switch (evt.keyCode)
    {
        case 61: // FF/Mac '='
        case 107: // FF '+' and '='
        case 187: // Chrome '+'
            XOJView.zoomIn();
            break;
        case 109: // FF '-'
        case 189: // Chrome '-'
            XOJView.zoomOut();
            break;
        case 48: // '0'
            XOJView.setScale(kDefaultScale, true);
        break;
    }
});

// Buttons

$('#previous').click(function() {
    XOJView.page--;
});

$('#previous').bind('contextmenu', function() {
    return false;
});

$('#next').click(function() {
    XOJView.page++;
});

$('#next').bind('contextmenu', function() {
    return false;
});

$('#pageNumber').bind('change', function() {
    XOJView.page = this.value;
});

$('#pageNumber').click(function() {
    XOJView.page = this.value;
});

$('#zoomOut').click(function () {
    XOJView.zoomOut();
});

$('#zoomOut').bind('contextmenu', function() {
    return false;
});

$('#zoomIn').click(function () {
    XOJView.zoomIn();
});

$('#zoomIn').bind('contextmenu', function() {
    return false;
});

$('#scaleSelect').click(function () {
    XOJView.parseScale(this.value);
});

$('#scaleSelect').bind('contextmenu', function() {
    return false;
});

$('#layerSelect').bind('change', function() {
    selectLayer(this.value);
});

$('#edit').click(function () {
    XOJView.toggleEdit();
});

$('#edit').bind('contextmenu', function() {
    return false;
});

$('#save').click(function () {
    XOJView.save();
});

$('#save').bind('contextmenu', function() {
    return false;
});


// Onload

jQuery.fn.extend({
    webournal: function(XOJLoader, XOJSaver, XOJLogin) {
        var params = document.location.search.substring(1).split('&');
        for (var i = 0; i < params.length; i++)
        {
            var param = params[i].split('=');
            params[unescape(param[0])] = unescape(param[1]);
        }

        $('#colorpatch_select').ColorPicker(
            {
                color: '000000',
                onBeforeShow: function() {
                    if(XOJView.editTextEditInProcess)
                    {
                        return false;
                    }
                    var color = new RGBColor($('#editcontrols button#colorpatch_select.colorpatch img').css('backgroundColor'));
                    $(this).ColorPickerSetColor(color.toHex());
                },
                onShow: function () {
                    if(XOJView.editTextEditInProcess)
                    {
                        return false;
                    }
                    return true;
                },
                onChange: function (hsb, hex, rgb) {
                    $('#colorpatch_select img').css('backgroundColor', '#' + hex);
                    XOJView.selectEditColor('#' + hex);
                }
            }
        );

        $('#colorpatch_select').mousedown(function() {
            if(XOJView.editTextEditInProcess)
            {
                return false;
            }
        });

        $(document).keydown($.proxy(XOJView.editKeyDown, XOJView));
        $(document).keyup($.proxy(XOJView.editKeyUp, XOJView));

        var scale = ('scale' in params) ? params.scale : kDefaultScale;
        XOJView.open(XOJLoader, XOJSaver, XOJLogin, parseFloat(scale));

        if (!window.File || !window.FileReader || !window.FileList || !window.Blob)
        {
            document.getElementById('fileInput').setAttribute('hidden', 'true');
        }
        else
        {
            document.getElementById('fileInput').value = null;
        }

        //if ('disableWorker' in params)
        //PDFJS.disableWorker = params['disableWorker'] === 'true' ? true : false;

        var sidebarScrollView = document.getElementById('sidebarScrollView');
        sidebarScrollView.addEventListener('scroll', updateThumbViewArea, true);
    }
});

$(document).trigger('webournalready');