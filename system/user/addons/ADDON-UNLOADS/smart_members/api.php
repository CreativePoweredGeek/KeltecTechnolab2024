<?php

	require_once( "hybrid/Auth.php" );
	require_once( "hybrid/Endpoint.php" );
	require_once("hybrid/vendor/autoload.php");

	Hybrid_Endpoint::process();

	/*if(isset($_GET['provider']) && $_GET['provider'] == "Live")
	{
		$_SERVER['QUERY_STRING'] = 'hauth.done=Live';
	}*/

?>