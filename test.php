<?php

require 'vendor/autoload.php';
require './connection.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

$results = [];

// Check if the form was submitted and the file was uploaded
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["excelFile"])) {
    $uploadedFile = $_FILES["excelFile"];

    // Check for errors during the file upload
    if ($uploadedFile["error"] == UPLOAD_ERR_OK) {
        // Move the uploaded file to a temporary location
        $tempFilePath = $uploadedFile["tmp_name"];

        $reader = new Xlsx();
        $spreadsheet = $reader->load($tempFilePath); // Thay tên tệp Excel thực tế của bạn

        // Lấy sheet hoạt động
        $sheet = $spreadsheet->getActiveSheet();

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

        // Iterate through rows and access data by column position
        for ($row = 2; $row <= $maxRow; $row++) {

            $storeCode = $sheet->getCell($columns['storeCode'] . $row)->getValue();
            $distributorId = $sheet->getCell($columns['distributorId'] . $row)->getValue();


            $sql = "SELECT * FROM stores WHERE storeCode = '$storeCode'";
            $result = $conn->query($sql);
            $StoreId = null;

            if ($result->num_rows > 0) {
                while ($value = $result->fetch_assoc()) {
                    // echo "<br/>id: " . $value['id'] . " va storename: " . $value['storeName'];
                    $StoreId = $value['id'];
                }
            }

            $sqlS = "SELECT * FROM store_images WHERE storeId = '$StoreId' and typeCode = 'overview' ";
            $sqlF = "SELECT * FROM store_images WHERE storeId = '$StoreId'";
            $completeReport = "UPDATE stores SET isDone = 1 WHERE storeId = '$StoreId'";

            if ($conn->query($sqlS) == true) {
                $result = $conn->query($sqlS);
                while ($value = $result->fetch_assoc()) {
                    $lat = $value['lat'];
                    $long = $value['long'];
                }
                //check_in
                $trimm0 = ($check_in !== null) ? str_replace(' ', '', $check_in) : null;
                if ($check_in != null && $trimm0 != null) {
                    insertImageIntoStore($conn, $StoreId, 'storeImages/' . $check_in . '.jpg', 'check_in', $lat, $long, '-1');
                }
                //check_out
                $trimm1 = ($check_out !== null) ? str_replace(' ', '', $check_out) : null;
                if ($check_out != null && $trimm1 != null) {
                    insertImageIntoStore($conn, $StoreId, 'storeImages/' . $check_out . '.jpg', 'check_out', $lat, $long, '-1');
                }
            } else if ($conn->query($sqlS) == true) {
                //overview
                $trimmov = ($overview !== null) ? str_replace(' ', '', $overview) : null;
                if ($overview != null && $trimmov != null) {
                    insertImageIntoStore($conn, $StoreId, 'storeImages/' . $overview . '.jpg', 'overview', $lat, $long, '-1');
                }
                //check_in
                $trimm0 = ($check_in !== null) ? str_replace(' ', '', $check_in) : null;
                if ($check_in != null && $trimm0 != null) {
                    insertImageIntoStore($conn, $StoreId, 'storeImages/' . $check_in . '.jpg', 'check_in', $lat, $long, '-1');
                }
                //check_out
                $trimm1 = ($check_out !== null) ? str_replace(' ', '', $check_out) : null;
                if ($check_out != null && $trimm1 != null) {
                    insertImageIntoStore($conn, $StoreId, 'storeImages/' . $check_out . '.jpg', 'check_out', $lat, $long, '-1');
                }

            }


            if (($pposmId !== null && $status !== 'Update') || $status == 'Thành công') {

                //CẬP NHẬT TRẠNG THÁI THÀNH CÔNG
                $updateStatus = "UPDATE stores SET status = 'TC' WHERE storeCode = '$storeCode' ";
                $conn->query($updateStatus);
                /* --------------START update pposm question */
                $sql3 = "UPDATE store_mapping_pposms SET question1 = '$question1',   question2 = '$question2', question3 = '$question3',question4 = '$question4',  question5 = '$question5',  description = '$description',active = 1 WHERE storeId = '$StoreId' AND pposmId='$pposmId'";
                $conn->query($sql3);
                /* END done */

                // cap nhat hinh anh trong store_images

                $sql4 = "SELECT * FROM store_images WHERE storeId = '$StoreId' and typeCode = 'overview' ";
                $sql5 = "SELECT * FROM store_images WHERE storeId = '$StoreId'";
                // them du lieu vao store_images
                if ($conn->query($sql4) == true) {
                    $result = $conn->query($sql4);

                    if ($result->num_rows > 0) {
                        // Hiển thị dữ liệu nếu có
                        while ($value = $result->fetch_assoc()) {
                            if (($value['lat'] && $value['long'] !== null) || ($value['lat'] && $value['long'] !== '')) {
                                $lat = $value['lat'];
                                $long = $value['long'];
                            } else {
                                $lat = str_replace(',', '.', $lat);
                                $long = str_replace(',', '.', $long);
                            }
                        }

                        //posm1
                        $trimm2 = ($posm1 !== null) ? str_replace(' ', '', $posm1) : null;
                        if ($posm1 != null && $trimm2 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $posm1 . '.jpg', 'posm', $lat, $long, $pposmId);
                        }
                        //posm2
                        $trimm3 = ($posm2 !== null) ? str_replace(' ', '', $posm2) : null;
                        if ($posm2 != null && $trimm3 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $posm2 . '.jpg', 'posm', $lat, $long, $pposmId);
                        }
                        //fee_info
                        $trimm4 = ($fee_info1 !== null) ? str_replace(' ', '', $fee_info1) : null;
                        if ($fee_info1 != null && $trimm4 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $fee_info1 . '.jpg', 'fee_info', $lat, $long, '-1');
                        }
                        //fee_info
                        $trimm5 = ($fee_info2 !== null) ? str_replace(' ', '', $fee_info2) : null;
                        if ($fee_info2 != null && $trimm5 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $fee_info2 . '.jpg', 'fee_info', $lat, $long, '-1');
                        }
                        //hotzone
                        $trimm6 = ($hotzone1 !== null) ? str_replace(' ', '', $hotzone1) : null;
                        if ($hotzone1 != null && $trimm6 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $hotzone1 . '.jpg', 'hot_zone', $lat, $long, '-1');
                        }
                        //hotzone
                        $trimm7 = ($hotzone2 !== null) ? str_replace(' ', '', $hotzone2) : null;
                        if ($hotzone2 != null && $trimm7 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $hotzone2 . '.jpg', 'hot_zone', $lat, $long, '-1');
                        }
                        $conn->query($completeReport);
                    } else if ($conn->query($sql5) == true) {

                        //posm1
                        $trimm2 = ($posm1 !== null) ? str_replace(' ', '', $posm1) : null;
                        if ($posm1 != null && $trimm2 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $posm1 . '.jpg', 'posm', $lat, $long, $pposmId);
                        }
                        //posm2
                        $trimm3 = ($posm2 !== null) ? str_replace(' ', '', $posm2) : null;
                        if ($posm2 != null && $trimm3 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $posm2 . '.jpg', 'posm', $lat, $long, $pposmId);
                        }
                        //fee_info
                        $trimm4 = ($fee_info1 !== null) ? str_replace(' ', '', $fee_info1) : null;
                        if ($fee_info1 != null && $trimm4 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $fee_info1 . '.jpg', 'fee_info', $lat, $long, '-1');
                        }
                        //fee_info
                        $trimm5 = ($fee_info2 !== null) ? str_replace(' ', '', $fee_info2) : null;
                        if ($fee_info2 != null && $trimm5 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $fee_info2 . '.jpg', 'fee_info', $lat, $long, '-1');
                        }
                        //hotzone
                        $trimm6 = ($hotzone1 !== null) ? str_replace(' ', '', $hotzone1) : null;
                        if ($hotzone1 != null && $trimm6 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $hotzone1 . '.jpg', 'hot_zone', $lat, $long, '-1');
                        }
                        //hotzone
                        $trimm7 = ($hotzone2 !== null) ? str_replace(' ', '', $hotzone2) : null;
                        if ($hotzone2 != null && $trimm7 != null) {
                            insertImageIntoStore($conn, $StoreId, 'storeImages/' . $hotzone2 . '.jpg', 'hot_zone', $lat, $long, '-1');
                        }
                        $conn->query($completeReport);
                    }
                }
                $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreId, 'Updated' => 'Data'];
            } else if ($status !== null && $note !== null) {
                //Nếu không cập nhật pposm thì cập nhật trạng thái không tìm thấy cửa hàng

                $sql = "SELECT * FROM stores WHERE storeCode = '$storeCode'";
                $result = $conn->query($sql);


                if ($result->num_rows > 0) {
                    // Use a WHERE clause to specify which row to update
                    $updateStatus = "UPDATE stores SET status = ";

                    // Switch statement for updating $status based on its original value
                    switch ($status) {
                        case "KTC - Đóng cửa tạm thời":
                            $updateStatus .= "'DONG_TAM_THOI'";
                            break;
                        case "KTC - Đóng cửa vĩnh viễn":
                            $updateStatus .= "'DONG_VINH_VIEN'";
                            break;
                        case "KTC - Khác":
                            $updateStatus .= "'KHAC'";
                            break;
                        case "KTC - Không tìm thấy cửa hàng":
                            $updateStatus .= "'KHONG_TIM_THAY'";
                            break;
                        case "Thành công":
                            $updateStatus .= "'TC'";
                            break;
                        case "KTC - Từ chối tiếp xúc":
                            $updateStatus .= "'TU_CHOI_TX'";
                            break;
                        // Add more cases if needed for other status values
                        default:
                            $updateStatus .= "'$status'";
                    }

                    $updateStatus .= ", note = '$note' WHERE storeCode = '$storeCode'";
                    $conn->query($updateStatus);
                    $conn->query($completeReport);
                }
                $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreId, 'Updated' => 'Status'];
            } else if ($status === 'Update') {
                if ($pposmId !== null) {
                    $sql_posm = "SELECT * FROM store_mapping_pposms WHERE storeId = '$StoreId' AND pposmId = '$pposmId'";
                    if ($conn->query($sql_posm)== TRUE ) {
                        $completePOSM = "UPDATE store_mapping_pposms SET active = 0 WHERE storeId = '$StoreId' AND pposmId = '$pposmId'";
                        $conn->query($completePOSM);
                        
                        $trimm1 = ($question1 !== null) ? str_replace(' ', '', $question1) : null;
                        $trimm2 = ($question2 !== null) ? str_replace(' ', '', $question2) : null;
                        $trimm3 = ($question3 !== null) ? str_replace(' ', '', $question3) : null;
                        $trimm4 = ($question4 !== null) ? str_replace(' ', '', $question4) : null;
                        $trimm5 = ($question5 !== null) ? str_replace(' ', '', $question5) : null;
                        $trimDescription = ($description !== null) ? str_replace(' ', '', $description) : null;
                        
                        // Check if any of the fields are not null and not an empty string
                        if (($question1 !== null && $trimm1 !== null) ||
                            ($question2 !== null && $trimm2 !== null) ||
                            ($question3 !== null && $trimm3 !== null) ||
                            ($question4 !== null && $trimm4 !== null) ||
                            ($question5 !== null && $trimm5 !== null) ||
                            ($description !== null && $trimDescription !== null)) {
                        
                            updatePosm($conn, $StoreId, $pposmId, $question1, $question2, $question3, $question4, $question5, $description);
                        }
                    }
                }
                $sql = "SELECT * FROM store_images WHERE storeId = '$StoreId'";
                if ($conn->query($sql) == TRUE) {
                    //overview
                    $trimmov = ($overview !== null) ? str_replace(' ', '', $overview) : null;
                    if ($overview != null && $trimmov != null) {
                        insertImageIntoStore($conn, $StoreId, 'storeImages/' . $overview . '.jpg', 'overview', $lat, $long, '-1');
                    }
                    //check_in
                    $trimm0 = ($check_in !== null) ? str_replace(' ', '', $check_in) : null;
                    if ($check_in != null && $trimm0 != null) {
                        insertImageIntoStore($conn, $StoreId, 'storeImages/' . $check_in . '.jpg', 'check_in', $lat, $long, '-1');
                    }
                    //check_out
                    $trimm1 = ($check_out !== null) ? str_replace(' ', '', $check_out) : null;
                    if ($check_out != null && $trimm1 != null) {
                        insertImageIntoStore($conn, $StoreId, 'storeImages/' . $check_out . '.jpg', 'check_out', $lat, $long, '-1');
                    }
                    //posm1
                    $trimm2 = ($posm1 !== null) ? str_replace(' ', '', $posm1) : null;
                    if ($posm1 != null && $trimm2 != null) {
                        insertImageIntoStore($conn, $StoreId, 'storeImages/' . $posm1 . '.jpg', 'posm', $lat, $long, $pposmId);
                    }
                    //posm2
                    $trimm3 = ($posm2 !== null) ? str_replace(' ', '', $posm2) : null;
                    if ($posm2 != null && $trimm3 != null) {
                        insertImageIntoStore($conn, $StoreId, 'storeImages/' . $posm2 . '.jpg', 'posm', $lat, $long, $pposmId);
                    }
                    //fee_info
                    $trimm4 = ($fee_info1 !== null) ? str_replace(' ', '', $fee_info1) : null;
                    if ($fee_info1 != null && $trimm4 != null) {
                        insertImageIntoStore($conn, $StoreId, 'storeImages/' . $fee_info1 . '.jpg', 'fee_info', $lat, $long, '-1');
                    }
                    //fee_info
                    $trimm5 = ($fee_info2 !== null) ? str_replace(' ', '', $fee_info2) : null;
                    if ($fee_info2 != null && $trimm5 != null) {
                        insertImageIntoStore($conn, $StoreId, 'storeImages/' . $fee_info2 . '.jpg', 'fee_info', $lat, $long, '-1');
                    }
                    //hotzone
                    $trimm6 = ($hotzone1 !== null) ? str_replace(' ', '', $hotzone1) : null;
                    if ($hotzone1 != null && $trimm6 != null) {
                        insertImageIntoStore($conn, $StoreId, 'storeImages/' . $hotzone1 . '.jpg', 'hot_zone', $lat, $long, '-1');
                    }
                    //hotzone
                    $trimm7 = ($hotzone2 !== null) ? str_replace(' ', '', $hotzone2) : null;
                    if ($hotzone2 != null && $trimm7 != null) {
                        insertImageIntoStore($conn, $StoreId, 'storeImages/' . $hotzone2 . '.jpg', 'hotzone', $lat, $long, '-1');
                    }
                    $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreId, 'Updated' => 'Update Info'];
                }

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
?>