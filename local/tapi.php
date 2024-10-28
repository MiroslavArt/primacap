<?php

header('Access-Control-Allow-Origin: *');

$headers = "From: from@webhookservesite.com";
/* change this sample email address to the valid email that you would like to receive letters from  */

 $message = print_r($_POST,true);
 @mail('mariff@petrosafeitue.com', 'Tilda TEST', $message, $headers);
/* change this sample email address to the valid email that you would like to receive submissions to  */

echo"ok";

?>