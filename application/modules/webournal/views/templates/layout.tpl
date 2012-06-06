<!DOCTYPE html>
<html>
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Webournal</title>
        <script type="text/javascript" src="/js/jquery-1.6.1.min.js"></script>
        <script type="text/javascript" src="/js/jquery-ui-1.8.13.custom.min.js"></script>
        <script type="text/javascript" src="/js/jquery.tmpl.min.js"></script>
        <script type="text/javascript" src="/js/jquery.tooltip.min.js"></script>

        {$this->javascriptHelper()}{$this->headScript()}

        <link href="/css/reset.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="/css/ui-lightness/jquery-ui-1.8.13.custom.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="/css/jquery.fileupload-ui.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="/css/jquery.tooltip.css" media="screen" rel="stylesheet" type="text/css" />
        <link href="/css/style.css" media="screen" rel="stylesheet" type="text/css" />

        {$this->cssHelper()}{$this->headLink()}

        {$this->controllerHelper()}
</head>
<body>
    {include file="header.tpl"}
    <div id="content">
        {$this->layout()->content}
    </div>
    {include file="footer.tpl"}
</body>
</html>