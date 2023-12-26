<?php

require 'vendor/autoload.php';
require './connection.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$results = [];

// Check if the form was submitted and the file was uploaded


$updateActive = "UPDATE store_mapping_pposms
SET active = 1
WHERE description IS NOT NULL AND question1 IS NOT NULL
;
";
if ($conn->query($updateActive) == true) {
    $results[] = ['storeCode' => "Cập nhật", 'StoreId' => "Active = Có", 'Updated' => '1'];
}


// Display the form if it's not a POST request
header('Content-Type: application/json');
echo json_encode($results);
exit();
