label = true;
$(document).ready(function() {

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

    $('.sl_setting_form h2').append('<em class="submenu-caret toggled"></em>');

    //For sliding .content box shared form
    $(document).on('click', '.sl_setting_form h2', function(event)
    {
        event.preventDefault();
        if($(this).hasClass('close-data')){
            $(this).addClass('open').removeClass('close-data')
            $(this).children('em').addClass('toggled')
            $(this).next().slideDown();
        }
        else{
            $(this).addClass('close-data').removeClass('open')
            $(this).children('em').removeClass('toggled')
            $(this).next().slideUp();
        }

    });
    
    //wrape to all fieldset tag
    $('.sl_setting_form h2').each(function(index, el){ 
        $(this).nextUntil(".last_fieldset, h2").wrapAll('<div class="sm-social-feed" style="display: none;"/>');
    });
    
    $('em.submenu-caret').each(function(index, el) {
        label = false;
        $(this).parent().next().find('input.chk_for_filter').each(function(index, el) {
            if($(this).val().length > 0){
                label = true;
                return false;
            }
        });

        if($('.sl_setting_form').hasClass('all-open'))
        {
            label = true;
        }

        if(label){
            $(this).addClass('toggled');
            $(this).parent().next().show()
            $(this).parent().removeClass('close-data')
        }else{
            $(this).removeClass('toggled');
            $(this).parent().next().hide()
            $(this).parent().addClass('close-data')
        }
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
        $('.app-overlay').addClass('app-overlay---open'); // ee6
        $('.main-title').removeClass('hidden');
        $('.download-title').addClass('hidden');
        $('#sm-modal').find('.paste-content').html("<span id='sm_copy_link'>" + $(this).attr('copy-link') + "</span>");
        $('#sm-modal').find('.move-rigth').show();
        $('#sm-modal').find('.copy_clip').attr('copy-content', $(this).attr('copy-link'));
        $('.overlay').show().removeClass('remove-pointer-events');
        $('#sm-modal').fadeIn();
        $('#sm_copy_link').OneClickSelect();
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

    $(document).on('click', '.copy_clip', function(event) {
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

    selectBoxes();
    
});

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
function selectBoxes(){
    $('.check_all').each(function(index, el) {
        if($(this).parents('.field-wrapper').find('.d-fields').find('input[type="checkbox"]:not(:checked)').length == 0){
            $(this).prop('checked', true);
        }else{
            $(this).prop('checked', false);
        }
    });
}