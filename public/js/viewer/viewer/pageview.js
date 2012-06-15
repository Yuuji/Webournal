var PageView = function pageView(container, content, id, pageWidth, pageHeight,
                                 stats, navigateTo)
{
    this.id = id;
    this.content = content;
    this.layer = [];
    this.layerLoaded = false;
    this.editLayer = -1;
    this.oldTextLayer = null;

    this.view = this.content;
    //this.x = view.x;
    //this.y = view.y;
    this.width = content.width;
    this.height = content.height;
    this.canvas = null;

    this.needRendering = false;

    var anchor = document.createElement('a');
    anchor.name = '' + this.id;

    var div = document.createElement('div');
    div.id = 'pageContainer' + this.id;
    div.className = 'page';

    this.div = div;

    container.appendChild(anchor);
    container.appendChild(div);

    this.loadCanvas = function loadCanvas(canvas)
    {
        if(canvas.attr('xojview_loaded')!=='loaded')
        {
            canvas.addClass('editlayer');
            canvas.mousedown($.proxy(XOJView.editMouseDown, XOJView));
            canvas.mousemove($.proxy(XOJView.editMouseMove, XOJView));
            canvas.mouseup($.proxy(XOJView.editMouseUp, XOJView));
            canvas.attr('xojview_loaded', 'loaded');
        }
    }
  
    this.getEditLayer = function getEditLayer(name)
    {
        if(this.editLayer == -1)
        {
            var xojEditLayer = this.content.getEditLayer();
            if(xojEditLayer!==false)
            {
                for(var key in this.layer)
                {
                    if(this.layer[key].layerKey==xojEditLayer)
                    {
                        var canvas = $('canvas#' + this.layer[key].id);
                        this.loadCanvas(canvas);
                        return key;
                    }
                }
            }
            
            if(typeof name == 'undefined' || name=="")
            {
                return -1;
            }
        
            var key = -1;
            for(var ckey in this.layer)
            {
                key = ckey;
            }
            key++;
            
            this.layer[key] = {};
            this.layer[key].name = name;


            var insertKey = this.content.addLayer(name, Math.round(new Date().getTime() / 1000));
            this.layer[key].layerKey = insertKey;

            this.addLayer(key, this.div, this.width, this.height, this.scale);

            this.editLayer = key;
        }

        var canvas = $('canvas#' + this.layer[this.editLayer].id);
        this.loadCanvas(canvas);

        return this.editLayer;
    }

    this.update = function pageViewUpdate(scale)
    {
        this.scale = scale || this.scale;
        div.style.width = (this.width * this.scale) + 'px';
        div.style.height = (this.height * this.scale) + 'px';

        this.remove();
    };

    function setupLinks(content, scale)
    {
        function bindLink(link, dest)
        {
            link.href = XOJView.getDestinationHash(dest);
            link.onclick = function pageViewSetupLinksOnclick() {
                if (dest)
                {
                    XOJView.navigateTo(dest);
                }
                return false;
            };
        }

        /*var links = content.getLinks();
        for (var i = 0; i < links.length; i++) {
          var link = document.createElement('a');
          link.style.left = (Math.floor(links[i].x - view.x) * scale) + 'px';
          link.style.top = (Math.floor(links[i].y - view.y) * scale) + 'px';
          link.style.width = Math.ceil(links[i].width * scale) + 'px';
          link.style.height = Math.ceil(links[i].height * scale) + 'px';
          link.href = links[i].url || '';
          if (!links[i].url)
            bindLink(link, ('dest' in links[i]) ? links[i].dest : null);
          div.appendChild(link);
        }*/
    }

    this.getPagePoint = function pageViewGetPagePoint(x, y)
    {
        var scale = XOJView.currentScale;
        return this.content.rotatePoint(x / scale, y / scale);
    };

    this.scrollIntoView = function pageViewScrollIntoView(dest)
    {
        if (!dest)
        {
            div.scrollIntoView(true);
            return;
        }

        var x = 0, y = 0;
        var width = 0, height = 0, widthScale, heightScale;
        var scale = 0;
        switch (dest[1].name) {
            case 'XYZ':
                x = dest[2];
                y = dest[3];
                scale = dest[4];
                break;
            case 'Fit':
            case 'FitB':
                scale = 'page-fit';
                break;
            case 'FitH':
            case 'FitBH':
                y = dest[2];
                scale = 'page-width';
                break;
            case 'FitV':
            case 'FitBV':
                x = dest[2];
                scale = 'page-height';
                break;
            case 'FitR':
                x = dest[2];
                y = dest[3];
                width = dest[4] - x;
                height = dest[5] - y;
                widthScale = (window.innerWidth - kScrollbarPadding) /
                width / kCssUnits;
                heightScale = (window.innerHeight - kScrollbarPadding) /
                height / kCssUnits;
                scale = Math.min(widthScale, heightScale);
                break;
            default:
                return;
        }

        var boundingRect = [
            this.content.rotatePoint(x, y),
            this.content.rotatePoint(x + width, y + height)
        ];

        if (scale && scale !== XOJView.currentScale)
        {
            XOJView.setScale(scale, true);
        }

        setTimeout(function pageViewScrollIntoViewRelayout() {
            // letting page to re-layout before scrolling
            var scale = XOJView.currentScale;
            var x = Math.min(boundingRect[0].x, boundingRect[1].x);
            var y = Math.min(boundingRect[0].y, boundingRect[1].y);
            var width = Math.abs(boundingRect[0].x - boundingRect[1].x);
            var height = Math.abs(boundingRect[0].y - boundingRect[1].y);

            // using temporary div to scroll it into view
            var tempDiv = document.createElement('div');
            tempDiv.style.position = 'absolute';
            tempDiv.style.left = Math.floor(x * scale) + 'px';
            tempDiv.style.top = Math.floor(y * scale) + 'px';
            tempDiv.style.width = Math.ceil(width * scale) + 'px';
            tempDiv.style.height = Math.ceil(height * scale) + 'px';
            div.appendChild(tempDiv);
            tempDiv.scrollIntoView(true);
            div.removeChild(tempDiv);
        }, 0);
    };

    this.addLayer = function addLayer(key, div, pageWidth, pageHeight, scale)
    {
        var canvasLayer = document.createElement('canvas');
        $(canvasLayer).css('display', 'none');
        this.layer[key].id = canvasLayer.id = 'page' + this.id + '_layer' + key;
        canvasLayer.className = 'pagelayer';

        // for chrome
        canvasLayer.onselectstart = function () {
            return false;
        }

        //canvasLayer.mozOpaque = true;
        div.appendChild(canvasLayer);

        canvasLayer.width = pageWidth * scale;
        canvasLayer.height = pageHeight * scale;

        var ctxLayer = canvasLayer.getContext('2d');
        ctxLayer.setTransform(1, 0, 0, 1, 0, 0);
        ctxLayer.clearRect(0,0, canvasLayer.width, canvasLayer.height);
        ctxLayer.save();

        return ctxLayer;
    }
  
    this.drawStroke = function drawStroke(ctxLayer, stroke)
    {
        var scale = this.scale;
        if(stroke.width)
        {
            ctxLayer.lineWidth = parseFloat(stroke.width)*scale;
        }
        else
        {
            ctxLayer.lineWidth = scale;
        }

        var alpha = 1;

        switch(stroke.tool.toLowerCase())
        {
            case 'highlighter':
                alpha = 0.5;
            case 'eraser':
                // seems to be the same like pen
            case 'pen':
                if(!stroke.color)
                {
                    stroke.color = 'black';
                }
                
                if(stroke.color.charAt(0)!="#")
                {
                    if(kColors[stroke.color])
                    {
                        stroke.color = kColors[stroke.color];
                    }
                }

                var color = new RGBColor(stroke.color);
                color.setAlpha(alpha);
                ctxLayer.strokeStyle = color.toRGB();
                break;
            default:
                return false;
                break;
        }

        var points = $.trim(stroke.value).split(' ');

        ctxLayer.beginPath();
        ctxLayer.moveTo(parseFloat(points[0])*scale, parseFloat(points[1])*scale);

        for(var i=2; i<points.length; i+=2)
        {
            ctxLayer.lineTo(parseFloat(points[i])*scale, parseFloat(points[i+1])*scale);
        }
        ctxLayer.stroke();
        return true;
    }
  
    this.drawText = function pageDrawText(ctxLayer, textLayer, text, layerKey)
    {
        var font = TEXT_DEFAULT_FONT;
        if(text.font)
        {
            font = text.font;
        }

        var fontsize = TEXT_DEFAULT_FONTSIZE;
        if(text.size)
        {
            fontsize = parseFloat(text.size);
        }

        if(!text.x || !text.y)
        {
            return false;
        }

        var x = text.x;
        var y = text.y;

        if(!text.color)
        {
            text.color = 'black';
        }
        if(text.color.charAt(0)!="#")
        {
            if(kColors[text.color])
            {
                text.color = kColors[text.color];
            }
        }

        var color = new RGBColor(text.color);

        if(!text.onEdit)
        {
            ctxLayer.font = (fontsize*this.scale) + 'px ' + "'" + font + "'";
            ctxLayer.fillStyle = color.toRGB();
            ctxLayer.textBaseline = 'top';
            
            
            var lines = text.value.split("\n");
            
            var counter = -1;
            for(var lineKey in lines)
            {
                counter++;
                var linespace = (counter*(fontsize+TEXT_DEFAULT_FONT_LINE_ADD));
                ctxLayer.fillText(lines[lineKey], x*this.scale, (y+linespace)*this.scale);
            }
        }

        if(typeof layerKey == 'number' || typeof layerKey == 'string')
        {
            var textLayerDiv = document.createElement('div');
            $(textLayerDiv).addClass('textlayer_page' + this.id + '_layer' + layerKey);
            $(textLayerDiv).addClass('layer_textlayer');
            $(textLayerDiv).css({
                'top': (y*this.scale) + 'px',
                'left': (x*this.scale) + 'px',
                'font': (fontsize*this.scale) + 'px ' + "'" + font + "'",
                'line-height': ((fontsize+TEXT_DEFAULT_FONT_LINE_ADD)*this.scale) + 'px',
                'min-height': ((fontsize+TEXT_DEFAULT_FONT_LINE_ADD)*this.scale) + 'px',
                'min-width': '5px'
            }).html(text.value.htmlentities().nl2br());
            
            if(!text.onEdit)
            {
                $(textLayerDiv).hide();
            }
            
            textLayer.appendChild(textLayerDiv);
        }
        else
        {
            var textLayerDiv = layerKey;
        }

        if(text.onEdit)
        {
            $(textLayerDiv).css('color', color.toHex());
        }
        else
        {
            $(textLayerDiv).css('color', 'transparent');
        }

        return textLayerDiv;
    }
    
    this.getPDFPage = function pageGetPDFPage()
    {
        if(content.background.type!='pdf')
        {
            return false;
        }
        
        var pdf = pdfcache.get(content.background.filename);

        if(!pdf)
        {
            return false;
        }
        
        return pdf.getPage(content.background.pageno);
    }
    
    this.remove = function pageviewRemove()
    {
        if (!div.hasChildNodes())
        {
            return false;
        }
        $(div).find('*').unbind()
        $(div).find('*').each(function() { $.discard(this) });
        div.removeAttribute('data-loaded');
        this.layerLoaded = false;
        if(this.canvas)
        {
            delete this.canvas;
        }
        
        switch(content.background.type)
        {
            case 'pdf':
                var pdf = pdfcache.get(content.background.filename);
                if(pdf)
                {
                    /**
                     * @todo need that anymore?
                     */
                    //pdf.removePageData(content.background.pageno);
                    //console.log('Delete PDF');
                }
                break;
        }
    }

    this.draw = function pageviewDraw()
    {
        if (div.hasChildNodes())
        {
            /** @todo Stats? */
            //this.updateStats();
            return false;
        }
        var canvas = document.createElement('canvas');
        canvas.id = 'page' + this.id;
        //canvas.mozOpaque = true;
        div.appendChild(canvas);

        var textLayer = document.createElement('div');
        textLayer.className = 'textLayer';
        div.appendChild(textLayer);

        $(textLayer).mousedown($.proxy(XOJView.editMouseDownTextLayer, XOJView));

        var scale = this.scale;
        canvas.width = pageWidth * scale;
        canvas.height = pageHeight * scale;

        var ctx = canvas.getContext('2d');
        ctx.save();

        var doCTX = function(ctx, self, color)
        {
            ctx.fillStyle = color;
            ctx.fillRect(0, 0, canvas.width, canvas.height);
            ctx.restore();
            ctx.translate(-self.content.x * self.scale, -self.content.y * self.scale);
        }

        //stats.begin = Date.now();

        this.needRendering = false;
        switch(content.background.type)
        {
            case 'solid':
                if(content.background.color)
                {
                    if(content.background.color.charAt(0)!="#")
                    {
                        if(kBgColors[content.background.color])
                        {
                            content.background.color = kBgColors[content.background.color];
                        }
                    }
                    
                    var color = new RGBColor(content.background.color);
                    doCTX(ctx, this, color.toRGB());
                }

                var style = content.background.style.toLowerCase();
                if(style!="plain")
                {
                    /** @todo scale! */
                    var pt = [];
                    if(style=='graph')
                    {
                        pt[1] = 0;
                        pt[3] = canvas.height;
                        for(var x=RULING_GRAPHSPACING*scale; x<canvas.width-1; x+=RULING_GRAPHSPACING*scale)
                        {
                            pt[0] = pt[2] = x;
                            ctx.lineWidth = RULING_THICKNESS*scale;
                            ctx.strokeStyle = RULING_COLOR;
                            ctx.beginPath();
                            ctx.moveTo(pt[0], pt[1]);
                            ctx.lineTo(pt[2], pt[3]);
                            ctx.closePath();
                            ctx.stroke();
                        }
                        
                        pt[0] = 0;
                        pt[2] = canvas.width;
                        for(var y=RULING_GRAPHSPACING*scale; y<canvas.height-1; y+=RULING_GRAPHSPACING*scale)
                        {
                            pt[1] = pt[3] = y;
                            ctx.lineWidth = RULING_THICKNESS*scale;
                            ctx.strokeStyle = RULING_COLOR;
                            ctx.beginPath();
                            ctx.moveTo(pt[0], pt[1]);
                            ctx.lineTo(pt[2], pt[3]);
                            ctx.closePath();
                            ctx.stroke();
                        }
                    }
                    else
                    {
                        pt[0] = 0;
                        pt[2] = canvas.width;
                        for(var y=RULING_TOPMARGIN*scale; y<canvas.height-1; y+=RULING_SPACING*scale)
                        {
                            pt[1] = pt[3] = y;
                            ctx.lineWidth = RULING_THICKNESS*scale;
                            ctx.strokeStyle = RULING_COLOR;
                            ctx.beginPath();
                            ctx.moveTo(pt[0], pt[1]);
                            ctx.lineTo(pt[2], pt[3]);
                            ctx.closePath();
                            ctx.stroke();
                        }
                    }
                    if(style=='lined')
                    {
                        pt[0] = pt[2] = RULING_LEFTMARGIN*scale;
                        pt[1] = 0;
                        pt[3] = canvas.height;
                        ctx.lineWidth = RULING_THICKNESS*scale;
                        ctx.strokeStyle = RULING_MARGIN_COLOR;
                        ctx.beginPath();
                        ctx.moveTo(pt[0], pt[1]);
                        ctx.lineTo(pt[2], pt[3]);
                        ctx.closePath();
                        ctx.stroke();
                    }
                }
                break;
            case 'pixmap':
                doCTX(ctx, this, 'rgb(255, 255, 255)')

                if(content.background.filename)
                {
                    var img = new Image();
                    img.onload = function() {
                        ctx.drawImage(img,0,0, img.width*scale, img.height*scale);
                    };
                    img.src = content.background.filename;
                }
                break;
            case 'pdf':
                doCTX(ctx, this, 'rgb(255, 255, 255)')
                var pdf = pdfcache.get(content.background.filename);

                if(pdf)
                {
                    this.needRendering = true;
                    this.startRendering(pdf.getPage(content.background.pageno), ctx, new TextLayerBuilder(textLayer));
                }
                break;
        }

        this.layerLoaded = false;
        this.layer = [];

        // now layers
        var counter=-1;
        for(var layerKey in content.layer)
        {
            counter++;

            this.layer[counter] = {};
            this.layer[counter].layerKey = layerKey;

            var layer = content.layer[layerKey];

            if(layer.name)
            {
                this.layer[counter].name = layer.name;
            }

            if(layer.time)
            {
                this.layer[counter].time = layer.time;
            }

            var ctxLayer = this.addLayer(counter, div, pageWidth, pageHeight, scale);

            // strokes
            for(var strokeKey in layer.strokes)
            {
                var stroke = layer.strokes[strokeKey];
                this.drawStroke(ctxLayer, stroke);
            }

            // texts
            for(var textKey in layer.texts)
            {
                var text = layer.texts[textKey];
                this.drawText(ctxLayer, textLayer, text, counter);
            }
        }

        this.layerLoaded = true;

        /** @todo Links? */
        //setupLinks(this.content, this.scale);
        this.canvas = canvas;
        if(this.needRendering==false)
        {
            if (this.onAfterDraw)
            {
                this.onAfterDraw();
            }
        }
        div.setAttribute('data-loaded', true);

        return true;
    };

    this.startRendering = function(pdfcontent, ctx, textLayer)
    {
        if(this.oldTextLayer)
        {
            this.oldTextLayer.destroy();
            delete this.oldTextLayer;
        }
        
        this.oldTextLayer = textLayer;
        var self = this;
        
        pdfcontent.data.render({
            canvasContext: ctx,
            viewport: pdfcontent.data.getViewport(this.scale),
            textLayer: textLayer
        }).then(
        function pdfPageRenderCallback()
        {
            //this.updateStats();
            if(self.div.getAttribute('data-loaded')=="true")
            {
                if (self.onAfterDraw)
                {
                    self.onAfterDraw();
                }
            }
            self.needRendering = false;
            delete textLayer;
        },
        function pdfPageRernderError(error)
        {
            XOJView.error('An error occurred while rendering the page.', error);
        });
        delete textLayer;
    };

    this.updateStats = function pageViewUpdateStats()
    {
        var t1 = stats.compile, t2 = stats.fonts, t3 = stats.render;
        var str = 'Time to compile/fonts/render: ' +
              (t1 - stats.begin) + '/' + (t2 - t1) + '/' + (t3 - t2) + ' ms';
        document.getElementById('info').innerHTML = str;
    };
};