var TextLayerBuilder = function textLayerBuilder(textLayerDiv) {
  this.textLayerDiv = textLayerDiv;

  this.destroy = function textLayerDestroy() {
    window.removeEventListener('scroll', this.textLayerOnScroll, false);
    delete this.textLayerDiv;
    delete self;
    if(this.textDivs)
    {
        for(var key in this.textDivs)
        {
            delete this.textDivs[key];
        }
        this.textDivs.splice(0,this.textDivs.length);
    }
    console.log('DESTROY');
  }

  this.beginLayout = function textLayerBuilderBeginLayout() {
    this.textDivs = [];
    this.textLayerQueue = [];
  };

  this.endLayout = function textLayerBuilderEndLayout() {
    var self = this;
    var textDivs = this.textDivs;
    var renderTimer = null;
    var renderingDone = false;
    var renderInterval = 0;
    var resumeInterval = 500; // in ms

    // Render the text layer, one div at a time
    function renderTextLayer() {
      if (textDivs.length === 0) {
        clearInterval(renderTimer);
        renderingDone = true;
        return;
      }
      var textDiv = textDivs.shift();
      if (textDiv.dataset.textLength > 0) {
        self.textLayerDiv.appendChild(textDiv);

        if (textDiv.dataset.textLength > 1) { // avoid div by zero
          // Adjust div width (via letterSpacing) to match canvas text
          // Due to the .offsetWidth calls, this is slow
          // This needs to come after appending to the DOM
          textDiv.style.letterSpacing =
            ((textDiv.dataset.canvasWidth - textDiv.offsetWidth) /
              (textDiv.dataset.textLength - 1)) + 'px';
        }
      } // textLength > 0
    }
    renderTimer = setInterval(renderTextLayer, renderInterval);

    // Stop rendering when user scrolls. Resume after XXX milliseconds
    // of no scroll events
    var scrollTimer = null;
    function textLayerOnScroll() {
      if (renderingDone) {
        self.destroy();
        return;
      }

      // Immediately pause rendering
      clearInterval(renderTimer);

      clearTimeout(scrollTimer);
      scrollTimer = setTimeout(function textLayerScrollTimer() {
        // Resume rendering
        renderTimer = setInterval(renderTextLayer, renderInterval);
      }, resumeInterval);
    }; // textLayerOnScroll
    this.textLayerOnScroll = textLayerOnScroll;
    window.addEventListener('scroll', this.textLayerOnScroll, false);
    delete self;
  }; // endLayout

  this.appendText = function textLayerBuilderAppendText(text,
                                                        fontName, fontSize) {
    var textDiv = document.createElement('div');

    // vScale and hScale already contain the scaling to pixel units
    var fontHeight = fontSize * text.geom.vScale;
    textDiv.dataset.canvasWidth = text.canvasWidth * text.geom.hScale;

    textDiv.style.fontSize = fontHeight + 'px';
    textDiv.style.fontFamily = fontName || 'sans-serif';
    textDiv.style.left = text.geom.x + 'px';
    textDiv.style.top = (text.geom.y - fontHeight) + 'px';
    textDiv.textContent = text.str;
    textDiv.dataset.textLength = text.length;
    this.textDivs.push(textDiv);
  };
};