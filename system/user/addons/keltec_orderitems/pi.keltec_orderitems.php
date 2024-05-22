<?php
class Keltec_orderitems
{
    //TEST: 148016, 148017
    //public $return_data = '';
    public function __construct()
    {
		//parent::__construct();
		// sets our plugin parameters		
	}
	public function list()
	{
		//test - https://keltecinc.com/script/order_items_json/k5ksnhyqwba@jg78hj!ju87@j/148592
		$order_id = ee()->TMPL->fetch_param('order_id');
		// fetch the order items associate with the Order ID
		    $orderItems = ee()->db->select('title, price, quantity, weight, extra')
		    ->from('exp_cartthrob_order_items')
		    ->where(array(
		        'order_id' => $order_id
		    ))
		    ->get();
			
			if($orderItems->num_rows() == "") { 
				echo 'nothing'; die(); 
			} else {
					$data = array();
					foreach($orderItems -> result() as $row) {
						$items = array(unserialize(base64_decode($row->extra)));
						foreach($items as $item){ 
							$requested_warehouse = $item['requested_warehouse'];
							$purchased_price = $item['purchased_price'];
							$expected_ship = $item['expected_ship'];
							$customer_number = $item['customer_number'];
							$label_description = $item['label_description'];
						}
						$row = array(
							'title' => $row->title, 
							'price' => $row->price, 
							'quantity' => $row->quantity,
							'purchased_price' => $purchased_price,
							'requested_warehouse' => $requested_warehouse,
							'expected_ship' => $expected_ship,
							'customer_number' => $customer_number,
							'label_description' => $label_description
						);
						array_push($data, $row);
					}
				header('Content-Type: application/json');
				return json_encode($data);
			}
	}
} // EOC Keltec_orderitems
?>