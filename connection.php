<?php
$servername = "103.90.227.186";
$username = "hd";
$password = "hd@123";
$database = "jti_survey_test";

$conn = mysqli_connect($servername, $username, $password, $database);
// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
//echo "Connected successfully";

?>