xid= "", csrf_token=""; label = true; ajaxCancell = false; cnt = 0;
$(document).ready(function() {
    se_url = se_url.replace('&amp;', '&')
    
    $(document).on('change','select#procedure',function(e){
        if($(this).val() == "ajax"){
            $(this).parents('fieldset:first').removeClass('last')
            $('.batches_wrapper').show();
        }else{
            $(this).parents('fieldset:first').addClass('last')
            $('.batches_wrapper').hide();
        }
    });
    $('select#procedure').trigger('change');
    
    $(document).on('click','.se_boxes',function(e){
        if($(this).hasClass('active') === true){
            $(this).removeClass('active')
            $(this).find('input[type="checkbox"]').prop('checked',false)
        }else{
            $(this).addClass('active')
            $(this).find('input[type="checkbox"]').prop('checked',true)
        }
        selectBoxes();
    });
    
    $(document).on('change','.se-radio input[type="radio"]',function(e){
        $(this).parents('.se-radio:first').find('label.choice').removeClass('chosen');
        $(this).parents(".toggle-item:first").find('input[type="radio"]:checked').parent('label').addClass('chosen');

        if($(this).parents(".toggle-item:first").length && typeof($(this).parents(".toggle-item:first").attr('toggle') !== "undefined"))
        {
            var classs = $(this).parents(".toggle-item:first").attr('toggle');
            if($(this).parents(".toggle-item:first").find('input[type="radio"]:checked').val() == "y")
            {
                $('.'+classs).show()
                $(this).parents('.se-radio').removeClass('last');
            }
            else
            {
                $(this).parents('.se-radio').addClass('last');
                $('.'+classs).hide()
            }
        }
    });
    $('.se-radio input[type="radio"]').trigger('change');

    $(document).on('click','select.se_inside_select',function(e){
        e.stopPropagation();
    });
    $(document).on('change','#se_channel_01',function(a,b){

        $('.loading-indicator').show()
        var seChannelThis = $(this);
        var my_class = seChannelThis.parents('.se_entry:first').children('.se_channel_recieve').find('.se_channel_fields')
        // if($(this).val() != ""){
            $.ajax({
                crossDomain: !0,
                type: "GET",
                dataType: "json",
                url: se_url,
                data: {
                    'channel_id': $(this).val(),
                    'is-ajax': true,
                },
                success: function(response) {
                    if(typeof(response.error) !== "undefined" && response.error != null && response.error != ""){
                        $('.field-wrapper').hide();
                        my_class.html('');
                        $('.loading-indicator').hide();
                        $('.error-message').show().children().html(response.error);
                        return false;
                    }
                    /*$groupExists = true;
                    if(typeof(response.error) !== "undefined" && response.error != null && response.error != ""){
                        $groupExists = false;
                    }*/

                    /*if(response.result.length > 0)
                    {*/
                        $('.error-message').hide();
                        statuses = response.status;
                        xid = response.xid;
                        csrf_token = response.csrf_token;

                        $('#se_status').html('<option value="ALL" selected="">ALL</option>')
                        _data = "";
                        for (var j = 0; j < statuses.length; j++) {
                            _data += "<option value='"+statuses[j]['status']+"'>"+statuses[j]['status']+"</option>";
                        }
                        $('#se_status').append(_data);
                            
                        
                        my_class.html('');
                        var channelFieldsExists = false;
                        if(typeof(response.result) !== "undefined" && response.result != null && response.result != ""){
                            channelFieldsExists = true;
                            var data = "";
                            for (var i = 0; i < response.result.length; i++) {
                                data += '<div class="se_boxes"><span>&#10004</span><input type="checkbox" class="check_fields" name="settings[custom_fields][]" value="' + response.result[i].field_id + '" /> ' + response.result[i].field_label + ' (' + response.result[i].field_type + ')';
                                if(response.result[i].field_type == "relationship")
                                {
                                    data += '<div class="se-inside_main"><div class="se_inside_rel relation_' + response.result[i].field_id + '"> <label>(To Identify Relationships When Import)</label>  <select class="se_inside_select" name="settings[relationship_field]['+response.result[i].field_id+']"> <option value="title">Title</option> <option value="url_title">Url Title</option> <option value="entry_id">Entry ID</option> </select> </div></div>'; 
                                }
                                else if(response.result[i].field_type == "fluid_field")
                                {
                                    test = response.result[i].rel;
                                    if(typeof(response.result[i].rel) !== "undefined" && Object.keys(response.result[i].rel).length > 0)
                                    {
                                        data += '<div class="se-inside_main">';
                                        if(typeof(response.result[i].rel.relationship) !== "undefined" && response.result[i].rel.relationship.length > 0)
                                        {
                                            for (var j = 0; j < response.result[i].rel.relationship.length; j++)
                                            {
                                                data += '<div class="se_inside_rel relation_'+response.result[i].rel.relationship[j].field_id+'">';
                                                    data += '<label>';
                                                        data += '<em>'+response.result[i].rel.relationship[j].field_label+'</em> ';
                                                        data += '(To Identify Relationships When Import)';
                                                    data += '</label> ';
                                                    data += '<select class="se_inside_select" name="settings[fluid_field]['+response.result[i].field_id+']['+response.result[i].rel.relationship[j].field_id+']">';
                                                        data += '<option value="title">Title</option>';
                                                        data += '<option value="url_title">Url Title</option>';
                                                        data += '<option value="entry_id">Entry ID</option>';
                                                    data += '</select>';
                                                data += '</div>';
                                            }
                                        }
                                        if(typeof(response.result[i].rel.grid_rel) !== "undefined" && response.result[i].rel.grid_rel.length > 0)
                                        {
                                            for (var j = 0; j < response.result[i].rel.grid_rel.length; j++)
                                            {
                                                data += '<div class="se_inside_rel relation_'+response.result[i].rel.grid_rel[j].col_id+'">';
                                                    data += '<label>';
                                                        data += '<em>'+response.result[i].rel.grid_rel[j].col_label+'</em>';
                                                        data += '(To Identify Relationships When Import)';
                                                    data += '</label> ';
                                                    data += '<select class="se_inside_select" name="settings[fluid_field]['+response.result[i].field_id+']['+response.result[i].rel.grid_rel[j].col_id+']">';
                                                        data += '<option value="title">Title</option>';
                                                        data += '<option value="url_title" selected="">Url Title</option>';
                                                        data += '<option value="entry_id">Entry ID</option>';
                                                    data += '</select>';
                                                data += '</div>';
                                            }
                                        }
                                        data += '</div>';
                                    }
                                }
                                else if(response.result[i].field_type == "playa")
                                {
                                    data += '<div class="se-inside_main"><div class="se_inside_rel relation_' + response.result[i].field_id + '"> <label>(To Identify Relationships When Import)</label>  <select class="se_inside_select" name="settings[playa_field]['+response.result[i].field_id+']"> <option value="title">Title</option> <option value="url_title">Url Title</option> <option value="entry_id">Entry ID</option> </select> </div></div>'; 
                                }
                                else if(response.result[i].field_type == "grid" && response.result[i].grid_rel != "NA") 
                                {
                                    for (var j = 0; j < response.result[i].grid_rel.length; j++) 
                                    {
                                        if(j == 0){
                                            data += '<div class="se-inside_main">';
                                        }
                                        data += '<div class="se_inside_rel relation_'+response.result[i].grid_rel[j].col_id+'"> <label><em>'+response.result[i].grid_rel[j].col_label+'</em> (To Identify Relationships When Import)</label>  <select class="se_inside_select" name="settings[grid_relationship]['+response.result[i].field_id+']['+response.result[i].grid_rel[j].col_id+']"> <option value="title">Title</option> <option value="url_title">Url Title</option> <option value="entry_id">Entry ID</option> </select> </div>';
                                        if(j == response.result[i].grid_rel.length - 1)
                                        {
                                            data += '</div>';
                                        }
                                    }
                                }
                                else if(response.result[i].field_type == "matrix" && response.result[i].matrix_rel != "NA") 
                                {
                                    for (var j = 0; j < response.result[i].matrix_rel.length; j++) 
                                    {
                                        if(j == 0){
                                            data += '<div class="se-inside_main">';
                                        }
                                        data += '<div class="se_inside_rel relation_'+response.result[i].matrix_rel[j].col_id+'"> <label><em>'+response.result[i].matrix_rel[j].col_label+'</em> (To Identify Relationships When Import)</label>  <select class="se_inside_select" name="settings[matrix_playa]['+response.result[i].field_id+']['+response.result[i].matrix_rel[j].col_id+']"> <option value="title">Title</option> <option value="url_title">Url Title</option> <option value="entry_id">Entry ID</option> </select> </div>';
                                        if(j == response.result[i].matrix_rel.length - 1)
                                        {
                                            data += '</div>';
                                        }
                                    }
                                }
                                data += '</div>';
                            };
                            my_class.append(data);
                        }
                        selectBoxes();
                        if(typeof(response.categories) !== "undefined" && response.categories === true){
                            $('.wrap-cats').show();
                        }else{
                            $('.wrap-cats').hide();
                        }
                        $('.field-wrapper').show();
                        
                        if($('.other-general-fields').find('.se_boxes:visible').length == 0){
                            $('.other-general-fields').hide();
                        }
                        
                        if(channelFieldsExists === false){
                            $('.se_channel_fields').parents('.field-wrapper:first').hide();
                        }
                        $('.loading-indicator').hide();
                    /*}
                    else
                    {
                        $('.field-wrapper').hide();
                        my_class.html('');
                        $('.loading-indicator').hide()
                    }*/
                },
                error:function(jqXhr){
                    $('.field-wrapper').hide();
                    my_class.html('');
                    $('.loading-indicator').hide()
                }
            });
        // }
    });

    $(document).on('change','.check_all',function(){
        if($(this).prop('checked') == true)
        {
            $(this).parents('.field-wrapper').find('.d-fields').children('.se_boxes').addClass('active').children('input[type="checkbox"]').prop('checked', true);
        }
        else
        {
            $(this).parents('.field-wrapper').find('.d-fields').children('.se_boxes').removeClass('active').children('input[type="checkbox"]').prop('checked', false);
        }
    })
    
    $(document).on('click', '.passkey', function(event) {
        event.preventDefault();
        link = $(this).attr('copy-link');
        $('.main-title').removeClass('hidden');
        $('.download-title').addClass('hidden');
        $('#sm-modal').find('.paste-content').html("<span id='sm_copy_link'>" + $(this).attr('copy-link') + "</span>");
        $('#sm-modal').find('.move-rigth').show();
        $('#sm-modal').find('.copy_clip').attr('copy-content', $(this).attr('copy-link'));
        $('.overlay').show().removeClass('remove-pointer-events');
        $('#sm-modal').fadeIn();
        $('#sm_copy_link').OneClickSelect();
    });

    $(document).on('click', '.ajax-download', function(event) {
        event.preventDefault();
        ajax_url = $(this).attr('href');
        $('.main-title').addClass('hidden');
        $('.download-title').removeClass('hidden');
        $('#sm-modal').find('.paste-content').html('<h4><img src="' + loadingImage + '" style="vertical-align: middle;"> &nbsp;Creating export file. <span id="perc-calc">0%</span></h4>');
        $('#sm-modal').find('.move-rigth').hide();
        $('.overlay').show().addClass('remove-pointer-events');
        $('#sm-modal').fadeIn();
        if(label === true){
            label = false;
        }else{
            alert("Function is currently in progress!");
            return false;
        }
        
        ajaxCancell = false;
        runAjax(ajax_url + '&type=ajax');

        return false;
    });

    $(document).on("click", ".m-close-sm-export", function(t){
        t.preventDefault();
        if(label === false){
            if(confirm('Export is in progress. Do you want to close it anyway?')){
                ajaxCancell = true;
                label = true;
                $(this).closest(".modal-wrap, .modal-form-wrap").trigger("modal:close");
                return true;
            }else{
                return false;
            }
        }else{
            $(this).closest(".modal-wrap, .modal-form-wrap").trigger("modal:close");
        }
    });

    $(document).on('click', '.copy_clip', function(event)
    {
        var aux = document.createElement("input");
        aux.setAttribute("value", $(this).attr('copy-content'));
        document.body.appendChild(aux);
        aux.select();
        document.execCommand("copy");
        document.body.removeChild(aux);
    });

    $('.smart-export-table-wrapper th').each(function(index, el) {
        if($(this).hasClass('field-table-export_counts')) {
            cnt = index+1;
            return false;
        }
    });

    $(document).on('click', '.download-export', function(event) 
    {
        dwn = Number($(this).parents('tr:first').children('td:nth-child('+cnt+')').html()) + 1;
        $(this).parents('tr:first').children('td:nth-child('+cnt+')').html(dwn)
    });

    $(document).on('click', 'a.delete', function(event) {
        event.preventDefault();
        if(confirm("Do you want to delete this export setting ?"))
        {
            _this = $(this);
            $.post($(this).attr('href'), {param1: 'value1'}, function(data, textStatus, xhr) {
                if(textStatus == "success")
                {
                    _this.parent().parent('tr').remove()

                    if($('.inside-table').children('table').children('tbody').children('tr').length == 0)
                    {
                        $('.inside-table').hide()
                        $('.tableFooter').hide()
                        $('.no_data').show()
                    }
                }
            });
        }
    });
    
    selectBoxes();

});

