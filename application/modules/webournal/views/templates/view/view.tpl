<!DOCTYPE html>
<html>
    <head>
        {$this->controllerHelper()}
        <title>Webournal</title>
        <link rel="stylesheet" href="/css/viewer/viewer.css"/>
        <link rel="stylesheet" href="/css/viewer/apprise.min.css"/>
        <link rel="stylesheet" href="/css/viewer/colorpicker.css"/>
        <script type="text/javascript" src="/js/jquery.js"></script>
        <script type="text/javascript" src="/js/webournal/view/view.js"></script>
        <script type="text/javascript">
            var webournal_XOJLoadURL = '{$Webournal->url('viewxoj', 'view', 'webournal', ['id' =>  $fileId])|escape:"javascript"}';
            var webournal_XOJSaveURL = '{$Webournal->url('savexoj', 'view', 'webournal', ['id' =>  $fileId])|escape:"javascript"}';
            var webournal_XOJRestLogin = '{$Webournal->url('', 'rest_login', 'webournal', null, true)|escape:"javascript"}';
            var webournal_XOJGroup = '{$WEBOURNAL_GROUP|escape:"javascript"}';
            var webournal_XOJDomain = '{$WEBOURNAL_DOMAIN|escape:"javascript"}';
        </script>
        <script type="text/javascript" src="/js/viewer/viewer.js"></script>
        
    </head>

  <body>
    <div id="controls">
      <button id="previous">
        <img src="/images/viewer/go-up.svg" align="top" height="16"/>
        Previous
      </button>

      <button id="next">
        <img src="/images/viewer/go-down.svg" align="top" height="16"/>
        Next
      </button>

      <div class="separator"></div>

      <input type="number" id="pageNumber"value="" size="4" min="1" />

      <span>/</span>
      <span id="numPages">--</span>

      <div class="separator"></div>

      <button id="zoomOut" title="Zoom Out">
        <img src="/images/viewer/zoom-out.svg" align="top" height="16"/>
      </button>
      <button id="zoomIn" title="Zoom In">
        <img src="/images/viewer/zoom-in.svg" align="top" height="16"/>
      </button>

      <div class="separator"></div>

      <select id="scaleSelect">
        <option id="customScaleOption" value="custom"></option>
        <option value="0.5">50%</option>
        <option value="0.75">75%</option>
        <option value="1">100%</option>
        <option value="1.25">125%</option>
        <option value="1.5" selected="selected">150%</option>
        <option value="2">200%</option>
        <option id="pageWidthOption" value="page-width">Page Width</option>
        <option id="pageFitOption" value="page-fit">Page Fit</option>
      </select>

      <div class="separator"></div>
      
      <select id="layerSelect">
          <option value="">Layer ausw&auml;hlen</option>
      </select>
      <button id="edit">
          <img src="/images/viewer/edit.png" align="top" height="16"/>
          Editieren
      </button>
      <button id="save" disabled>
          <img src="/images/viewer/save.png" align="top" height="16"/>
          Speichern
      </button>
      <div class="separator"></div>

      <a href="#" id="viewBookmark" title="Bookmark (or copy) current location"><img src="/images/viewer/bookmark.svg" alt="Bookmark" align="top" height="16"/></a>
      
      <div class="separator"></div>

      <button id="print" onclick="window.print();" oncontextmenu="return false;" style="visibility: hidden">
        <img src="/images/viewer/document-print.svg" align="top" height="16"/>
        Print
      </button>

      <button id="download" title="Download" onclick="XOJView.download();" oncontextmenu="return false;" style="visibility: hidden">
        <img src="/images/viewer/download.svg" align="top" height="16"/>
        Download
      </button>

      <!-- <div class="separator"></div> -->

      <input id="fileInput" type="file" oncontextmenu="return false;" style="visibility: hidden">

      

      <span id="info">--</span>
    </div>
    <div id="editcontrols" style="display: none">
        <button id="hand" onclick="XOJView.selectEditTool('hand');" oncontextmenu="return false;" class="edittool selected">
            <img src="/images/viewer/hand.png" align="top" height="16"/>
        </button>
        <button id="pencil" onclick="XOJView.selectEditTool('pencil');" oncontextmenu="return false;" class="edittool">
            <img src="/images/viewer/pencil.png" align="top" height="16"/>
        </button>
        <button id="eraser" onclick="XOJView.selectEditTool('eraser');" oncontextmenu="return false;" class="edittool">
            <img src="/images/viewer/eraser.png" align="top" height="16"/>
        </button>
        <button id="highlighter" onclick="XOJView.selectEditTool('highlighter');" oncontextmenu="return false;" class="edittool">
            <img src="/images/viewer/highlighter.png" align="top" height="16"/>
        </button>
        <button id="texttool" onclick="XOJView.selectEditTool('texttool');" oncontextmenu="return false;" class="edittool">
            <img src="/images/viewer/text-tool.png" align="top" height="16"/>
        </button>
        <button id="ruler" onclick="XOJView.selectToggleRuler();" oncontextmenu="return false;" disabled>
            <img src="/images/viewer/ruler.png" align="top" height="16"/>
        </button>
        <div class="separator"></div>
        <button id="undo" onclick="XOJView.editUndo();" oncontextmenu="return false;" disabled>
            <img src="/images/viewer/undo.png" align="top" height="16"/>
        </button>
        <button id="redo" onclick="XOJView.editRedo();" oncontextmenu="return false;" disabled>
            <img src="/images/viewer/redo.png" align="top" height="16"/>
        </button>
        <div class="separator"></div>
        <button id="thickness_thin" onclick="XOJView.selectEditThickness('thin');" oncontextmenu="return false;" class="thickness" disabled>
            <img src="/images/viewer/thin.png" align="top" height="16"/>
        </button>
        <button id="thickness_medium" onclick="XOJView.selectEditThickness('medium')" oncontextmenu="return false;" class="thickness selected" disabled>
            <img src="/images/viewer/medium.png" align="top" height="16"/>
        </button>
        <button id="thickness_thick" onclick="XOJView.selectEditThickness('thick')" oncontextmenu="return false;" class="thickness" disabled>
            <img src="/images/viewer/thick.png" align="top" height="16"/>
        </button>
        <div class="separator"></div>
        <button id="colorpatch_black" onmousedown="XOJView.selectEditColor('black'); return false;" oncontextmenu="return false;" class="colorpatch selected" disabled>
            <img src="/images/viewer/black.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_blue" onmousedown="XOJView.selectEditColor('blue'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/blue.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_red" onmousedown="XOJView.selectEditColor('red'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/red.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_green" onmousedown="XOJView.selectEditColor('green'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/green.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_gray" onmousedown="XOJView.selectEditColor('gray'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/gray.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_lightblue" onmousedown="XOJView.selectEditColor('lightblue'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/lightblue.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_lightgreen" onmousedown="XOJView.selectEditColor('lightgreen'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/lightgreen.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_magenta" onmousedown="XOJView.selectEditColor('magenta'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/magenta.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_orange" onmousedown="XOJView.selectEditColor('orange'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/orange.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_yellow" onmousedown="XOJView.selectEditColor('yellow'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/yellow.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_white" onmousedown="XOJView.selectEditColor('white'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/white.png" align="top" height="16"/>
        </button>
        <button id="colorpatch_select" onclick="XOJView.selectEditColor('select'); return false;" oncontextmenu="return false;" class="colorpatch" disabled>
            <img src="/images/viewer/blank.gif" align="top" height="16" style="background-color: black"/>
        </button>
    </div>
    <div id="errorWrapper" hidden='true'>
      <div id="errorMessageLeft">
        <span id="errorMessage"></span>
        <button id="errorShowMore" onclick="" oncontextmenu="return false;">
          More Information
        </button>
        <button id="errorShowLess" onclick="" oncontextmenu="return false;" hidden='true'>
          Less Information
        </button>
      </div>
      <div id="errorMessageRight">
        <button id="errorClose" oncontextmenu="return false;">
          Close
        </button>
      </div>
      <div class="clearBoth"></div>
      <div id="errorMoreInfo" hidden='true'></div>
    </div>

    <div id="sidebar">
      <div id="sidebarBox">
        <div id="sidebarScrollView">
          <div id="sidebarView"></div>
        </div>
        <div id="outlineScrollView" hidden='true'>
          <div id="outlineView"></div>
        </div>
        <div id="sidebarControls">
          <button id="thumbsSwitch" title="Show Thumbnails" onclick="XOJView.switchSidebarView('thumbs')" data-selected>
            <img src="/images/viewer/nav-thumbs.svg" align="top" height="16" alt="Thumbs" />
          </button>
          <button id="outlineSwitch" title="Show Document Outline" onclick="XOJView.switchSidebarView('outline')" disabled>
            <img src="/images/viewer/nav-outline.svg" align="top" height="16" alt="Document Outline" />
          </button>
        </div>
     </div>
    </div>

    <div id="loading">Loading... 0%</div>
    <div id="viewerouter"><div id="viewer"></div></div>
  </body>
</html>
