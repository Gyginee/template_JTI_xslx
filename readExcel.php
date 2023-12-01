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

        // Lấy sheet theo tên Sheet
        $sheetName = 'MAIN';
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

        function insertImageIntoStore($conn, $storeId, $imagePath, $typeCode, $lat, $long, $posmId)
        {
            // Use prepared statements to prevent SQL injection
            $sqlInsert = $conn->prepare("INSERT INTO store_images (storeId, imagePath, typeCode, lat, `long`, posmId) 
                                 VALUES (?, ?, ?, ?, ?, ?)");

            // Bind parameters
            $sqlInsert->bind_param("ssssss", $storeId, $imagePath, $typeCode, $lat, $long, $posmId);

            // Execute the query
            if ($sqlInsert->execute()) {
                return true; // Successful insertion
            } else {
                return false; // Insertion failed
            }
        }
        function updatePosm($conn, $storeId, $posmId, $question1, $question2, $question3, $question4, $question5, $description)
        {
            // Build the SET part of the SQL query dynamically based on the provided values
            $setClause = '';

            $fields = array(
                'question1' => $question1,
                'question2' => $question2,
                'question3' => $question3,
                'question4' => $question4,
                'question5' => $question5,
                'description' => $description
            );

            foreach ($fields as $field => $value) {
                // Check if the value is not empty or null, then include it in the set clause
                if ($value !== '' && $value !== null) {
                    $setClause .= "$field = ?, ";
                }
            }

            // Remove the trailing comma
            $setClause = rtrim($setClause, ', ');

            // Prepare the SQL update statement
            $sqlUpdate = $conn->prepare("UPDATE store_mapping_pposms 
                SET $setClause 
                WHERE storeId = ? AND pposmId = ?");

            // Build an array of values for binding
            $bindValues = array_values(array_filter($fields, function ($value) {
                return $value !== '' && $value !== null;
            }));

            // Append storeId and posmId to the binding values
            $bindValues[] = $storeId;
            $bindValues[] = $posmId;

            // Bind parameters dynamically
            $bindTypes = str_repeat('s', count($bindValues));
            $sqlUpdate->bind_param($bindTypes, ...$bindValues);

            // Execute the query
            if ($sqlUpdate->execute()) {
                return true; // Successful update
            } else {
                // Return the error message for better error handling
                return "Error: " . $sqlUpdate->error;
            }
        }

        // Now you can use these column positions to retrieve data from the sheet
        $maxRow = $sheet->getHighestRow();

        // Iterate through rows and access data by column position
        for ($row = 2; $row <= $maxRow; $row++) {

            $storeCode = $sheet->getCell($columns['storeCode'] . $row)->getValue();
            $pposmId = $sheet->getCell($columns['pposmId'] . $row)->getValue();
            $question1 = $sheet->getCell($columns['question1'] . $row)->getValue();
            $question2 = $sheet->getCell($columns['question2'] . $row)->getValue();
            $question3 = $sheet->getCell($columns['question3'] . $row)->getValue();
            $question4 = $sheet->getCell($columns['question4'] . $row)->getValue();
            $question5 = $sheet->getCell($columns['question5'] . $row)->getFormattedValue();
            $description = $sheet->getCell($columns['description'] . $row)->getValue();
            $overview = $sheet->getCell($columns['_OVV'] . $row)->getCalculatedValue();
            $check_in = $sheet->getCell($columns['_IN'] . $row)->getCalculatedValue();
            $check_out = $sheet->getCell($columns['_OUT'] . $row)->getCalculatedValue();
            $hotzone1 = $sheet->getCell($columns['_HZ1'] . $row)->getCalculatedValue();
            $hotzone2 = $sheet->getCell($columns['_HZ2'] . $row)->getCalculatedValue();
            $posm1 = $sheet->getCell($columns['_POSM1'] . $row)->getCalculatedValue();
            $posm2 = $sheet->getCell($columns['_POSM2'] . $row)->getCalculatedValue();
            $fee_info1 = $sheet->getCell($columns['_PXN1'] . $row)->getCalculatedValue();
            $fee_info2 = $sheet->getCell($columns['_PXN2'] . $row)->getCalculatedValue();
            $ilat = $sheet->getCell($columns['lat'] . $row)->getValue();
            $ilong = $sheet->getCell($columns['long'] . $row)->getValue();
            $status = $sheet->getCell($columns['STATUS'] . $row)->getValue();
            $note = $sheet->getCell($columns['NOTE'] . $row)->getValue();
            $winner = $sheet->getCell($columns['winnerRelationship'] . $row)->getValue();

            //Lấy StoreId 
            $sql = "SELECT * FROM stores WHERE storeCode = '$storeCode' AND status IS NOT NULL";
            $result = $conn->query($sql);
            $StoreId = null;
            if ($result->num_rows > 0) {
                while ($value = $result->fetch_assoc()) {
                    $StoreId = $value['id'];
                }
            }

            //Lấy Lat Long
            if ($ilat !== null && $ilong !== null) {
                $lat = str_replace(',', '.', $ilat);
                $long = str_replace(',', '.', $ilong);
            } else {
                $lat = '';
                $long = '';
            }


            $completeReport = "UPDATE stores SET isDone = 1 WHERE id = '$StoreId'";

            if ($status !== Null && $status !== 'Update') {

                //Get last StoreId
                $sql = "SELECT * FROM stores WHERE storeCode = '$storeCode'";
                $result = $conn->query($sql);
                if ($result->num_rows > 0) {
                    while ($value = $result->fetch_assoc()) {
                        $StoreIdAdd = $value['id'];
                    }
                }

                //CẬP NHẬT TRẠNG THÁI 
                switch ($status) {
                    case "KTC - Đóng cửa tạm thời":
                        $statusId = "DONG_TAM_THOI";
                        break;
                    case "KTC - Đóng cửa vĩnh viễn":
                        $statusId = "DONG_VINH_VIEN";
                        break;
                    case "KTC - Khác":
                        $statusId = "KHAC";
                        break;
                    case "KTC - Không tìm thấy cửa hàng":
                        $statusId = "KHONG_TIM_THAY";
                        break;
                    case "Thành công":
                        $statusId = "TC";
                        break;
                    case "KTC - Từ chối tiếp xúc":
                        $statusId = "TU_CHOI_TX";
                        break;
                        // Add more cases if needed for other status values
                    default:
                        $statusId = "TC";
                }

                $updateStatus = "UPDATE stores SET status = '$statusId' WHERE id = '$StoreIdAdd'";
                if ($conn->query($updateStatus) == true) {
                    $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreIdAdd, 'Updated' => 'STATUS Update'];
                }

                $trimnote = ($note !== null) ? str_replace(' ', '', $note) : null;
                if ($note != null && $trimnote != null) {
                    $updateStatus = "UPDATE stores SET note = '$note' WHERE id = '$StoreIdAdd'";
                    if ($conn->query($updateStatus) == true) {
                        $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreIdAdd, 'Updated' => 'NOTE Update'];
                    }
                }
                $completeReport = "UPDATE stores SET isDone = 1 WHERE id = '$StoreIdAdd'";
                $conn->query($completeReport);

                //ADD image
                if ($pposmId == null) {
                    $sqlS = "SELECT * FROM store_images WHERE storeId = '$StoreIdAdd' and typeCode = 'overview' ";
                    $sqlF = "SELECT * FROM store_images WHERE storeId = '$StoreIdAdd'";
                    $result = $conn->query($sqlS);
                    if ($conn->query($sqlS) == true && $result->num_rows > 0) {
                        while ($value = $result->fetch_assoc()) {
                            if (($value['lat'] && $value['long'] !== null) || ($value['lat'] && $value['long'] !== '')) {
                                $lat = $value['lat'];
                                $long = $value['long'];
                            }
                        }
                        //overview
                        $trimmov = ($overview !== null) ? str_replace(' ', '', $overview) : null;
                        if ($overview != null && $trimmov != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $overview . '.jpg', 'overview', $lat, $long, '-1');
                        }
                        //check_in
                        $trimm0 = ($check_in !== null) ? str_replace(' ', '', $check_in) : null;
                        if ($check_in != null && $trimm0 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $check_in . '.jpg', 'check_in', $lat, $long, '-1');
                        }
                        //check_out
                        $trimm1 = ($check_out !== null) ? str_replace(' ', '', $check_out) : null;
                        if ($check_out != null && $trimm1 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $check_out . '.jpg', 'check_out', $lat, $long, '-1');
                        }

                        $completeReport = "UPDATE stores SET isDone = 1 WHERE id = '$StoreIdAdd'";
                        if ($conn->query($completeReport)) {
                            $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreIdAdd, 'Updated' => 'IN-OUT'];
                        };
                    } else if ($conn->query($sqlS) == true) {
                        //overview
                        $trimmov = ($overview !== null) ? str_replace(' ', '', $overview) : null;
                        if ($overview != null && $trimmov != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $overview . '.jpg', 'overview', $lat, $long, '-1');
                        }
                        //check_in
                        $trimm0 = ($check_in !== null) ? str_replace(' ', '', $check_in) : null;
                        if ($check_in != null && $trimm0 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $check_in . '.jpg', 'check_in', $lat, $long, '-1');
                        }
                        //check_out
                        $trimm1 = ($check_out !== null) ? str_replace(' ', '', $check_out) : null;
                        if ($check_out != null && $trimm1 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $check_out . '.jpg', 'check_out', $lat, $long, '-1');
                        }

                        $completeReport = "UPDATE stores SET isDone = 1 WHERE id = '$StoreIdAdd'";
                        if ($conn->query($completeReport)) {
                            $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreIdAdd, 'Updated' => 'OVV-IN-OUT'];
                        };
                    }
                }
                if ($pposmId !== null) {
                    /* --------------START update pposm question */
                    $sql3 = "UPDATE store_mapping_pposms SET question1 = '$question1',   question2 = '$question2', question3 = '$question3',question4 = '$question4',  question5 = '$question5',  description = '$description',active = 1 WHERE storeId = '$StoreIdAdd' AND pposmId='$pposmId'";
                    if ($conn->query($sql3) == true) {
                        $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreIdAdd, 'Updated' => ' POSM'];
                    }
                    /* END done */

                    // cap nhat hinh anh trong store_images

                    $sql4 = "SELECT * FROM store_images WHERE storeId = '$StoreIdAdd' and typeCode = 'overview' ";
                    $sql5 = "SELECT * FROM store_images WHERE storeId = '$StoreIdAdd'";
                    // them du lieu vao store_images
                    $result4 = $conn->query($sql4);
                    $result5 = $conn->query($sql5);
                    if ($conn->query($sql4) == true && $result4->num_rows > 0) {
                        // Hiển thị dữ liệu nếu có

                        while ($value = $result4->fetch_assoc()) {
                            if (($value['lat'] && $value['long'] !== null) || ($value['lat'] && $value['long'] !== '')) {
                                $lat = $value['lat'];
                                $long = $value['long'];
                            }
                        }

                        //check_in
                        $trimm0 = ($check_in !== null) ? str_replace(' ', '', $check_in) : null;
                        if ($check_in != null && $trimm0 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $check_in . '.jpg', 'check_in', $lat, $long, '-1');
                        }
                        //check_out
                        $trimm1 = ($check_out !== null) ? str_replace(' ', '', $check_out) : null;
                        if ($check_out != null && $trimm1 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $check_out . '.jpg', 'check_out', $lat, $long, '-1');
                        }

                        //posm1
                        $trimm2 = ($posm1 !== null) ? str_replace(' ', '', $posm1) : null;
                        if ($posm1 != null && $trimm2 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $posm1 . '.jpg', 'posm', $lat, $long, $pposmId);
                        }
                        //posm2
                        $trimm3 = ($posm2 !== null) ? str_replace(' ', '', $posm2) : null;
                        if ($posm2 != null && $trimm3 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $posm2 . '.jpg', 'posm', $lat, $long, $pposmId);
                        }
                        //fee_info
                        $trimm4 = ($fee_info1 !== null) ? str_replace(' ', '', $fee_info1) : null;
                        if ($fee_info1 != null && $trimm4 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $fee_info1 . '.jpg', 'fee_info', $lat, $long, '-1');
                        }
                        //fee_info
                        $trimm5 = ($fee_info2 !== null) ? str_replace(' ', '', $fee_info2) : null;
                        if ($fee_info2 != null && $trimm5 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $fee_info2 . '.jpg', 'fee_info', $lat, $long, '-1');
                        }
                        //hotzone
                        $trimm6 = ($hotzone1 !== null) ? str_replace(' ', '', $hotzone1) : null;
                        if ($hotzone1 != null && $trimm6 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $hotzone1 . '.jpg', 'hot_zone', $lat, $long, '-1');
                        }
                        //hotzone
                        $trimm7 = ($hotzone2 !== null) ? str_replace(' ', '', $hotzone2) : null;
                        if ($hotzone2 != null && $trimm7 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $hotzone2 . '.jpg', 'hot_zone', $lat, $long, '-1');
                        }

                        $conn->query($completeReport);
                        $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreIdAdd, 'Updated' => 'ImageOVV'];
                    } else if ($conn->query($sql5) == true) {

                        $trimmov = ($overview !== null) ? str_replace(' ', '', $overview) : null;
                        if ($overview != null && $trimmov != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $overview . '.jpg', 'overview', $lat, $long, '-1');
                        }
                        //check_in
                        $trimm0 = ($check_in !== null) ? str_replace(' ', '', $check_in) : null;
                        if ($check_in != null && $trimm0 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $check_in . '.jpg', 'check_in', $lat, $long, '-1');
                        }
                        //check_out
                        $trimm1 = ($check_out !== null) ? str_replace(' ', '', $check_out) : null;
                        if ($check_out != null && $trimm1 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $check_out . '.jpg', 'check_out', $lat, $long, '-1');
                        }
                        //posm1
                        $trimm2 = ($posm1 !== null) ? str_replace(' ', '', $posm1) : null;
                        if ($posm1 != null && $trimm2 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $posm1 . '.jpg', 'posm', $lat, $long, $pposmId);
                        }
                        //posm2
                        $trimm3 = ($posm2 !== null) ? str_replace(' ', '', $posm2) : null;
                        if ($posm2 != null && $trimm3 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $posm2 . '.jpg', 'posm', $lat, $long, $pposmId);
                        }
                        //fee_info
                        $trimm4 = ($fee_info1 !== null) ? str_replace(' ', '', $fee_info1) : null;
                        if ($fee_info1 != null && $trimm4 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $fee_info1 . '.jpg', 'fee_info', $lat, $long, '-1');
                        }
                        //fee_info
                        $trimm5 = ($fee_info2 !== null) ? str_replace(' ', '', $fee_info2) : null;
                        if ($fee_info2 != null && $trimm5 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $fee_info2 . '.jpg', 'fee_info', $lat, $long, '-1');
                        }
                        //hotzone
                        $trimm6 = ($hotzone1 !== null) ? str_replace(' ', '', $hotzone1) : null;
                        if ($hotzone1 != null && $trimm6 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $hotzone1 . '.jpg', 'hot_zone', $lat, $long, '-1');
                        }
                        //hotzone
                        $trimm7 = ($hotzone2 !== null) ? str_replace(' ', '', $hotzone2) : null;
                        if ($hotzone2 != null && $trimm7 != null) {
                            insertImageIntoStore($conn, $StoreIdAdd, 'storeImages/' . $hotzone2 . '.jpg', 'hot_zone', $lat, $long, '-1');
                        }
                        $conn->query($completeReport);
                        $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreIdAdd, 'Updated' => 'ImageOutOVV'];
                    }
                }

                //$results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreIdAdd, 'Updated' => ' Data'];
            }

            if ($status === 'Update') {

                if ($ilat !== null && $ilong !== null) {
                    $lat = str_replace(',', '.', $ilat);
                    $long = str_replace(',', '.', $ilong);
                    $sql = "UPDATE store_images SET lat = '$lat' , `long` = '$long' WHERE storeId = '$StoreId' and typeCode = 'overview'";
                    $conn->query($sql);
                    $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreId, 'Updated' => 'Update Lat/Long'];
                } else if ($ilat == null && $ilong == null) {
                    $lat = '';
                    $long = '';
                }

                $winnerTrim = ($winner !== null) ? str_replace(' ', '', $winner) : null;

                if ($winner !== null && $winnerTrim !== null) {
                    $sql = "UPDATE stores set winnerRelationship = '$winner' WHERE id = '$StoreId'";
                    $conn->query($sql);
                    $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreId, 'Updated' => 'Update winnerRelationship'];
                }


                if ($pposmId !== null) {

                    $sql_posm = "SELECT * FROM store_mapping_pposms WHERE storeId = '$StoreId' AND pposmId = '$pposmId'";
                    if ($conn->query($sql_posm) == TRUE) {
                        $completePOSM = "UPDATE store_mapping_pposms SET active = 1 WHERE storeId = '$StoreId' AND pposmId = '$pposmId'";
                        $conn->query($completePOSM);

                        $trimm1 = ($question1 !== null) ? str_replace(' ', '', $question1) : null;
                        $trimm2 = ($question2 !== null) ? str_replace(' ', '', $question2) : null;
                        $trimm3 = ($question3 !== null) ? str_replace(' ', '', $question3) : null;
                        $trimm4 = ($question4 !== null) ? str_replace(' ', '', $question4) : null;
                        $trimm5 = ($question5 !== null) ? str_replace(' ', '', $question5) : null;
                        $trimDescription = ($description !== null) ? str_replace(' ', '', $description) : null;

                        // Check if any of the fields are not null and not an empty string
                        if (
                            ($question1 !== null && $trimm1 !== null) ||
                            ($question2 !== null && $trimm2 !== null) ||
                            ($question3 !== null && $trimm3 !== null) ||
                            ($question4 !== null && $trimm4 !== null) ||
                            ($question5 !== null && $trimm5 !== null) ||
                            ($description !== null && $trimDescription !== null)
                        ) {

                            updatePosm($conn, $StoreId, $pposmId, $question1, $question2, $question3, $question4, $question5, $description);
                        }
                        $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreId, 'Updated' => 'Update Posm'];
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
                    $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreId, 'Updated' => 'Update Images'];
                }
            }

            $sql = "SELECT * FROM stores WHERE storeCode = '$storeCode'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                while ($value = $result->fetch_assoc()) {
                    $StoreIdWinner = $value['id'];
                }
                $winnerTrim = ($winner !== null) ? str_replace(' ', '', $winner) : null;

                if ($winner !== null && $winnerTrim !== null) {
                    $sql = "UPDATE stores set winnerRelationship = '$winner' WHERE id = '$StoreIdWinner'";
                    if ($conn->query($sql) == true) {
                        $results[] = ['storeCode' => $storeCode, 'StoreId' => $StoreIdWinner, 'Updated' => 'Update winnerRelationship'];
                    }
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
