$(document).ready(function()
{
    var geocoder;
    function initialize() 
    {
        geocoder = new google.maps.Geocoder();
    }

    $(document).on('click', 'button[name="plot_location"]', function(e)
    {
        e.preventDefault();
        var _this       = $(this);
        var address     = "";
        var totalInputs = _this.parents('.saf-wrapper:first').find('input[type="text"]').length;

        _this.parents('.saf-wrapper:first').find('input[type="text"]').each(function(index, el) 
        {
        	if($(this).val() != "" && (index) < (totalInputs-2))
        	{
        		address += $(this).val() + ', ';
        	}
        });
        
        address = address.substring(0,(address.length-2));
        geocoder.geocode({'address': address}, function(results, status) 
        {
           if (status == google.maps.GeocoderStatus.OK)
           {
               var lat = results[0].geometry.location.lat();
               var lon = results[0].geometry.location.lng();
               _this.parents('.saf-wrapper:first').find('div.latitude').find('input[type="text"]').val(lat);
               _this.parents('.saf-wrapper:first').find('div.longitude').find('input[type="text"]').val(lon);

               return false;
           }
           else
           {
               alert('The location you added was not a valid map location.');
           }
       });
    });

    initialize();
});