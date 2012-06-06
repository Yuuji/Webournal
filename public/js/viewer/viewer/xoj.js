var XOJReader = {

    data: {},
    numPages: 0,
    loaderFunc: null,
    saverFunc: null,
    errorFunc: null,
    successFunc: null,

    load: function(options) {
        var self = this;
        
        this.loaderFunc = (options.loader ? options.loader : this.loaderFunc);
        this.saverFunc = (options.saver ? options.saver : this.saverFunc);
        this.errorFunc = (options.error ? options.error : this.errorFunc);
        this.successFunc = (options.success ? options.success : this.successFunc);
        
        if(!this.loaderFunc)
        {
            if(this.errorFunc)
            {
                this.errorFunc();
            }
        }
        
        this.loaderFunc({
            start: 0,
            numberOfPages: 20,
            success: function(data) {
                if(!data || !data.pages)
                {
                    if(self.errorFunc)
                    {
                        self.errorFunc();
                    }
                }
                else
                {
                    self.data = data;
                    self.loadXOJ();
                    
                    if(self.successFunc)
                    {
                        self.successFunc();
                    }
                }
            },
            error: function() {
                if(self.errorFunc)
                {
                    self.errorFunc();
                }
            }
        });
    },

    loadXOJ: function() {
        this.numPages = this.data.pages.length;
    },
    
    getPage: function(pagenum) {
        return new XOJPage(this.data.pages[pagenum-1]);
    },
    
    setPage: function(pagenum, page) {
        for(key in this.data.pages[pagenum-1])
        {
            this.data.pages[pagenum-1][key] = page[key];
        }
    },
    
    getTitle: function() {
        return this.data.title;
    }
};

var XOJPage = function(page) {
    
    this.layer = {};
    
    $.extend(true, this, page);
    
    this.x = 0;
    this.y = 0;
    
    this.rotatePoint = function pageRotatePoint(x, y, reverse) {
      var rotate = reverse ? (360 - this.rotate) : this.rotate;
      switch (rotate) {
        case 180:
          return {x: this.width - x, y: y};
        case 90:
          return {x: this.width - y, y: this.height - x};
        case 270:
          return {x: y, y: x};
        case 360:
        case 0:
        default:
          return {x: x, y: this.height - y};
      }
    };
    
    this.addLayer = function pageAddLayer(name)
    {
        var time = false;
        if(arguments.length>1)
        {
            time = arguments[1];
        }
        
        var lastKey = -1;
        for(var key in this.layer)
        {
            lastKey = key;
        }
        
        lastKey++;
        
        this.layer[lastKey] = new XOJLayer({
            name: name,
            time: time
        });
        
        return lastKey;
    };
    
    this.getEditLayer = function pageGetEditLayer() {
        if(typeof this.editLayer != "undefined")
        {
            return this.editLayer;
        }
        
        return false;
    };
    
    for(var key in this.layer)
    {
        this.layer[key] = new XOJLayer(this.layer[key]);
    }
};

var XOJLayer = function(layer) {
    this.strokes = [];
    this.texts = [];
    
    $.extend(true, this, layer);
    
    this.getLayerObject = function layerGetLayerObject() {
        var obj = {
            'strokes': [],
            'texts': []
        };
        
        for(var key in this.strokes)
        {
            obj.strokes.push(this.strokes[key].getStrokeObject());
        }
        
        for(var key in this.texts)
        {
            obj.texts.push(this.texts[key].getTextObject());
        }
        
        return obj;
    };
    
    this.getLastStrokeKey = function layerGetLastStrokeKey() {
        var lastKey = -1;
        for(var key in this.strokes)
        {
            lastKey = key;
        }
        
        return lastKey;
    };
    
    this.addStroke = function layerAddStroke(tool, color, width, value) {
        var lastKey = this.getLastStrokeKey();
        
        lastKey++;
        
        this.strokes[lastKey] = new XOJStroke({
            tool: tool,
            color: color,
            width: width,
            value: value
        });
        
        return lastKey;
    };
    
    this.removeStroke = function layerRemoveStroke(key) {
        this.strokes.splice(key,1);
    };
    
    this.removeLastStroke = function layerRemoveLastStroke() {
        var lastKey = this.getLastStrokeKey();
        this.removeStroke(lastKey);
    };
    
    this.getLastTextKey = function layerGetLastTextKey() {
        var lastKey = -1;
        for(var key in this.texts)
        {
            lastKey = key;
        }
        
        return lastKey;
    };
    
    this.addText = function layerAddText(font, size, x, y, color, value) {
        var lastKey = this.getLastTextKey();
        
        lastKey++;
        
        this.texts[lastKey] = new XOJText({
            font: font,
            size: size,
            x: x,
            y: y,
            color: color,
            value: value
        });
        
        return lastKey;
    };
    
    this.addTextAtKey = function layerAddTextAtKey(key, font, size, x, y, color, value) {
        this.texts.splice(key,0,new XOJText({
            font: font,
            size: size,
            x: x,
            y: y,
            color: color,
            value: value
        }));
    };
    
    this.editText = function layereditText(key, font, size, x, y, color, value) {
        this.texts[key].font = font;
        this.texts[key].size = size;
        this.texts[key].x = x;
        this.texts[key].y = y;
        this.texts[key].color = color;
        this.texts[key].value = value;
    };
    
    this.removeText = function layerRemoveText(key) {
        this.texts.splice(key,1);
    };
    
    this.removeLastText = function layerRemoveLastText() {
        var lastKey = this.getLastTextKey();
        this.removeText(lastKey);
    };
    
    for(var key in this.strokes)
    {
        this.strokes[key] = new XOJStroke(this.strokes[key]);
    }
    
    for(var key in this.texts)
    {
        this.texts[key] = new XOJText(this.texts[key]);
    }
};

var XOJStroke = function(stroke) {
    this.tool = '';
    this.color = '';
    this.width = '';
    this.value = '';
    
    if(stroke.tool)
    {
        this.tool = stroke.tool;
    }
    
    if(stroke.color)
    {
        this.color = stroke.color;
    }
    
    if(stroke.width)
    {
        this.width = stroke.width;
    }
    
    if(stroke.value)
    {
        this.value = stroke.value;
    }
    
    this.getStrokeObject = function strokeGetStrokeObject()
    {
        return {
            'tool': this.tool,
            'color': this.color,
            'width': this.width,
            'value': this.value
        };
    };
};

var XOJText = function(text) {
    this.font = '';
    this.size = '';
    this.x = '';
    this.y = '';
    this.color = '';
    this.value = '';
    
    if(text.font)
    {
        this.font = text.font;
    }
    
    if(text.size)
    {
        this.size = text.size;
    }
    
    if(text.x)
    {
        this.x = text.x;
    }
    
    if(text.y)
    {
        this.y = text.y;
    }
    
    if(text.color)
    {
        this.color = text.color;
    }
    
    if(text.value)
    {
        this.value = text.value;
    }
    
    this.getTextObject = function strokeGetTextObject()
    {
        return {
            'font': this.font,
            'size': this.size,
            'x': this.x,
            'y': this.y,
            'color': this.color,
            'value': this.value
        };
    };
};