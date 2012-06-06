function include(file, callback)
{
    var script = document.createElement('script');
    
    if(typeof callback == 'function')
    {
        $(script).load(callback);
    }

    var head = document.getElementsByTagName('head')[0];
    head.appendChild(script);
    
    $(script).attr('type', 'text/javascript');
    $(script).attr('src', file);
    
}

$(document).ready(function() {
    var path = '/js/viewer/viewer/';
    /** @todo version management */
    var now = new Date();
    //var ext  = '.js?version=' + now.getTime();
    var ext = '.js';
    var files = [
        'apprise-1.5.min',
        'rgbcolor',
        'string',
        'colorpicker',
        'undomanager',
        'compatibility',
        'pdf',
        'xoj',
        'helper',
        'xojview',
        'pageview',
        'thumbnailview',
        'documentoutlineview',
        'pagehelper',
        'textlayer'
    ];

    var preload = ['defaults'];
    var loader = ['loader'];
    
    var preloaded = 0;
    var loaded = 0;
    
    var loadLoader = function () {
        loaded++;

        if(loaded==files.length)
        {
            for(var lkey in loader)
            {
                include(path + loader[lkey] + ext);
            }
        }
    };
    
    for(pkey in preload)
    {
        include(path + preload[pkey] + ext, function() {
            preloaded++;
            if(preloaded==preload.length)
            {
                for(key in files)
                {
                    include(path + files[key] + ext, loadLoader);
                }
            }
        });
    }
    
});