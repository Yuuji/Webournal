var ThumbnailView = function thumbnailView(container, page, id, pageRatio)
{
    var anchor = document.createElement('a');
    anchor.href = '#' + id;
    anchor.onclick = function stopNivigation() {
        XOJView.page = id;
        return false;
    };
    
    var view = page;
    this.width = view.width;
    this.height = view.height;
    this.id = id;

    var maxThumbSize = 134;
    var canvasWidth = pageRatio >= 1 ? maxThumbSize : maxThumbSize * pageRatio;
    var canvasHeight = pageRatio <= 1 ? maxThumbSize : maxThumbSize / pageRatio;
    var scaleX = this.scaleX = (canvasWidth / this.width);
    var scaleY = this.scaleY = (canvasHeight / this.height);

    var div = document.createElement('div');
    div.id = 'thumbnailContainer' + id;
    div.className = 'thumbnail';

    anchor.appendChild(div);
    container.appendChild(anchor);

    this.hasImage = false;

    function getPageDrawContext()
    {
        var canvas = $('#thumbnail' + id);
        if(canvas.length>0)
        {
            canvas = canvas[0];
        }
        else
        {
            var canvas = document.createElement('canvas');
            canvas.id = 'thumbnail' + id;
            canvas.mozOpaque = true;

            canvas.width = canvasWidth;
            canvas.height = canvasHeight;

            div.setAttribute('data-loaded', true);
            div.appendChild(canvas);
        }

        var ctx = canvas.getContext('2d');
        ctx.save();
        ctx.fillStyle = 'rgb(255, 255, 255)';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        ctx.restore();

        var view = page;
        ctx.translate(-view.content.x * scaleX, -view.content.y * scaleY);
        div.style.width = (view.width * scaleX) + 'px';
        div.style.height = (view.height * scaleY) + 'px';
        div.style.lineHeight = (view.height * scaleY) + 'px';

        return ctx;
    }

    this.draw = function thumbnailViewDraw()
    {
        if (this.hasImage)
        {
            return;
        }

        var ctx = getPageDrawContext();
        /** @todo don't work :( */
        if (XOJView.pages[page.id - 1].draw())
        {
            cache.push(page.view);
        }
        var self = this;
        $(this).delay(100).queue(function() {
            self.setImage(page);
        });
        //page.startRendering(ctx, function thumbnailViewDrawStartRendering() {});

        this.hasImage = true;
    };

    this.setImage = function thumbnailViewSetImage(pageView)
    {
        var ctx = getPageDrawContext();
        $(pageView.div).children('canvas').each(function() {
            if($(this).is(":visible"))
            {
                ctx.drawImage(this, 0, 0, this.width, this.height,
                0, 0, ctx.canvas.width, ctx.canvas.height);
            }
        });
        this.hasImage = true;
    };
};