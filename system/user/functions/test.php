<?
	echo '<p>Hello from a PHP include</p>';
	echo '<p>{logged_in_username} <--- This is being called from inside the Include File.</p>';
	?>
	
	 <p><?php $username = ee()->session->userdata('username'); 
		echo $username.' <- called from ee session w/i the include';
	?></p>