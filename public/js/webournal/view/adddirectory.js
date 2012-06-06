$(function() {
    $('select#type').change(function () {
        if($(this).val()=='date')
        {
            $('tr#type_date').show();
        }
        else
        {
            $('tr#type_date').hide();
        }
    });
});