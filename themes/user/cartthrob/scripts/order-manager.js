$(function(){

	function setDate(shouldSubmit = false) {
		var rangePicker = $('select[name="date-range-select"]')
		var rangePickerVal = rangePicker.val()
		if(rangePickerVal.includes('---')) {
			var range = rangePickerVal.split('---')
			console.log('range', range)
			$('input[name="date_start"').datepicker('setDate', range[0])
			$('input[name="date_finish"').datepicker('setDate', range[1])
		}
	}


    if ($(".datepicker").size() > 0)
    {
        $(".datepicker").datepicker({dateFormat: "yy-mm-dd"});

        // Autofill
        setDate()
        $(document).on('change', 'select[name="date-range-select"]', function() {
        	setDate()
        })
    }

    $(document).on("change", "select[name=report]", function(){
        $(this).parents("form").submit();
    });

    $("#reports").show();
});