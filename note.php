<?php


require 'vendor/autoload.php';
require './connection.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$results = [];

// Function to process data

// Check if the form was submitted and the file was uploaded
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excelFile"])) {
    $uploadedFile = $_FILES["excelFile"];

    // Check for errors during the file upload
    if ($uploadedFile["error"] == UPLOAD_ERR_OK) {
        // Move the uploaded file to a temporary location
        $tempFilePath = $uploadedFile["tmp_name"];

        $reader = new Xlsx();
        $spreadsheet = $reader->load($tempFilePath); // Thay tên tệp Excel thực tế của bạn

        // Lấy sheet theo tên Sheet
        $sheetName = 'NOTE';
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


        function processPosm($storeId, $pposmId, $conn)
        {
            try {
                $sql = "UPDATE store_mapping_pposms
                        SET
                            active = 0,
                            question1 = '',
                            question2 = '',
                            question3 = '',
                            question4 = '',
                            question5 = '',
                            description = ''
                        WHERE
                            storeId = ? AND pposmId = ?";

                $stmt = $conn->prepare($sql);
                $stmt->bind_param('ss', $storeId, $pposmId);
                $stmt->execute();
                $stmt->close();

                return true;
            } catch (Exception $e) {
                // Log or handle the exception as needed
                // Log or handle the exception as needed
                $errorMessage = $e->getMessage();
                $errorCode = $e->getCode();
                $errorFile = $e->getFile();
                $errorLine = $e->getLine();
                // Log the exception details or print them for debugging
                error_log("Error in processPosm: $errorMessage (Code: $errorCode) in $errorFile at line $errorLine");

                return false;
            }
        }

        function processImg($img, $conn)
        {
            try {
                $deleteSql = "DELETE FROM store_images
                WHERE imagePath = ?";

                $deleteStmt = $conn->prepare($deleteSql);
                $deleteStmt->bind_param('s', $img);
                $deleteStmt->execute();
                $deleteStmt->close();

                return true;
            } catch (Exception $e) {
                // Handle exceptions or errors here
                return false;
            }
        }
        // Now you can use these column positions to retrieve data from the sheet
        $maxRow = $sheet->getHighestRow();

        // Iterate through rows and access data by column position
        for ($row = 2; $row <= $maxRow; $row++) {

            $storeCode = $sheet->getCell($columns['storeCode'] . $row)->getValue();
            $pposmId = $sheet->getCell($columns['pposmId'] . $row)->getValue();
            $img = $sheet->getCell($columns['Image'] . $row)->getValue();
            $note = $sheet->getCell($columns['NOTE'] . $row)->getValue();

            //Lấy StoreId 
            $sql = "SELECT * FROM stores WHERE storeCode = '$storeCode' AND isDone = 1";
            $result = $conn->query($sql);
            $StoreId = null;
            if ($result->num_rows > 0) {
                while ($value = $result->fetch_assoc()) {
                    $StoreId = $value['id'];
                }
            }


            if ($note == "Xóa thông tin POSM") {

                $pposmIdTrim = ($pposmId !== null) ? str_replace(' ', '', $pposmId) : null;

                if ($pposmId !== null && $pposmIdTrim !== null) {

                    if (processPosm($StoreId, $pposmId, $conn)) {
                        $results[] = ['storeCode' => $storeCode . " | " . $StoreId, 'StoreId' => $pposmId, 'Updated' => 'Xóa POSM'];
                    } else $results[] = ['storeCode' => $storeCode . " | " . $StoreId, 'StoreId' => $pposmId, 'Updated' => 'Lỗi truy vấn'];
                } else $results[] = ['storeCode' => $storeCode . " | " . $StoreId, 'StoreId' => $pposmId, 'Updated' => 'Lỗi POSM'];
            }
            if ($note == "Xóa hình") {
                $imgTrim = ($img !== null) ? str_replace(' ', '', $img) : null;

                if ($img !== null && $imgTrim !== null) {
                    if (processImg($img, $conn)) {
                        $results[] = ['storeCode' => "Làm gì có", 'StoreId' => $img, 'Updated' => 'Xóa Hình'];
                    } else $results[] = ['storeCode' => "làm gì có", 'StoreId' => $img, 'Updated' => 'Lỗi truy vấn'];
                } else $results[] = ['storeCode' => "Làm gì có", 'StoreId' => $img, 'Updated' => 'Lỗi storeCode'];
            }
        }


        // Return the results as JSON (you can modify this based on your needs)
        header('Content-Type: application/json');
        echo json_encode($results);
        exit();
    } else {
        // Handle file upload errors here
        echo "Error uploading file.";
    }
} else {
    // Display the form if it's not a POST request
    header('Content-Type: application/json');
    echo json_encode($results);
    exit();
}
