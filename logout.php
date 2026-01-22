<?php
	//16.clear sessions
	session_start();

	if (isset($_SESSION['OfficerID'])) {
		session_destroy();
		unset($_SESSION['OfficerID']);
		unset($_SESSION['user_name'] );
		 
        
	} 
		header("Location: login.php");

?>