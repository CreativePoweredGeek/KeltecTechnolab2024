var process_on = false;
var ajaxCancel = false;
$(document).ready(function() {
	$('.super_channel_id').on('change', 'input[name="channel_id"]', function(event) {
		event.preventDefault();
		var _this = $(this);

		$.ajax({
			url: superExportURL,
			type: 'POST',
			dataType: 'json',
			data: {channel_id: _this.val()},
		})
		.done(function(data, status, xhr) {
			$.each(data, function(index, val) {
				$('.' + index).html(val);
			});
			Relationship.renderFields();
			SelectField.renderFields();
		})
		.fail(function() {
			alert('There is some issue in fetching channel fields. Please refresh and try again.')
		})
		.always(function() {
			// console.log("complete");
		});
	});

	$(document).on('click', '.super_export_download.ajax', function(event) {
		event.preventDefault();
		var _this = $(this);

		if(process_on)
		{
			return false;
			alert('One download is already in process. Please refresh and try again!');
		}
		else
		{
			process_on = true;
			$('.spinner').show();
			$('.download_export').attr('href', "").addClass('hidden');
			$('.export-percent').html("0%");

			$('.app-overlay').addClass('app-overlay---open')
					.removeClass('app-overlay---closed')
					.removeClass('app-overlay--destruct')
					.removeClass('app-overlay--warning');
			$('#super-export-modal-ajax-export').fadeIn();

			ajaxExport(_this.attr('href'));
		}
	});

	$(document).on("modal:close", '.modal-wrap', function(event) {
		var modal = $(this);

		if (modal.is(":visible")) {
			if(process_on)
			{
				event.preventDefault();
				event.stopImmediatePropagation();
				return false;

			}

			// fade out the overlay
			$('.overlay').fadeOut('slow');

			if (modal.hasClass('modal-wrap')) {
				modal.fadeOut('fast');
			} else {
				// disappear the app modal
				modal.addClass('app-modal---closed');
				setTimeout(function() {
					modal.removeClass('app-modal---open');
				}, 500);

				if (modal.hasClass('app-modal--live-preview')) {
					// disappear the preview
					$('.live-preview---open').addClass('live-preview---closed');
					setTimeout(function() {
						$('.live-preview---open').removeClass('live-preview---open');
					}, 500);
				}
			}

			// distract the actor
			$('.app-overlay---open').addClass('app-overlay---closed');
			setTimeout(function() {
				$('.app-overlay---open').removeClass('app-overlay---open')
					.removeClass('app-overlay--destruct')
					.removeClass('app-overlay--warning');
			}, 500);

			// replace the viewport scroll, if needed
			setTimeout(function() {
				$('body').css('overflow','');
			}, 200);

			if ( ! $(this).is('.modal-form-wrap, .app-modal'))
			{
				$(document).scrollTop($(this).data('scroll'));
			} else {
				// Remove viewport scroll
				$('body').css('overflow','hidden');
			}

			var button = $('.form-ctrls .button', this);
			button.removeClass('work');
			button.val(button.data('submit-text'));
		}
	});

	function ajaxExport(url)
	{
		$.ajax({
			url: url,
			type: 'GET',
			cache: false,
			dataType: 'json',
			data: {type: 'ajax'},
		})
		.done(function(data, type, xhr) {

			if(ajaxCancel)
			{
				ajaxCancel = false;
				process_on = false;
				$('.spinner').hide();
				return false;
			}

			if(data.status == "error")
			{
				alert(data.error);
			}
			else
			{

				var percent = ((100 * data.offset) / data.total).toFixed(2);
				$('.export-percent').html(percent + "%");

				if(data.status == "pending")
				{
					ajaxExport(data.next_batch);
				}
				else
				{
					process_on = false;
					$('.download_export').attr('href', data.url).attr('download', "").removeClass('hidden');
					$('.spinner').hide();
				}
			}
		})
		.fail(function() {
			console.log("error");
			$('.spinner').hide();
		})
		.always(function() {
			console.log("complete");
		});

	}

	$(document).on("click", ".m-close-custom", function(t){
	    t.preventDefault();
	    if(process_on)
	    {
	        if(confirm('One export is in progress. Do you really want to close it anyway?'))
	        {
	            ajaxCancel = true;
	            process_on = false;
	            $(this).closest(".modal-wrap, .modal-form-wrap").trigger("modal:close");
	            $('.app-overlay---open').removeClass('app-overlay---open')
					.removeClass('app-overlay--destruct')
					.removeClass('app-overlay--warning');
	            return true;
	        }
	        else
	        {
	            return false;
	        }
	    }
	    else
	    {
	        $(this).closest(".modal-wrap, .modal-form-wrap").trigger("modal:close");
	        $('.app-overlay---open').removeClass('app-overlay---open')
					.removeClass('app-overlay--destruct')
					.removeClass('app-overlay--warning');
	    }
	});

	$(document).on('click', '.passkey', function(event) {
		event.preventDefault();
		var _this = $(this);
		var copyUrl = _this.attr('copy-url');

		$('#super-export-modal-copy-clipboard').find('.copy-clipboard').html(copyUrl);
		$('#super-export-modal-copy-clipboard').find('.copy_to_clipboard_btn').attr('content', copyUrl);
		$('.copy-clipboard').selectAllOnClick();

		$('.app-overlay').addClass('app-overlay---open')
					.removeClass('app-overlay---closed')
					.removeClass('app-overlay--destruct')
					.removeClass('app-overlay--warning');
		$('#super-export-modal-copy-clipboard').fadeIn();
	});

	$(document).on('click', '.copy_to_clipboard_btn', function(event) {
		event.preventDefault();
	    var temp = document.createElement("input");
	    temp.setAttribute("value", $(this).attr('content'));
	    document.body.appendChild(temp);
	    temp.select();

	    document.execCommand("copy");
	    document.body.removeChild(temp);

	    $('.copy-clipboard').click();
	});

	$.fn.selectAllOnClick = function () {
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
});