function selectBoxes(){
    $('.check_all').each(function(index, el) {
        if($(this).parents('.field-wrapper').find('.d-fields').find('input[type="checkbox"]:not(:checked)').length == 0){
            $(this).prop('checked', true);
        }else{
            $(this).prop('checked', false);
        }
    });
}

function setUrlParameter(url, key, value) {
    var parts = url.split("#", 2), anchor = parts.length > 1 ? "#" + parts[1] : '';
    var query = (url = parts[0]).split("?", 2);
    if (query.length === 1) 
        return url + "?" + key + "=" + value + anchor;

    for (var params = query[query.length - 1].split("&"), i = 0; i < params.length; i++){
        if (params[i].toLowerCase().startsWith(key.toLowerCase() + "=")){
            return params[i] = key + "=" + value, query[query.length - 1] = params.join("&"), query.join("?") + anchor;
        }
    }
    return url + "&" + key + "=" + value + anchor;
}

function runAjax(callUrl)
{

    if(ajaxCancell == true){
        ajaxCancell = false;
        label = true;
        return false;
    }

    $.ajax({
        url: callUrl,
        type: 'POST',
    })
    .success(function(data) {
        data = atob(data);
        data = JSON.parse(data);
        if(typeof(data.error) !== "undefined"){
            label = true;
            ajaxCancell = false;
            alert(data.error);
            $(".m-close-sm-export").closest(".modal-wrap, .modal-form-wrap").trigger("modal:close");
            return false;
        } else {
            if(data.status == "pending"){
                $done = (data.offset * 100) / data.totalrows;
                $('#perc-calc').html(parseInt($done) + "%");
                callUrl = setUrlParameter(callUrl, "offset", data.offset);
                runAjax(callUrl);
            }else{
                $('#sm-modal').find('.paste-content').html('Export file created. You can download it from <a href="' + data.url + '" download="" >here</a>');
                $('.overlay').removeClass('remove-pointer-events');
                label = true;
            }
        }
    })
    .fail(function(data) {
        label = true;
        ajaxCancell = false;
        $(".m-close-sm-export").closest(".modal-wrap, .modal-form-wrap").trigger("modal:close");
        alert("Something went wrong! Please check for console errors");
    })

}

$.fn.OneClickSelect = function () {
    return $(this).on('click', function () {
        var range, selection;

        if (window.getSelection) {
            selection = window.getSelection();
            range = document.createRange();
            range.selectNodeContents(this);
            selection.removeAllRanges();
            selection.addRange(range);
        } else if (document.body.createTextRange) {
            range = document.body.createTextRange();
            range.moveToElementText(this);
            range.select();
        }
    });
};