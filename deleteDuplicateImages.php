<?php

// Include file connection
include 'connection.php';

try {
    // Your SQL query with CTE to get the count
    $countQuery = "
        SELECT COUNT(*) AS rowCount
        FROM (
            SELECT 
                id,
                imagePath,
                storeId,
                ROW_NUMBER() OVER (PARTITION BY imagePath, storeId ORDER BY id DESC) AS RowNum
            FROM $database.store_images
        ) AS Subquery
        WHERE RowNum > 1;
    ";

    // Execute the count query
    $countResult = $conn->query($countQuery);
    $countRow = $countResult->fetch_assoc();
    $rowCount = $countRow['rowCount'];

    // Create a temporary table to store CTE results
    $createTempTableQuery = "CREATE TEMPORARY TABLE temp_cte AS (
        SELECT 
            id,
            imagePath,
            storeId,
            ROW_NUMBER() OVER (PARTITION BY imagePath, storeId ORDER BY id DESC) AS RowNum
        FROM $database.store_images
    );";

    $conn->query($createTempTableQuery);

    // Your SQL DELETE query using the temporary table
    $deleteQuery = "DELETE FROM $database.store_images WHERE id IN (
        SELECT id FROM temp_cte WHERE RowNum > 1
    );";

    // Execute the DELETE query
    $result = $conn->query($deleteQuery);

    // Retrieve data from the temporary table
    $selectTempDataQuery = "SELECT * FROM temp_cte;";
    $tempResult = $conn->query($selectTempDataQuery);

    // Check if the query was successful
    if ($tempResult !== false) {
        $tempData = array(); // Initialize an array to store temporary table data

        // Fetch each row from the temporary table
        while ($row = $tempResult->fetch_assoc()) {
            $tempData[] = $row;
        }

        // Generate HTML table directly within PHP
        $htmlTable = '<table class="table table-bordered">';
        $htmlTable .= '<thead><tr>';
        foreach ($tempData[0] as $key => $value) {
            $htmlTable .= '<th scope="col">' . htmlspecialchars($key) . '</th>';
        }
        $htmlTable .= '</tr></thead><tbody>';

        foreach ($tempData as $row) {
            $htmlTable .= '<tr>';
            foreach ($row as $value) {
                $htmlTable .= '<td>' . htmlspecialchars($value) . '</td>';
            }
            $htmlTable .= '</tr>';
        }

        $htmlTable .= '</tbody></table>';

        // Output the HTML table
        echo $htmlTable;

        // Drop the temporary table
        $dropTempTableQuery = "DROP TEMPORARY TABLE IF EXISTS temp_cte;";
        $conn->query($dropTempTableQuery);
    } else {
        // Output an error message
        echo "Error retrieving data from the temporary table.";
    }
} catch (Exception $e) {
    // Output an error message
    echo "Error: " . $e->getMessage();
}

// Close the database connection
$conn->close();
?>
