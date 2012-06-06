function xojLoad(options)
{
    var start = (options.start ? options.start : 0);
    var numberofpages = (options.numberOfPages ? options.numberOfPages : -1);
    
    var url = webournal_XOJLoadURL;
    
    if(url.charCodeAt(url.length)!=='/')
    {
        url += '/';
    }
    
    url += 'start/' + start + '/' + numberofpages + '/';
    
    $.ajax({
        url: url,
        dataType: 'json',
        data: {
            
        },
        success: function(data) {
            if(options.success)
            {
                options.success(data);
            }
        },
        error: function() {
            if(options.error)
            {
                options.error();
            }
        }
    });
}

function xojSave(saveData, callBack)
{
    var url = webournal_XOJSaveURL;
    
    $.ajax({
        type: 'POST',
        cache: false,
        url: url,
        dataType: 'json',
        data: {'data': JSON.stringify(saveData)},
        success: function(data) {
            if(data && data.success)
            {
                return callBack(true);
            }
            else
            {
                return callBack(false);
            }
        },
        error: function() {
            callBack(false);
        }
    });
}

function xojLogin(username, password, callBack)
{
    var senddata  = {};
    
    var type = 'GET';
    
    if(typeof username == "string" && typeof password == "string")
    {
        type = 'POST';
        senddata.login_user = username;
        senddata.login_password = password;
        senddata.group = webournal_XOJGroup;
    }
    
    var url = webournal_XOJRestLogin;
    
    if(type=='GET')
    {
        url += '?group=' + webournal_XOJGroup;
    }
    
    $.ajax({
        type: type,
        url: url,
        dataType: 'jsonp',
        data: senddata,
        crossDomain: true,
        success: function(data) {
            if(senddata.login_user)
            {
                if(data && data.success)
                {
                    return callBack(senddata.login_user);
                }
                else
                {
                    return callBack(false);
                }
            }
            else
            {
                if(data.data && data.data.logedin)
                {
                    return callBack(data.data.username);
                }
                else
                {
                    return callBack(false);
                }
            }
        },
        error: function() {
            callBack(false);
        }
    });
}

$(document).bind('webournalready', function() {
    $(document).webournal(xojLoad, xojSave, xojLogin);
});