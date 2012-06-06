var kDefaultScale = 1.5;
var kDefaultScaleDelta = 1.1;
var kCacheSize = 20;
var kCssUnits = 96.0 / 72.0;
var kScrollbarPadding = 40;
var kMinScale = 0.25;
var kMaxScale = 4.0;

var firstLoadSelectedLayer = -1;
var firstLoadSelectedLayerPage = -1;

var kThicknessPen = {
    'very thin': 0.42,
    'thin': 0.85,
    'medium': 1.41,
    'thick': 2.26,
    'very thick': 5.67
}

var kThicknessEraser = {
    'very thin': 2.83,
    'thin': 2.83,
    'medium': 8.50,
    'thick': 19.84,
    'very thick': 19.84
}

var kThicknessHighlighter = {
    'very thin': 2.83,
    'thin': 2.83,
    'medium': 8.50,
    'thick': 19.84,
    'very thick': 19.84
}

var kColors =
{
    'black': '#000000',
    'blue': '#3333cc',
    'red': '#ff0000',
    'green': '#008000',
    'gray': '#808080',
    'lightblue': '#00c0ff',
    'lightgreen': '#00ff00',
    'magenta': '#ff00ff',
    'orange': '#ff8000',
    'yellow': '#ffff00',
    'white': '#ffffff'
};


var kBgColors = 
{
    'blue': '#a0e8ff',
    'pink': '#ffc0d4',
    'green': '#80ffc0',
    'orange': '#ffc080',
    'yellow': '#ffff80',
    'white': '#ffffff'
};

var RULING_GRAPHSPACING = 14.17;
var RULING_THICKNESS = 0.5;
var RULING_COLOR = 'rgb(64, 160, 255)';
var RULING_TOPMARGIN = 80.0;
var RULING_LEFTMARGIN = 72.0;
var RULING_SPACING = 24.0;
var RULING_MARGIN_COLOR = 'rgb(255, 0, 128)';

var TEXT_DEFAULT_FONT = 'sans-serif';
var TEXT_DEFAULT_FONTSIZE = 12;
var TEXT_DEFAULT_FONT_LINE_ADD = 2;