This Custom Plugin is a collection of functions directed at Shipping information. This plugin replaced kt_shipping.



# estimated_date_alt()

This function calculates shipping dates within the KELTEC eCommerce system. The plugin calculates shipping date based on the Product entry_id and the warehouse passed into the plugin. 



## Use

```{exp:keltec_shipping:estimated_date warehouse="" entry_id=""}```

### Parameters:
__warehouse:__ You can pass in the 

* Customers preferred warehouse,  
* The selected warehouse from within the cart,  

or any of the following static values: 
* MAIN 
* TX 
* NC
* CA.

__entry_id:__ This has to be the product entry ID



# tracking_links()
This function creates links to FedEx and UPS based on the Tracking link passed back into the website from VAM after an item has been shiped.

This function is used in the Orders Page in the KELETC Website.


## Version History:

### Version 1.3  11/13/2020 
#### skip_holiday()
Updated to use dates from channel entries so client can update them from the control panel.

Fixed a looping issue where the script was not looping through a block of dates correctly for long holidays (implemented a do{}while() loop)

### Version 1.2  10/11/2020 

#### get_mfg_lead_time()
If the Maunfacturers Lead time comes back as '0', we need to add 5 business days to the ship date. This came about because although all products are supposed to have a manufacturers lead time, many do not and are causing the Expected Ship date to return as the Current day.

#### convert_to_weekday()
Discovered a bug where, the function was not using the actual calculated date passed into it from other rules to generate the date. It was setting it from the Current day of weekend it was being checked. EX: if on a Saturday, the shipdate was the following Saturday, it was using 'saturday' to generate a date based off the current day.