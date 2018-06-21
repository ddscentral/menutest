<?php
/**
 * Created by PhpStorm.
 * User: DDS
 * Date: 6/21/2018
 * Time: 6:43 PM
 */

require_once(__DIR__ . "/functions.php");

$conn = connect_db();

if ($conn->connect_error) {
    die("Connection failed : " . $conn->connect_error);
}

$ids = get_menus_by_parent_id($conn, 0);

print_r($ids);