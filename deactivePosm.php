<?php

require 'vendor/autoload.php';
require './connection.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$results = [];

// Check if the form was submitted and the file was uploaded


$updateActive = "UPDATE store_mapping_pposms set active = 0 WHERE active = 1 AND question1='' AND question2='' AND question3='' AND question4='' AND question5='';";
if ($conn->query($updateActive) == true) {
    $results[] = ['storeCode' => "Cập nhật", 'StoreId' => "Active = Có", 'Updated' => '1'];
}


// Display the form if it's not a POST request
header('Content-Type: application/json');
echo json_encode($results);
exit();
