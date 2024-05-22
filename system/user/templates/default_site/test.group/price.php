<?php
$file = 'http://dev.keltecinc.com/uploads/pricing-updates-4-19-1.csv';

if(file_exists($fileName))
		{

$dbname = 'keltecinc_eedata';
$username = 'keltecinc_boss';
$password = '#kM4MtN%[lqm';
$servername = '192.243.105.20';


$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

	
			$file = fopen($fileName,'r');
			
			while(!feof($file))
			{ 

           		$name = fgets($file);
				
				if(!empty($name)) {
					$line = explode(',', $name);
					$product = $line[0];
					$price = $line[1];
					
				

					$sql = "SELECT entry_id FROM exp_channel_titles WHERE channel_id = '37' AND title = '$product'";
					$result = $conn->query($sql);
					
					if ($result->num_rows > 0) {
					    // output data of each row
					    while($row = $result->fetch_assoc()) {
					        $entryid = $row['entry_id'];
					        echo $entryid;
					        $sql = "UPDATE exp_channel_data_field_3 SET field_id_3 = '$price' WHERE entry_id = '$entryid'";

								if ($conn->query($sql) === TRUE) {
								    echo "Record updated successfully";
								} else {
								    echo "Error updating record: " . $conn->error;
								}
					    }
					} else {
					    echo "0 results";
					}
					
				
				
				
				} // CLOSE if(!empty($name))
			$conn->close();
			}
		}
?>

