<?php
class KT_shipping
{
    // This Plugin identifies an appropriate Product price based on the Customer.
    // There are 4 Different pricing types:
    //    ~Custom Product Pricing
    //    ~Discount by Class
    //    ~Blanket Discount
    //    ~Max Discount per product
    //
    // The KEY Function is evaluatePrice(), and it is a cascading system.
    //    If there is no custom price for a product, then we look for a Class discount. 
    //    If there is no Class Discount then it looks for Blanket Discounts
    // The wildcard is that individual products have maximum discounts that are allowed.
    
    
    public $return_data = '';
    
    public function __construct()
    {
		//$parameter = ee()->TMPL->fetch_param('type');
		$this->return_data = ee()->TMPL->tagdata;
	}
    
        
    
    public function shipping_info_old()
    {
	   //parameters
	    $bCountry = ee()->TMPL->fetch_param('country');
	    $itemClass = ee()->TMPL->tagdata;
		$itemInventory = ee()->TMPL->fetch_param('iteminventory');
		$inTransitQty = ee()->TMPL->fetch_param('intransitqty');
		$mfgLead = ee()->TMPL->fetch_param('mfglead');
		//---
		$onOrder_po = ee()->TMPL->fetch_param('onorder_po'); // Number
		$onOrder_reqDate = ee()->TMPL->fetch_param('onorder_reqdate'); //Date
	    
	    //these are arrays of possible item class codes
	    $inventoryArrayA = array("STOCK","MTO");
		//$inventoryArrayB = array("CAH‐PURCH","DC","DR","G","HVF","KA‐PURCH","KC‐PURCH","KF‐PURCH","KP","KS‐PURCH","OI","PF","SCK‐PURCH","SEP‐PURCH","AS","SS","CS","REF","SEP-PROD","KG");
		$inventoryArrayB = array('AS','CAH-PURCH');
	    $today = $this->create_date();
		$tomorrow = date('m/d/Y', strtotime($today.' + 1 days'));
		$currentTime = $this->create_time();
		$mfgLeadTime = date('m/d/Y', strtotime($today.' + '.$mfgLead.' days'));
	    
		if($itemInventory >= 1)
		{
			if($currentTime < "15:00:00") {
				$shipDate = 'If ordered today, the est. shipping date is '.$today;
			} else {
				$shipDate = 'If ordered today, the est. shipping date is '.$tomorrow;
			}
		} else // no inventory
		{
			if(in_array($itemClass, $inventoryArrayB)) 
			{
				if($mfgLead != '') {
					$shipDate = 'If ordered today, the est. shipping date based on Manufacturers Lead time is '.$mfgLeadTime;
				} else { 
					$shipDate = 'Contact KELTEC for ship dates. (no mfgLeadTime)';
				}
			
			} elseif(in_array($itemClass, $inventoryArrayA, FALSE))
			{
				
				$shipDate = 'If ordered today, the est. shipping date based on Manufacturers Lead time is '.$mfgLeadTime;
				
			} else {
				//echo gettype($itemClass);
				$shipDate = $itemClass.' - Contact your KELTEC Sales Rep for availability.';
			} 
			
		}
	    
	    return $shipDate;
	    
    }
    
       public function shipping_info()
    {
	   //parameters
	    $bCountry = ee()->TMPL->fetch_param('country');
	    $itemClass = ee()->TMPL->fetch_param('class');
		$itemInventory = ee()->TMPL->fetch_param('iteminventory');
		$inTransitQty = ee()->TMPL->fetch_param('intransitqty');
		$mfgLead = ee()->TMPL->fetch_param('mfglead');
		//---
		$onOrder_po = ee()->TMPL->fetch_param('onorder_po'); // Number
		$onOrder_reqDate = ee()->TMPL->fetch_param('onorder_reqdate'); //Date
	    
	    //these are arrays of possible item class codes
	    $inventoryArrayA = array("STOCK","MTO");
		$inventoryArrayB = array("CAH‐PURCH","DC","DR","G","HVF","KA‐PURCH","KC‐PURCH","KF‐PURCH","KP","KS‐PURCH","OI","PF","SCK‐PURCH","SEP‐PURCH","AS","SS","CS","REF","SEP-PROD","KG");

	    $today = $this->create_date();
		$tomorrow = date('m/d/Y', strtotime($today.' + 1 days'));
		$currentTime = $this->create_time();
		$mfgLeadTime = date('m/d/Y', strtotime($today.' + '.$mfgLead.' days'));
	    
	    if($itemInventory >= 1)
		{
			if($currentTime < "15:00:00") {
				$shipDate = 'If ordered today, the est. shipping date is '.$today;
			} else {
				$shipDate = 'If ordered today, the est. shipping date is '.$tomorrow;
			}
		} else // no inventory
		{
			if(in_array($itemClass, $inventoryArrayB)) 
			{
				if($mfgLead != '') {
					$shipDate = 'If ordered today, the est. shipping date based on Manufacturers Lead time is '.$mfgLeadTime;
				} else { 
					$shipDate = 'Contact KELTEC for ship dates. (no mfgLeadTime)';
				}
			
			} elseif(in_array($itemClass, $inventoryArrayA, FALSE))
			{
				
				$shipDate = 'If ordered today, the est. shipping date based on Manufacturers Lead time is '.$mfgLeadTime;
				
			} else {
				//echo gettype($itemClass);
				$shipDate = 'Contact your KELTEC Sales Rep for availability.';
			} 
			
		}
		
		
	    
	    return $shipDate;
	    
    }
    
	private function  create_date() 
	{
		$tz = 'EST';
		$timestamp = time();
		$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
		$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
		$date = $dt->format('m/d/Y');
		return $date;
	}
	private function  create_time() 
	{
		$tz = 'EST';
		$timestamp = time();
		$dt = new DateTime("now", new DateTimeZone($tz)); //first argument "must" be a string
		$dt->setTimestamp($timestamp); //adjust the object to correct timestamp
		$time = $dt->format('H:i:s');
		return $time;
	}    

}

?>