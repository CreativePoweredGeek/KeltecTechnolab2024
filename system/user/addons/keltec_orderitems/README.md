This custom Plugin is designed to retreive all CartThrob Orders Order Items field data from the exp_cartthrob_order_items table and output them as a JSON array as an endpoint for RDI to access and transfer the data into the KELTEC VAM system.


# Use

This plugin is called from the scripts->order_items_json template

The url looks like: 
https://keltecinc.com/script/order_items_json/k5ksnhyqwba@jg78hj!ju87@j/{order_id}

## Notes:
segment_3 of the URL is a key that must be passed from the RDI Script to prevent unauthorized access to the script. The value should always be: k5ksnhyqwba@jg78hj!ju87@j

last_segment is the Order ID of all the items to be retreived.

## Version History

### Version 1.0 - 10/27/20
Initial implementation of the Plugin.