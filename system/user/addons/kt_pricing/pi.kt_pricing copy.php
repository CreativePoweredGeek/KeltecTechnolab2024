<?php
class KT_pricing
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
    public function evaluate_price()
    {
	   //{exp:kt_pricing:evaluate_price customerID="" KTproduct="" pPrice="" pClass="" pMAXdiscount=""}
	   	
	    $cust = ee()->TMPL->fetch_param('customerID');
		$product = ee()->TMPL->fetch_param('KTproduct');
		$oprice = ee()->TMPL->fetch_param('pPrice');
		$pclass = ee()->TMPL->fetch_param('pClass');
		$pMaxDisc = ee()->TMPL->fetch_param('pMAXdiscount');
	    
	    $channel = ee('Model')->get('Channel')->filter('channel_name', 'customer_contract_pricing')->first();
		
		$entries = ee('Model')->get('ChannelEntry')
			->filter('channel_id', $channel->channel_id)
			->filter('field_id_183', $cust)
			->filter('field_id_27', $product)
			->first();
		
		
		if($entries != "") {
			$id = explode(':', $entries);
			$eid = $id[3];
			$result=$oprice.'/'.'{exp:channel:entries channel="customer_contract_pricing" entry_id="'.$eid.'"}{price}{/exp:channel:entries}';
			return $result;
			
		} else {
			$new = $this->get_class_discount($cust,$pclass,$pMaxDisc,$oprice);
			return $new;
		}
		
	}
	public function get_class_discount($cust,$pclass,$pMaxDisc,$oprice) 
	{
		
		$channel = ee('Model')->get('Channel')->filter('channel_name', 'customer_discounts')->first();
		
		$entries = ee('Model')->get('ChannelEntry')
			->filter('channel_id', $channel->channel_id)
			->filter('field_id_183', $cust) //customer
			->filter('field_id_5', $pclass) //class field
			->first();
		
		if($entries != "") {
			$disc = explode(':', $entries);
			$disc = $id[3];
			$cdiscount='{exp:channel:entries channel="customer_discounts" entry_id="'.$eid.'"}{line_discount}{/exp:channel:entries}';
			//If the Maximum discount of a product is smaller than product discount, we need to use it instead
			if ($pMaxDisc !="") {
				if($cdiscount > $pMaxDisc) {
					$cdiscount = $pMaxDisc;
				}
			}
			$trimmed_price = str_replace('$', '', $oprice);
			$new = $trimmed_price-($cdiscount/100)*$trimmed_price;
			return $oprice.'/$'.number_format($new,2);
		} else {
			$blanket = $this->get_blanket_discount($cust,$pclass,$pMaxDisc,$oprice);
			return $blanket;
		}
		
		
	}
	public function get_blanket_discount($cust,$pclass,$pMaxDisc,$oprice) 
	{
		
		$channel = ee('Model')->get('Channel')->filter('channel_name', 'customer_discounts')->first();
		
		$entries = ee('Model')->get('ChannelEntry')
			->filter('channel_id', $channel->channel_id)
			->filter('field_id_183', $cust) //customer
			->filter('field_id_5', 'blanket') //class field
			->first();
		
		if($entries != "") {
			$disc = explode(':', $entries);
			$disc = $id[3];
			$bdiscount='{exp:channel:entries channel="customer_discounts" entry_id="'.$eid.'"}{line_discount}{/exp:channel:entries}';
			//If the Maximum discount of a product is smaller than product discount, we need to use it instead
			if ($pMaxDisc !="") {
				if($bdiscount > $pMaxDisc) {
					$bdiscount = $pMaxDisc;
				}
			}
			
			
			$trimmed_price = str_replace('$', '', $oprice);
			$new = $trimmed_price-($bdiscount/100)*$trimmed_price;
			return $oprice.'/$'.number_format($new,2);
		} else {
			return $oprice;
		}
		
		
	}

}

?>