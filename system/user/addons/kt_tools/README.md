A custom plugin for the Keltec ecommerce website to handle various functions

# reduce_warehouse_inventory
This plugin updates the individual warehouse inventories when an order has been placed.

__Parameters__:

* quantity = Quantity of the product purchased  
* product_id = Entry ID of the Product Purchased  
* warehouse = The Warehouse the Product is specified to be shipped from (MAIN, TX, NC, CA)  

__Use__:  
{exp:kt_tools:reduce_warehouse_inventory quantity="" product_id="" warehouse=""}

# get_users_csr_email  
This method fetches the Email address of the CSR assigned to the Customer. It queries the appropriate tables by passing in the {logged_in_member_id}

__Parameters__:  
* member_id = The ID of the Logged in Member (generally {logged_in_member_id})  

__Use__:  
{exp:kt_tools:get_users_csr_email member_id=""}

# get_users_csr_screenname  
This method fetches the Screen Name of the CSR assigned to the Customer. It queries the appropriate tables by passing in the {logged_in_member_id}

__Use__:  
{exp:kt_tools:get_users_csr_screenname member_id=""}

__Parameters__:  
* member_id = Quantity of the product purchased 

# get_original_price



# evaluate_price  
This function modifies the product price based upon the users available Custom Pricing and discounts  
It uses on the Customer Number to make the connections

__Use__:  
{exp:kt_tools:evaluate_price}

__Parameters__:  

* KTproduct="{title}" 
* pPrice="{price}" 
* pClass="{class}" 
* pMAXdiscount="{max_product_discount}"