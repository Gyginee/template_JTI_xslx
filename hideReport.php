<?php

require 'vendor/autoload.php';
require './connection.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$results = [];

// Check if the form was submitted and the file was uploaded
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excelFile"])) {
    $uploadedFile = $_FILES["excelFile"];

    // Validate file type and move the uploaded file to a secure location
    $allowedExtensions = ['xlsx'];
    $uploadedFileExtension = strtolower(pathinfo($uploadedFile['name'], PATHINFO_EXTENSION));

    if (in_array($uploadedFileExtension, $allowedExtensions)) {
        $tempFilePath = 'upload/' . uniqid('excel_') . '.' . $uploadedFileExtension;

        if (move_uploaded_file($uploadedFile['tmp_name'], $tempFilePath)) {
            $reader = new Xlsx();
            $spreadsheet = $reader->load($tempFilePath);

            // Lấy sheet theo tên Sheet
            $sheetName = 'HIDE';
            $sheet = $spreadsheet->getSheetByName($sheetName);

            // Đọc hàng header 
            $headerRow = $sheet->getRowIterator(1)->current();
            $cellIterator = $headerRow->getCellIterator();

            // Lấy vị trí cột dựa trên tiêu đề cột
            foreach ($cellIterator as $cell) {
                $column = $cell->getColumn();
                $value = $cell->getValue();
                $columns[$value] = $column;
            }

            // Now you can use these column positions to retrieve data from the sheet
            $maxRow = $sheet->getHighestRow();

            // Prepare the SQL statement
            $sql = "UPDATE stores SET isDone = 0 WHERE id = ?";
            $stmt = $conn->prepare($sql);

            // Iterate through rows and access data by column position
            for ($row = 2; $row <= $maxRow; $row++) {
                $storeId = $sheet->getCell($columns['storeId'] . $row)->getValue();

                // Bind parameters and execute the statement
                $stmt->bind_param('s', $storeId);
                if ($stmt->execute()) {
                    $results[] = ['storeCode' => 'Lười check', 'StoreId' => $storeId, 'Updated' => 'Hide Report'];
                } else {
                    // Handle database update errors
                    $results[] = ['storeCode' => 'Lười check', 'StoreId' => $storeId, 'Error' => $stmt->error];
                }
            }

            // Close the statement
            $stmt->close();

            // Return the results as JSON (you can modify this based on your needs)
            header('Content-Type: application/json');
            echo json_encode($results);
            exit();
        } else {
            // Handle file upload errors here
            echo "Error moving uploaded file.";
        }
    } else {
        echo "Invalid file type. Only Excel files are allowed.";
    }
} else {
    // Display the form if it's not a POST request
    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
}
