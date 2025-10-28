<?php
include_once("includes/mysql_connect.php");

$parameters = $_GET;

// Use Query and SELECT statement to get the shop information
$query = "SELECT * FROM shops WHERE shop_url='" . $parameters['shop'] . "' LIMIT 1";
$result = $mysql->query($query);

// Check if the number of rows is less than 1
// If it's less than 1, that means we need to redirect the merchant to the installation page
if ($result->num_rows < 1) {
    header("Location: install.php?shop=" . $_GET['shop']);
    exit();
}

// Use fetch assoc function to get the records
$store_data = $result->fetch_assoc();


?>
