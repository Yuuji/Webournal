var XOJView = {
    xojLoader: null,
    xojSaver: null,
    xojLogin: null,
    loggedIn: false,
    currentPageNumber: 1,
    pages: [],
    thumbnails: [],
    currentScale: kDefaultScale,
    initialBookmark: document.location.hash.substring(1),
    editName: '',
    editInProcess: false,
    editInProcessPart: false,
    editTool: 'hand',
    editRuler: false,
    editThickness: 'medium',
    editColor: 'black',
    editColorHighlighter: 'yellow',
    editColorPicker: '#000000',
    editProgress: false,
    editNewStroke: {},
    editCanvasSave: null,
    editUndoManager: null,
    editTextEditInProcess: false,
    editTextEditKey: -1,
    
    getEditUndoManager: function()
    {
        if(!this.editUndoManager)
        {
            this.editUndoManager = new UndoManager();

            this.editUndoManager.setCallback(function() {
                $('button#undo').attr('disabled', !this.hasUndo());
                $('button#redo').attr('disabled', !this.hasRedo());
                
                $('button#save').attr('disabled', !this.hasUndo());
            });
        }

        return this.editUndoManager;
    },
  
    editUndo: function()
    {
        var undo = this.getEditUndoManager();
        undo.undo();
        delete undo;
    },
  
    editRedo: function()
    {
        var undo = this.getEditUndoManager();
        undo.redo();
        delete undo;
    },
    
    resetEdit: function()
    {
        var check = this.editGetCurrentTextDiv();
        
        if(typeof check != 'boolean')
        {
            $(check).blur();
        }
        
        delete check;
        
        if(arguments.length>0 && arguments[0])
        {
            $('#editcontrols').hide();
            $('#edit').removeClass('selected');
            this.selectEditTool('hand');
        }
        
        this.editInProcess = false;
        this.editInProcessPart = false;
        this.editProgress = false;
        this.loginInProcess = false;
        this.editNewStroke = {};
        this.editCanvasSave = null;
        this.editTextEditInProcess = false;
        this.editTextEditKey = -1;
    },

    toggleEdit: function()
    {
        if($('#edit').hasClass('selected'))
        {
            $('#layerSelect').children('option[value=""]').attr('selected', true);
            selectLayer('');
        }
        else
        {
            this.edit();
        }
    },
    
    checkLogin: function(callBack)
    {
        if(this.loginInProcess)
        {
            return;
        }
        if(!this.xojLogin)
        {
            callBack('');
        }
        
        this.checkLoginCallBack(false, false, callBack);
    },
    
    checkLoginCallBack: function(username, password, callBack)
    {
        var that = this;
        
        this.loginInProcess = true;
        
        var check = this.xojLogin(username, password,
            function(check)
            {
                $('#viewer').css('cursor', 'default');
                if(!(typeof check == 'boolean' || typeof check == 'string'))
                {
                    /** @todo error handling */
                    return false;
                }

                if(typeof check == 'boolean')
                {
                    if(check===true)
                    {
                        // logedin but no given username
                        that.loginInProcess = false;
                        return callBack('');
                    }
                }
                else
                {
                    // logedin and given username
                    that.loginInProcess = false;
                    return callBack(check);
                }

                // not logedin

                /** @todo add register link */
                apprise('Bitte logge dich ein!',
                {
                    'confirm': true,
                    'login'  : true,
                    'animate': true
                },
                function(username, password)
                {
                    if(typeof username == 'boolean')
                    {
                        // cancel
                        that.loginInProcess = false;
                        return;
                    }
                    
                    /** @todo progress */
                    $('#viewer').css('cursor', 'wait');
                    
                    return that.checkLoginCallBack(username, password, callBack);
                }
            );
        });
    },

    edit: function()
    {
        if(this.editInProcess)
        {
            return;
        }
        
        if(!this.checkLogin($.proxy(this.editAfterLoginCallback, this)))
        {
            return;
        }
    },

    editAfterLoginCallback: function(username)
    {
        if(!this.loggedIn)
        {
            this.openIntern(this.currentScale, $.proxy(function() {
                this.editCallback(username);
            }, this));
        }
        else
        {
            this.editCallback(username);
        }
    },
    
    editCallback: function(username)
    {
        if(typeof username == "string" && username.length>0)
        {
            this.editName = username;
        }
        
        var layerKey = this.pages[this.page-1].getEditLayer(this.editName);
        
        if(layerKey==-1)
        {
            this.editInProcess = true;
            var that = this;
            apprise('Bitte gebe einen Namen ein. Dieser erscheint &ouml;ffentlich f&uuml;r deine Anmerkungen!',
                {
                    'confirm': true,
                    'input': this.editName,
                    'animate': true
                },
                function(name)
                {
                    that.editInProcess = false;
                    if(typeof name == 'boolean' && name==false)
                    {
                        return;
                    }
                    else
                    {
                        if(name!="")
                        {
                            that.editName = name;
                            layerKey = that.pages[that.page-1].getEditLayer(that.editName);
                        }
                        else
                        {
                            that.editName = '';
                        }
                        that.edit();
                    }
                }
            );
        }
        else if(layerKey==-2)
        {
            /**
             *@todo What now?
             */
            //updateViewarea(true);
            //this.editCallback(username);
        }
        else
        {
            updateLayerSelect(this.page, true);
            $('#layerSelect').children('option[value="' + layerKey + '"]').attr('selected', true);
            selectLayer(layerKey, false, true);
            this.selectEditTool(this.editTool);
            $('button#edit').addClass('selected');
            this.editInProcess = true;
            this.editDoRedraw();
        }
        
        delete layerKey;
    },
  
    editDoClear: function(ctx)
    {
        ctx.setTransform(1,0,0,1,0,0);
        // Will always clear the right space
        ctx.clearRect(0,0,ctx.canvas.width,ctx.canvas.height);
        var w = ctx.canvas.width;
        ctx.canvas.width = 1;
        ctx.canvas.width = w;
        ctx.save();
        delete w;
    },
  
    editDoRestore: function()
    {
        var canvas = this.editGetCanvas();
        var ctx = canvas.getContext('2d');
        this.editDoClear(ctx);
        ctx.putImageData(this.editCanvasSave, 0, 0);
        
        delete canvas;
        delete ctx;
    },
  
    editDoRedraw: function()
    {
        var page = this.page;
        if(arguments.length>0)
        {
            page = arguments[0];
        }

        var canvas = this.editGetCanvas(page);
        var ctx = canvas.getContext('2d');

        var pageView = this.pages[page-1];
        var editLayerId = pageView.getEditLayer();
        var layerKey = pageView.layer[editLayerId].layerKey;

        this.editDoClear(ctx);

        // Strokes
        for(var strokeKey in pageView.content.layer[layerKey].strokes)
        {
            var stroke = pageView.content.layer[layerKey].strokes[strokeKey];
            pageView.drawStroke(ctx, stroke);
        }

        var div = $(pageView.div);
        var textLayer = div.find('div.textLayer');

        if(this.editTextEditKey==-1)
        {
            $(textLayer).find('div.isEditable').remove();
        }
        else
        {
            var that = this;
            $(textLayer).find('div.isEditable').each(function() {
                if($(this).data('textid')!=that.editTextEditKey)
                {
                    $(this).remove();
                }
                else
                {
                    $(this).css('color', pageView.content.layer[layerKey].texts[that.editTextEditKey].color);
                }
            });
        }

        // Texts
        for(var textKey in pageView.content.layer[layerKey].texts)
        {
            if(this.editTextEditKey!=textKey)
            {
                var text = pageView.content.layer[layerKey].texts[textKey];
                var newTextLayer = pageView.drawText(ctx, textLayer[0], text, editLayerId);

                $(newTextLayer).addClass('isEditable');
                $(newTextLayer).attr('contenteditable', true);
                $(newTextLayer).data('textid', textKey);
                $(newTextLayer).focus($.proxy(XOJView.editFocus, XOJView));
                $(newTextLayer).blur($.proxy(XOJView.editBlur, XOJView));
                $(newTextLayer).show();
            }
        }
    },
    editCheckStroke: function (stroke)
    {
        var correctedStroke = '';
        
        var points = $.trim(stroke).split(' ');
        
        var newX = 0;
        var newY = 0;
        
        var oldX = parseFloat(points[0]);
        var oldY = parseFloat(points[1]);
        var first = true;
        for(var i=2; i<points.length; i+=2)
        {
            correctedStroke += (first ? '' : ' ') + oldX.toFixed(2) + ' ' + oldY.toFixed(2);
            first = false;
            
            newX = parseFloat(points[i]);
            newY = parseFloat(points[i+1]);
            
            var xLen = newX-oldX;
            var yLen = newY-oldY;
            
            if(Math.abs(xLen)>5 || Math.abs(yLen)>5)
            {
                var m = yLen/xLen;
                
                // split x or y?
                
                var doX = true;
                var stepAdd = 5;
                if(Math.abs(xLen) > Math.abs(yLen))
                {
                    var steps = Math.abs(xLen) / 5;
                    doX = true;
                    
                    if(newX < oldX)
                    {
                        stepAdd = -stepAdd;
                    }
                }
                else
                {
                    var steps = Math.abs(yLen) / 5;
                    doX = false;
                    
                    if(newY < oldY)
                    {
                        stepAdd = -stepAdd;
                    }
                }
                
                var startX = oldX;
                var startY = oldY;
                
                var stepX = startX;
                var stepY = startY;
                for(var add=1; add<steps; add++)
                {
                    if(doX)
                    {
                        stepX += stepAdd;
                        stepY = (m * (stepX - startX)) + startY;
                    }
                    else
                    {
                        stepY += stepAdd;
                        stepX = ((stepY - startY) / m) + startX;
                    }
                    
                    correctedStroke += (first ? '' : ' ') + stepX.toFixed(2) + ' ' + stepY.toFixed(2);
                }
                
            }
            
            oldX = newX;
            oldY = newY;
        }
        
        correctedStroke += (first ? '' : ' ') + oldX.toFixed(2) + ' ' + oldY.toFixed(2);
        
        return correctedStroke;
    },
  
    editAddStroke: function(pageNum, stroke)
    {
        var pageView = this.pages[pageNum-1];
        var editLayerId = pageView.getEditLayer();

        if(editLayerId==-1)
        {
            return false;
        }

        var layerKey = pageView.layer[editLayerId].layerKey;

        pageView.content.layer[layerKey].addStroke(stroke.tool, stroke.color, stroke.width, stroke.value);

        this.editDoRedraw(pageNum);
        return true;
    },
  
    editRemoveStroke: function(pageNum)
    {
        var pageView = this.pages[pageNum-1];
        var editLayerId = pageView.getEditLayer();

        if(editLayerId==-1)
        {
            return false;
        }

        var layerKey = pageView.layer[editLayerId].layerKey;

        pageView.content.layer[layerKey].removeLastStroke();

        this.editDoRedraw(pageNum);
        return true;
    },
  
    editAddText: function(pageNum, text)
    {
        var pageView = this.pages[pageNum-1];
        var editLayerId = pageView.getEditLayer();

        if(editLayerId==-1)
        {
            return false;
        }

        var layerKey = pageView.layer[editLayerId].layerKey;

        pageView.content.layer[layerKey].addText(text.font, text.size, text.x, text.y, text.color, text.value);
        this.editDoRedraw(pageNum);
        return true;
    },
  
    editAddTextAtKey: function(pageNum, text, key)
    {
        var pageView = this.pages[pageNum-1];
        var editLayerId = pageView.getEditLayer();

        if(editLayerId==-1)
        {
            return false;
        }

        var layerKey = pageView.layer[editLayerId].layerKey;

        pageView.content.layer[layerKey].addTextAtKey(key, text.font, text.size, text.x, text.y, text.color, text.value);
        this.editDoRedraw(pageNum);
        return true;
    },
  
    editUpdateText: function(pageNum, key, text)
    {
        var pageView = this.pages[pageNum-1];
        var editLayerId = pageView.getEditLayer();

        if(editLayerId==-1)
        {
            return false;
        }

        var layerKey = pageView.layer[editLayerId].layerKey;

        pageView.content.layer[layerKey].editText(key, text.font, text.size, text.x, text.y, text.color, text.value);
        this.editDoRedraw(pageNum);
        return true;
    },
  
    editRemoveText: function(pageNum, key)
    {
        var pageView = this.pages[pageNum-1];
        var editLayerId = pageView.getEditLayer();

        if(editLayerId==-1)
        {
            return false;
        }

        var layerKey = pageView.layer[editLayerId].layerKey;

        pageView.content.layer[layerKey].removeText(key);

        this.editDoRedraw(pageNum);
        return true;
    },
  
    editRemoveLastText: function(pageNum)
    {
        var pageView = this.pages[pageNum-1];
        var editLayerId = pageView.getEditLayer();

        if(editLayerId==-1)
        {
            return false;
        }

        var layerKey = pageView.layer[editLayerId].layerKey;

        pageView.content.layer[layerKey].removeLastText();

        this.editDoRedraw(pageNum);
        return true;
    },
  
    selectEditCursor: function()
    {
        var tool = this.editTool;
        var cursorType = 'default';
        var cursorURL = '';

        var pageView = this.pages[this.page-1];
        var editLayerId = pageView.getEditLayer();
        if(!(editLayerId>-1 && editLayerId==selectLayerCurrentValue))
        {
            cursorType = 'default';
        }
        else if(tool=='hand')
        {
            cursorType = 'default';
        }
        else if(tool=='texttool')
        {
            cursorType = 'text';
        }
        else
        {
            var canvas = document.createElement('canvas');
            switch(tool)
            {
                case 'pencil':
                    canvas.width = 4;
                    canvas.height = 4;
                    break;
                case 'eraser':
                case 'highlighter':
                    canvas.width = 10;
                    canvas.height = 10;
                    break;
            }

            var ctx = canvas.getContext('2d');

            var color = false;
            switch(tool)
            {
                case 'pencil':
                    color = new RGBColor(this.editColor);
                    ctx.fillStyle = color.toRGB();
                    ctx.fillRect(0, 0, canvas.width, canvas.height);
                    break;
                case 'eraser':
                    color = new RGBColor('white');
                case 'highlighter':
                    if(typeof color == 'boolean')
                    {
                    color = new RGBColor(this.editColorHighlighter);
                    }
                    ctx.strokeStyle = 'rgb(0,0,0)';
                    ctx.lineWidth = 2;
                    ctx.fillStyle = color.toRGB();
                    ctx.beginPath();
                    ctx.moveTo(0,0);
                    ctx.lineTo(canvas.width, 0);
                    ctx.lineTo(canvas.width, canvas.height);
                    ctx.lineTo(0, canvas.height);
                    ctx.lineTo(0, 0);
                    ctx.fill();
                    ctx.stroke();
                    break;
            }
            ctx.save();
            cursorType = 'url';
            cursorURL = canvas.toDataURL('image/png');
        }

        if(cursorType=='url')
        {
            $('div#viewer').css('cursor', 'url("' + cursorURL + '"), crosshair');
        }
        else
        {
            $('div#viewer').css('cursor', cursorType);
        }
    },
  
    selectEditTool: function(tool)
    {
        this.editTool = tool;
        this.selectEditCursor(tool);
        $('#editcontrols button.edittool').removeClass('selected');
        $('#editcontrols button#' + tool + '.edittool').addClass('selected');

        $('#editcontrols button.thickness').removeClass('selected');
        /*$('#viewer *').each(function() {
        this.onselectstart = XOJView.doCursorChange;
        });*/
        if(tool=='texttool' || tool=='hand')
        {
            $('#editcontrols button.thickness').attr('disabled', true);
            $('canvas.editlayer').css('zIndex', "1");
        }
        else
        {
            $('#editcontrols button.thickness').attr('disabled', false);
            $('#editcontrols button#thickness_' + this.editThickness + '.thickness').addClass('selected');
            $('canvas.editlayer').css('zIndex', "200");
        }
        
        if(tool=='pencil' || tool=='highlighter')
        {
            $('#editcontrols button#ruler').attr('disabled', false);
            $('#editcontrols button#ruler').removeClass('selected');
            this.editRuler = false;
        }
        else
        {
            $('#editcontrols button#ruler').attr('disabled', true);
        }

        var pageView = this.pages[this.page-1];
        var div = $(pageView.div);
        var textLayer = div.find('div.textLayer');
        var textLayers = textLayer.children('div');
        if(tool=='hand')
        {
            textLayers.each(function() {
                if(!$(this).hasClass('editlayer'))
                {
                    $(this).show();
                }
            });
        }
        else
        {
            textLayers.each(function() {
                if(!$(this).hasClass('editlayer'))
                {
                    $(this).hide();
                }
            });
        }

        $('#editcontrols button.colorpatch').removeClass('selected');
        if(tool=='eraser' || tool=='hand')
        {
            $('#editcontrols button.colorpatch').attr('disabled', true);
        }
        else if(tool=='highlighter')
        {
            $('#editcontrols button.colorpatch').attr('disabled', false);
            this.selectEditColor(this.editColorHighlighter);
        }
        else
        {
            $('#editcontrols button.colorpatch').attr('disabled', false);
            this.selectEditColor(this.editColor);
        }
    },
    
    selectToggleRuler: function()
    {
        this.editRuler = !this.editRuler;
        
        if(this.editRuler)
        {
            $('#editcontrols button#ruler').addClass('selected');
        }
        else
        {
            $('#editcontrols button#ruler').removeClass('selected');
        }
    },
  
    selectEditThickness: function(thickness)
    {
        this.editThickness = thickness;
        $('#editcontrols button.thickness').removeClass('selected');
        $('#editcontrols button#thickness_' + thickness + '.thickness').addClass('selected');
    },
  
    selectEditColor: function(color)
    {
        var colorName = color;
        if(color[0]=='#')
        {
            colorName = 'select'
            this.editColorPicker = color;
            $('#editcontrols button#colorpatch_select.colorpatch img').css('backgroundColor', color);
        }
        else if(color=='select')
        {
            colorName = 'select';
            color = this.editColorPicker;
        }
        else
        {
            colorName = color;
        }

        if(this.editTool=='highlighter')
        {
            this.editColorHighlighter = color;
        }
        else
        {
            this.editColor = color;
        }

        $('#editcontrols button.colorpatch').removeClass('selected');
        $('#editcontrols button#colorpatch_' + colorName + '.colorpatch').addClass('selected');

        this.selectEditCursor();
        
        if(this.editTool == 'texttool')
        {
            if(this.editTextEditInProcess)
            {
                this.editTextColorChange();
            }
        }
    },
  
    fixPos: function(ev, pageView)
    {
        if(!ev.offsetX)
        {
            ev.offsetX = ev.layerX || ev.originalEvent.layerX;
            ev.offsetY = ev.layerY || ev.originalEvent.layerY;
        }


        ev.originalOffsetX = ev.offsetX;
        ev.originalOffsetY = ev.offsetY;

        ev.offsetX = ev.offsetX / pageView.scale;
        ev.offsetY = ev.offsetY / pageView.scale;

        return ev;
    },
  
    editMouseDownTextLayer: function(ev)
    {
        if(!this.editInProcess || this.editTool!='texttool')
        {
            return;
        }
        this.editMouseDown(ev);
    },
  
    editMouseDown: function(ev)
    {
        if(ev.button!=0)
        {
            return true;
        }

        var editId = this.pages[this.page-1].getEditLayer();
        var layerId = this.pages[this.page-1].layer[editId].id;
        
        ev.srcElement = ev.srcElement || ev.target;

        if($(ev.srcElement).hasClass('isEditable'))
        {
            if($(ev.srcElement).parent().parent()[0].id!=this.pages[this.page-1].div.id)
            {
                ev.preventDefault();
                return false;
            }
            this.editTextEditInProcess = true;
            return true;
        }

        if($(ev.srcElement).parent()[0].id!=this.pages[this.page-1].div.id)
        {
            return true;
        }
        
        this.editTextEditInProcess = false;

        var ctx = this.editGetCanvas().getContext('2d');
        var pageView = this.pages[this.page-1];
        this.editCanvasSave = ctx.getImageData(0, 0, ctx.canvas.width, ctx.canvas.height);
        this.editProgress = true;

        ev = this.fixPos(ev, pageView);

        var tool = false;
        var width = false;
        var color = false;
        switch(this.editTool)
        {
            case 'eraser':
                tool = 'eraser';
                color = 'white';
                width = kThicknessEraser[this.editThickness];
            case 'highlighter':
                if(typeof tool == 'boolean')
                {
                    tool = 'highlighter';
                }

                if(typeof width == 'boolean')
                {
                    width = kThicknessHighlighter[this.editThickness];
                }
                if(typeof color == 'boolean')
                {
                    color = this.editColorHighlighter;
                }
            case 'pencil':
                if(typeof tool == 'boolean')
                {
                    tool = 'pen';
                }

                if(typeof width == 'boolean')
                {
                    width = kThicknessPen[this.editThickness];
                }

                if(typeof color == 'boolean')
                {
                    color = this.editColor;
                }

                this.editNewStroke = {};
                this.editNewStroke.tool = tool;
                this.editNewStroke.color = color;
                this.editNewStroke.width = width;
                this.editNewStroke.value = ev.offsetX.toFixed(2) + ' ' + ev.offsetY.toFixed(2);
                this.editNewStroke.lastValue = {
                    x: ev.offsetX,
                    y: ev.offsetY
                };
                pageView.drawStroke(ctx, this.editNewStroke);
                this.editInProcessPart = true;
                break;
            case 'texttool':
                var div = $(pageView.div);
                var textLayer = div.find('div.textLayer');
                var editLayerId = pageView.getEditLayer();

                var newTextLayer = pageView.drawText(ctx, textLayer[0], {
                    x: ev.offsetX,
                    y: (ev.offsetY - ((TEXT_DEFAULT_FONTSIZE * pageView.scale)/4) ),
                    font: TEXT_DEFAULT_FONT,
                    color: this.editColor,
                    size: TEXT_DEFAULT_FONTSIZE,
                    value: '',
                    onEdit: true
                }, editLayerId);
                $(newTextLayer).addClass('isEditable');
                $(newTextLayer).attr('contenteditable', true);
                $(newTextLayer).data('textid', 'new');
                $(newTextLayer).blur($.proxy(XOJView.editBlur, XOJView));
                $(newTextLayer).show();
                setTimeout(function() { 
                    $(newTextLayer).focus();
                    $(newTextLayer).selectRange(0,1);
                }, 200);
                this.editTextEditInProcess = true;
                this.editTextEditKey = -1;
                break;
        }

        return false;
    },
  
    editGetCanvas: function()
    {
        var page = this.page;

        if(arguments.length>0)
        {
            page = arguments[0];
        }

        var pageView = this.pages[page-1];

        return $('#' + pageView.layer[pageView.getEditLayer()].id)[0];
    },

    editMouseMove: function(ev)
    {
        if(!this.editInProcessPart)
        {
            return false;
        }

        var ctx = ev.target.getContext('2d');
        var pageView = this.pages[this.page-1];

        ev = this.fixPos(ev, pageView);

        switch(this.editTool)
        {
            case 'eraser':
            case 'highlighter':
            case 'pencil':
                this.editDoRestore();
                if(this.editRuler)
                {
                    this.editNewStroke.value  = ' ' + this.editNewStroke.lastValue.x.toFixed(2) + ' ' + this.editNewStroke.lastValue.y.toFixed(2);
                    this.editNewStroke.value += ' ' + ev.offsetX.toFixed(2) + ' ' + ev.offsetY.toFixed(2);
                }
                else
                {
                    this.editNewStroke.value += ' ' + ev.offsetX.toFixed(2) + ' ' + ev.offsetY.toFixed(2);
                    this.editNewStroke.lastValue = {
                        x: ev.offsetX,
                        y: ev.offsetY
                    };
                }
                pageView.drawStroke(ctx, this.editNewStroke);
                break;
        }
        return false;
    },
  
    editMouseUp: function(ev)
    {
        if(!this.editInProcessPart)
        {
            return false;
        }
        this.editInProcessPart = false;
        this.editDoRestore();
        var undo = this.getEditUndoManager();
        switch(this.editTool)
        {
            case 'eraser':
            case 'highlighter':
            case 'pencil':
                if(this.editNewStroke.value.split(' ').length<4)
                {
                    break;
                }
                this.editNewStroke.value = this.editCheckStroke(this.editNewStroke.value);
                undo.register(
                    this, this.editRemoveStroke, [this.page], 'Remove Stroke',
                    this, this.editAddStroke, [this.page, this.editNewStroke], 'Add Stroke'
                );
                this.editAddStroke(this.page, this.editNewStroke);
                break;
        }
        return false;
    },
  
    editKeyDown: function(ev)
    {
        if(this.editInProcessPart)
        {
            if(ev.keyCode == 27)
            {
                // Escape
                this.editInProcessPart = false;
                this.editDoRestore();
            }
            return false;
        }
        else if(this.editTextEditInProcess)
        {
            /** @todo escape */
        }

        return true;
    },
  
    editKeyUp: function(ev)
    {

        return true;

        // Don't needed
    },
  
    editFocus: function(ev)
    {
        ev.srcElement = ev.srcElement || ev.target;
        if(!$(ev.srcElement).hasClass('isEditable'))
        {
            return true;
        }

        var textid = $(ev.srcElement).data('textid');

        if(textid && textid!='new')
        {
            this.editTextEditKey = textid;
            this.editDoRedraw();
        }

        return true;
    },
  
    editBlur: function(ev)
    {
        if(!this.editTextEditInProcess)
        {
            return true;
        }
        
        ev.srcElement = ev.srcElement || ev.target;

        if(!ev.srcElement)
        {
            ev.srcElement = ev.target;
        }

        if(!$(ev.srcElement).hasClass('isEditable'))
        {
            return true;
        }

        var undo = this.getEditUndoManager();
        var textid = $(ev.srcElement).data('textid');

        var pageView = this.pages[this.page-1];
        var text = $(ev.srcElement).html().stripReturn().br2nl().div2nl().striptags().html_entity_decode();
        var textCheck = text.stripwhitespaces();

        if(textid=='new')
        {
            if(textCheck.length>0)
            {
                var newText = {
                    x: parseFloat($(ev.srcElement).css('left')) / pageView.scale,
                    y: parseFloat($(ev.srcElement).css('top')) / pageView.scale,
                    font: TEXT_DEFAULT_FONT,
                    color: this.editColor,
                    size: TEXT_DEFAULT_FONTSIZE,
                    value: $(ev.srcElement).html().stripReturn().br2nl().div2nl().striptags(),
                    onEdit: false
                };

                undo.register(
                    this, this.editRemoveLastText, [this.page], 'Remove Text',
                    this, this.editAddText, [this.page, newText], 'Add Text'
                );
                var newTextLayer = this.editAddText(this.page, newText);
            }
            $(ev.srcElement).remove();
        }
        else
        {
            var editLayerId = pageView.getEditLayer();
            var layerKey = pageView.layer[editLayerId].layerKey;
            var oldTextTemp = pageView.content.layer[layerKey].texts[textid];
            var oldText = new XOJText({
                font: oldTextTemp.font,
                size: oldTextTemp.size,
                x: oldTextTemp.x,
                y: oldTextTemp.y,
                color: oldTextTemp.color,
                value: oldTextTemp.value
            });
            this.editTextEditKey = -1;
            if(textCheck.length>0)
            {
                if(text!=oldText.value)
                {
                    var newText = new XOJText({
                        font: oldText.font,
                        size: oldText.size,
                        x: oldText.x,
                        y: oldText.y,
                        color: oldText.color,
                        value: text
                    });
                    undo.register(
                        this, this.editUpdateText, [this.page, textid, oldText], 'Update Text',
                        this, this.editUpdateText, [this.page, textid, newText], 'Update Text'
                    );
                    this.editUpdateText(this.page, textid, newText);
                }
            }
            else
            {
                undo.register(
                    this, this.editAddTextAtKey, [this.page, oldText, textid], 'Add Text',
                    this, this.editRemoveText, [this.page, textid], 'Remove Text'
                );
                this.editRemoveText(this.page, textid);
            }
        }

        this.editTextEditKey = -1;
        this.editTextEditInProcess = false;
        return true;
    },
    
    editGetCurrentTextDiv: function() {
        if(!this.editTextEditInProcess)
        {
            return false;
        }
        
        
        var pageView = this.pages[this.page-1];
        var textLayer = $(pageView.div).children('div.textLayer');
        
        var found = false;
        var that = this;
        $(textLayer).find('div.isEditable').each(function() {
            if(that.editTextEditKey==-1 && $(this).data('textid')=='new')
            {
                found = $(this)[0];
                return false;
            }
            else if($(this).data('textid')==that.editTextEditKey)
            {
                found = $(this)[0];
                return false;
            }
        });
        
        return found;
    },
    
    editTextColorChange: function() {
        if(!this.editTextEditInProcess)
        {
            return true;
        }

        var textDiv = this.editGetCurrentTextDiv();
        
        if(typeof textDiv == 'boolean')
        {
            return true;
        }
        
                
        if(this.editTextEditKey==-1)
        {
            var color = this.editColor;
            if(color.charAt(0)!="#")
            {
                if(kColors[color])
                {
                    color = kColors[color];
                }
            }
            
            $(textDiv).css('color', color);
            
            return true;
        }
        else
        {
            var pageView = this.pages[this.page-1];
            var editLayerId = pageView.getEditLayer();
            var layerKey = pageView.layer[editLayerId].layerKey;
            var oldTextTemp = pageView.content.layer[layerKey].texts[this.editTextEditKey];
            var oldText = new XOJText({
                font: oldTextTemp.font,
                size: oldTextTemp.size,
                x: oldTextTemp.x,
                y: oldTextTemp.y,
                color: oldTextTemp.color,
                value: oldTextTemp.value
            });
            var newText = new XOJText({
                font: oldText.font,
                size: oldText.size,
                x: oldText.x,
                y: oldText.y,
                color: this.editColor,
                value: oldTextTemp.value
            });

            if(oldText.color!=newText.color)
            {
                var undo = this.getEditUndoManager();
                undo.register(
                    this, this.editUpdateText, [this.page, this.editTextEditKey, oldText], 'Update text color',
                    this, this.editUpdateText, [this.page, this.editTextEditKey, newText], 'Update text color'
                );
                this.editUpdateText(this.page, this.editTextEditKey, newText);
            }
        }
    },

    setScale: function(val, resetAutoSettings)
    {
        var pages = this.pages;
        for (var i = 0; i < pages.length; i++)
        {
            pages[i].update(val * kCssUnits);
        }
        
        delete pages;

        if (this.currentScale != val)
        {
            this.pages[this.page - 1].scrollIntoView();
        }
        this.currentScale = val;

        var event = $.Event('scalechange');
        event.pageNumber = val;
        event.scale = val;
        event.resetAutoSettings = resetAutoSettings;
        $(window).trigger(event);
        
        delete event;
    },

    parseScale: function(value, resetAutoSettings)
    {
        if ('custom' == value)
        {
            return;
        }

        var scale = parseFloat(value);
        if (scale)
        {
            this.setScale(scale, true);
            return;
        }

        var currentPage = this.pages[this.page - 1];
        var pageWidthScale = (window.innerWidth - kScrollbarPadding) / currentPage.width / kCssUnits;
        var pageHeightScale = (window.innerHeight - kScrollbarPadding) / currentPage.height / kCssUnits;
        if ('page-width' == value)
        {
            this.setScale(pageWidthScale, resetAutoSettings);
        }
        if ('page-height' == value)
        {
            this.setScale(pageHeightScale, resetAutoSettings);
        }
        if ('page-fit' == value)
        {
            this.setScale(
                Math.min(pageWidthScale, pageHeightScale),
                resetAutoSettings
            );
        }
    },

    zoomIn: function()
    {
        var newScale = Math.min(kMaxScale, this.currentScale * kDefaultScaleDelta);
        this.setScale(newScale, true);
        delete newScale;
    },

    zoomOut: function()
    {
        var newScale = Math.max(kMinScale, this.currentScale / kDefaultScaleDelta);
        this.setScale(newScale, true);
        delete newScale;
    },

    open: function(xojLoader, xojSaver, xojLogin, scale)
    {
        //document.title = this.url = url;
        
        this.xojLoader = xojLoader;
        this.xojSaver = xojSaver;
        this.xojLogin = xojLogin;

        var ua = $.browser;

        if(ua.mozilla && parseFloat(ua.version.slice(0,3))<6)
        {
            PDFJS.disableWorker = true;
        }
        
        // check Login

        var self = this;
        this.xojLogin(false, false, function(check) {
            self.loggedIn = false;
            if(typeof check == 'boolean')
            {
                if(check===true)
                {
                    self.loggedIn = true;
                }
            }
            else
            {
                self.loggedIn = true;
            }
        })
        
        this.openIntern(scale);
    },
    
    openIntern: function(scale, callBack)
    {
        var self = this;
        XOJReader.load({
            loader: self.xojLoader,
            error: function getPdfError() {
                var loadingIndicator = document.getElementById('loading');
                loadingIndicator.innerHTML = 'Error';
                var moreInfo = {
                    message: 'Unexpected server response!'
                };
                self.error('An error occurred while loading the XOJ.', moreInfo);
            },
            success: function success() {
                self.loading = true;
                self.load(scale, function() {
                    if(typeof callBack === "function")
                    {
                        callBack();
                    }
                });
                self.loading = false;
            }
        });
    },
    
    save: function()
    {
        if(!this.checkLogin($.proxy(this.saveCallback, this)))
        {
            return;
        }
    },
    
    saveCallback: function()
    {
        var saveData = {
            'displayname' : this.editName,
            'pages': {}
        };
        for(var pagenum in this.pages)
        {
            var layerKey = this.pages[pagenum].getEditLayer();
            
            if(layerKey!=-1 && layerKey!=-2)
            {
                var layerKey = this.pages[pagenum].layer[layerKey].layerKey;
                var layer = this.pages[pagenum].content.layer[layerKey];
                
                saveData.pages[parseInt(pagenum)+1] = layer.getLayerObject();
            }
        }
        
        this.xojSaver(saveData, function(success) {
            if(!success)
            {
                alert('Speichern fehlgeschlagen!');
                /** @todo error handling */
            }
        });
    },

    download: function()
    {
        window.open(this.url + '#pdfjs.action=download', '_parent');
    },

    navigateTo: function(dest)
    {
        if (typeof dest === 'string')
        {
            dest = this.destinations[dest];
        }
        if (!(dest instanceof Array))
        {
            return; // invalid destination
                    // dest array looks like that: <page-ref> </XYZ|FitXXX> <args..>
        }
        
        var destRef = dest[0];
        var pageNumber = destRef instanceof Object ? this.pagesRefMap[destRef.num + ' ' + destRef.gen + ' R'] : (destRef + 1);
        if (pageNumber)
        {
            this.page = pageNumber;
            var currentPage = this.pages[pageNumber - 1];
            currentPage.scrollIntoView(dest);
        }
    },

    getDestinationHash: function(dest)
    {
        if (typeof dest === 'string')
        {
            return '#' + escape(dest);
        }
        if (dest instanceof Array)
        {
            var destRef = dest[0]; // see navigateTo method for dest format
            var pageNumber = destRef instanceof Object ? this.pagesRefMap[destRef.num + ' ' + destRef.gen + ' R'] : (destRef + 1);
            if (pageNumber)
            {
                var pdfOpenParams = '#page=' + pageNumber;
                var destKind = dest[1];
                if ('name' in destKind && destKind.name == 'XYZ') {
                    var scale = (dest[4] || this.currentScale);
                    pdfOpenParams += '&zoom=' + (scale * 100);
                    if (dest[2] || dest[3])
                    {
                        pdfOpenParams += ',' + (dest[2] || 0) + ',' + (dest[3] || 0);
                    }
                    if(selectLayerCurrentValue!="")
                    {
                        pdfOpenParams += '&layer=' + selectLayerCurrentValue;
                    }
                }
                return pdfOpenParams;
            }
        }
        return '';
    },

    /**
    * Show the error box.
    * @param {String} message A message that is human readable.
    * @param {Object} moreInfo (optional) Further information about the error
    *                            that is more technical.  Should have a 'message'
    *                            and optionally a 'stack' property.
    */
    error: function(message, moreInfo)
    {
        var errorWrapper = document.getElementById('errorWrapper');
        errorWrapper.removeAttribute('hidden');

        var errorMessage = document.getElementById('errorMessage');
        errorMessage.innerHTML = message;

        var closeButton = document.getElementById('errorClose');
        closeButton.onclick = function() {
            errorWrapper.setAttribute('hidden', 'true');
        };

        var errorMoreInfo = document.getElementById('errorMoreInfo');
        var moreInfoButton = document.getElementById('errorShowMore');
        var lessInfoButton = document.getElementById('errorShowLess');
        moreInfoButton.onclick = function() {
            errorMoreInfo.removeAttribute('hidden');
            moreInfoButton.setAttribute('hidden', 'true');
            lessInfoButton.removeAttribute('hidden');
        };
        lessInfoButton.onclick = function() {
            errorMoreInfo.setAttribute('hidden', 'true');
            moreInfoButton.removeAttribute('hidden');
            lessInfoButton.setAttribute('hidden', 'true');
        };
        moreInfoButton.removeAttribute('hidden');
        lessInfoButton.setAttribute('hidden', 'true');
        errorMoreInfo.innerHTML = 'PDF.JS Build: ' + PDFJS.build + '\n';

        if (moreInfo)
        {
            errorMoreInfo.innerHTML += 'Message: ' + moreInfo.message;
            if (moreInfo.stack)
            {
                errorMoreInfo.innerHTML += '\n' + 'Stack: ' + moreInfo.stack;
            }
        }
    },

    progress: function(level)
    {
        var percent = Math.round(level * 100);
        var loadingIndicator = document.getElementById('loading');
        loadingIndicator.innerHTML = 'Loading... ' + percent + '%';
    },
    
    loadOutline: function (pdf)
    {
        var self = this;
        
        pdf.getDestinations().then(function(destinations) {
            self.destinations = destinations;
        });
        
        pdf.getOutline().then(function(outline) {
            if(!outline)
                return;
            self.outline = new DocumentOutlineView(outline);
            var outlineSwitchButton = document.getElementById('outlineSwitch');
            outlineSwitchButton.removeAttribute('disabled');
            self.switchSidebarView('outline');
        });
    },

    load: function(scale)
    {
        var loadCallback = null;
        if(arguments.length>1)
        {
            var loadCallback = arguments[1];
        }

        function bindOnAfterDraw(pageView, thumbnailView)
        {
            // when page is painted, using the image as thumbnail base
            pageView.onAfterDraw = function xojViewLoadOnAfterDraw() {
                thumbnailView.setImage(pageView);
            };
        }

        var errorWrapper = document.getElementById('errorWrapper');
        errorWrapper.setAttribute('hidden', 'true');

        var loadingIndicator = document.getElementById('loading');
        loadingIndicator.setAttribute('hidden', 'true');

        var sidebar = document.getElementById('sidebarView');
        sidebar.parentNode.scrollTop = 0;

        while (sidebar.hasChildNodes())
        {
            sidebar.removeChild(sidebar.lastChild);
        }

        if ('_loadingInterval' in sidebar)
        {
            clearInterval(sidebar._loadingInterval);
        }

        var container = document.getElementById('viewer');
        while (container.hasChildNodes())
        {
            container.removeChild(container.lastChild);
        }

        var pagesCount = XOJReader.numPages;
        document.title = XOJReader.getTitle();

        document.getElementById('numPages').innerHTML = pagesCount;
        document.getElementById('pageNumber').max = pagesCount;

        var waitFuncCallback = function(self, scale, loadCallback)
        {
            var pdfPages = [];
            for (var i = 1; i <= pagesCount; i++)
            {
                var page = XOJReader.getPage(i);

                var pageView = new PageView(container, page, i, page.width, page.height,
                page.stats, self.navigateTo.bind(self));

                /** @todo thumbnails */
                var thumbnailView = new ThumbnailView(sidebar, pageView, i,
                page.width / page.height);
                bindOnAfterDraw(pageView, thumbnailView);
                pageView.scale = scale;
                pages.push(pageView);
                thumbnails.push(thumbnailView);
                
                pdfPages.push(pageView.getPDFPage());
            }
            
            PDFJS.Promise.all(pdfPages).then(function(loadedPdfPages) {
                for(var i=0; i<loadedPdfPages.length; i++)
                {
                    var pdfPage = loadedPdfPages[i];
                    if(typeof pdfPage != 'boolean')
                    {
                        var pageRef = pdfPage.ref;
                        pagesRefMap[pageRef.num + ' ' + pageRef.gen + ' R'] = i;
                    }
                }
                
                self.pagesRefMap = pagesRefMap;
                self.setScale(scale || kDefaultScale, true);

                if (self.initialBookmark)
                {
                    self.setHash(self.initialBookmark);
                    self.initialBookmark = null;
                }
                else
                {
                    self.page = 1;
                }

                if(loadCallback)
                {
                    loadCallback();
                }
            });
        }

        var waitFunc = function(callback, loadCallback)
        {
            var pdfsToLoad = 0;
            var finished = false;
            var hasError = false;
            var filenames = [];

            var callbackFunc = callback;
            var loadCallbackFunc = loadCallback;

            this.add = function(filename)
            {
                filenames.push(filename);
                pdfsToLoad++;
            }
            
            this.has = function(filename)
            {
                var check = jQuery.inArray(filename, filenames);
                
                if(check===-1)
                {
                    return false;
                }
                
                return true;
            }

            this.sub = function(error)
            {
                if(error)
                {
                    hasError = true;
                }
                pdfsToLoad--;
                this.isReady();
            }

            this.finish = function()
            {
                finished = true;
                this.isReady();
            }

            this.isReady = function()
            {
                if(finished && pdfsToLoad==0)
                {
                    callbackFunc(hasError, loadCallbackFunc);
                }
            }
        }

        var self = this;
        var waitForPDF = new waitFunc(function(hasError, loadCallback) {
            if(hasError)
            {
                self.error('An error occurred while reading the PDF.');
            }
            else
            {
                waitFuncCallback(self, scale, loadCallback);
            }
        }, loadCallback);

        var pages = this.pages = [];
        var pagesRefMap = {};
        var thumbnails = this.thumbnails = [];
        var lastPDFFilename = '';

        for (var i = 1; i <= pagesCount; i++)
        {
            var page = XOJReader.getPage(i);

            switch(page.background.type)
            {
                case 'solid':
                    /** @todo implement */
                    break;
                case 'pixmap':
                    if(page.background.filename)
                    {
                        var pfilename = page.background.filename;
                        /*var last1 = pfilename.lastIndexOf('/');
                        var last2 = pfilename.lastIndexOf('\\');

                        if(last1>last2)
                        {
                            last2 = last1;
                        }

                        if(last2>-1)
                        {
                            pfilename = pfilename.substr(last2+1);
                        }*/

                        page.background.filename = pfilename;
                    }
                    break;
                case 'pdf':
                    if(page.background.filename)
                    {
                        var filename = page.background.filename;
                        /*var last1 = filename.lastIndexOf('/');
                        var last2 = filename.lastIndexOf('\\');

                        if(last1>last2)
                        {
                            last2 = last1;
                        }

                        if(last2>-1)
                        {
                            filename = filename.substr(last2+1);
                        } */

                        var self = this;

                        if(!waitForPDF.has(filename))
                        {
                            waitForPDF.add(filename);
                            /** @todo progress */

                            PDFJS.getDocument(filename).then(function(pdf) {
                                pdfcache.set(filename, pdf);
                                waitForPDF.sub();
                                self.loadOutline(pdf);
                            });

                            /*$.ajax({
                                url: filename,
                                dataType: 'binary',
                                async: true,
                                error: function(jqXHR, textStatus, errorThrown) {
                                    waitForPDF.sub(true);
                                    var loadingIndicator = document.getElementById('loading');
                                    loadingIndicator.innerHTML = 'Error';
                                    var moreInfo = {
                                        message: 'Unexpected server response of ' + e.target.status + '.'
                                    };
                                    self.error('An error occurred while loading the PDF.', moreInfo);
                                },
                                success: function(data, textStatus, jqXH) {
                                    try
                                    {
                                        if(!isArrayBuffer(data))
                                        {
                                            data = jqXH.response.buffer;
                                        }
                                        var pdf = new PDFJS.PDFDoc(data);
                                        pdfcache.set(filename, pdf);
                                        waitForPDF.sub();
                                        self.loadOutline(pdf);
                                    }
                                    catch (e)
                                    {
                                        self.error('An error occurred while reading the PDF.', e);
                                        waitForPDF.sub(true);
                                    }
                                } 

                            }); */
                        }
                        lastPDFFilename = filename;
                    }
                    page.background.filename = lastPDFFilename;
                    break;
            }
            XOJReader.setPage(i, page);
        }

        waitForPDF.finish();

    },

    setHash: function(hash)
    {
        if (!hash)
        {
            return;
        }

        if (hash.indexOf('=') >= 0)
        {
            // parsing query string
            var paramsPairs = hash.split('&');
            var params = {};
            for (var i = 0; i < paramsPairs.length; ++i)
            {
                var paramPair = paramsPairs[i].split('=');
                params[paramPair[0]] = paramPair[1];
            }
            // borrowing syntax from "Parameters for Opening PDF Files"
            if ('nameddest' in params)
            {
                xojView.navigateTo(params.nameddest);
                return;
            }
            if ('layer' in params)
            {
                var layerNumber = (params.layer | 0) || 1;
                firstLoadSelectedLayer = layerNumber;

                var pageNumber = -1;
                if ('page' in params)
                {
                    pageNumber = (params.page | 0) || 1;
                    firstLoadSelectedLayerPage = pageNumber;
                }

                updateLayerSelect(pageNumber);
            }
            if ('page' in params)
            {
                var pageNumber = (params.page | 0) || 1;
                this.page = pageNumber;
                if ('zoom' in params)
                {
                    var zoomArgs = params.zoom.split(','); // scale,left,top
                    // building destination array
                    var dest = [
                        null,
                        new Name('XYZ'),
                        (zoomArgs[1] | 0),
                        (zoomArgs[2] | 0),
                        (zoomArgs[0] | 0) / 100
                    ];
                    var currentPage = this.pages[pageNumber - 1];
                    currentPage.scrollIntoView(dest);
                }
                else
                {
                    this.page = params.page; // simple page
                }
                return;
            }
        }
        else if (/^\d+$/.test(hash)) // page number
        {
            this.page = hash;
        }
        else // named destination
        {
            XOJView.navigateTo(unescape(hash));
        }
    },

    switchSidebarView: function(view)
    {
        var thumbsScrollView = document.getElementById('sidebarScrollView');
        var outlineScrollView = document.getElementById('outlineScrollView');
        var thumbsSwitchButton = document.getElementById('thumbsSwitch');
        var outlineSwitchButton = document.getElementById('outlineSwitch');
        switch (view)
        {
            case 'thumbs':
                thumbsScrollView.removeAttribute('hidden');
                outlineScrollView.setAttribute('hidden', 'true');
                thumbsSwitchButton.setAttribute('data-selected', true);
                outlineSwitchButton.removeAttribute('data-selected');
                updateThumbViewArea();
                break;
            case 'outline':
                thumbsScrollView.setAttribute('hidden', 'true');
                outlineScrollView.removeAttribute('hidden');
                thumbsSwitchButton.removeAttribute('data-selected');
                outlineSwitchButton.setAttribute('data-selected', true);
                break;
        }
    },

    getVisiblePages: function()
    {
        var pages = this.pages;
        var kBottomMargin = 10;
        var visiblePages = [];

        var currentHeight = kBottomMargin;
        var windowTop = window.pageYOffset;
        for (var i = 1; i <= pages.length; ++i)
        {
            var page = pages[i - 1];
            var pageHeight = page.height * page.scale + kBottomMargin;
            delete page;
            
            if (currentHeight + pageHeight > windowTop)
            {
                delete pageHeight;
                break;
            }
            delete pageHeight;
            currentHeight += pageHeight;
        }

        var windowBottom = window.pageYOffset + window.innerHeight;
        for (; i <= pages.length && currentHeight < windowBottom; ++i)
        {
            var singlePage = pages[i - 1];
            visiblePages.push({
                id: singlePage.id,
                y: currentHeight,
                view: singlePage
            });
            delete singlePage;
            
            currentHeight += singlePage.height * singlePage.scale + kBottomMargin;
        }
        
        delete pages;
        delete kBottomMargin;
        delete currentHeight;
        delete windowTop;
        delete windowBottom;
        
        return visiblePages;
    },

    getVisibleThumbs: function()
    {
        var thumbs = this.thumbnails;
        var kBottomMargin = 5;
        var visibleThumbs = [];

        var view = document.getElementById('sidebarScrollView');
        var currentHeight = kBottomMargin;
        var top = view.scrollTop;
        for (var i = 1; i <= thumbs.length; ++i)
        {
            var thumb = thumbs[i - 1];
            var thumbHeight = thumb.height * thumb.scaleY + kBottomMargin;
            if (currentHeight + thumbHeight > top)
            {
                break;
            }

            currentHeight += thumbHeight;
        }

        var bottom = top + view.clientHeight;
        for (; i <= thumbs.length && currentHeight < bottom; ++i)
        {
            var singleThumb = thumbs[i - 1];
            visibleThumbs.push({
                id: singleThumb.id,
                y: currentHeight,
                view: singleThumb
            });
            currentHeight += singleThumb.height * singleThumb.scaleY + kBottomMargin;
        }

        return visibleThumbs;
    }
};