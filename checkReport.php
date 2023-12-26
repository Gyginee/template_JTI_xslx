<?php

require 'vendor/autoload.php';
require './connection.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$results = [];

// Check if the form was submitted and the file was uploaded



// Display the form if it's not a POST request
header('Content-Type: application/json');
echo json_encode($results);
exit();
