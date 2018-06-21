<?php
/**
 * Created by PhpStorm.
 * User: DDS
 * Date: 6/21/2018
 * Time: 6:43 PM
 */

require_once(__DIR__ . "/functions.php");

$conn = connect_db();

function generate_menu_html($conn, $parent_id = 0, $lvl = 0, $base="") {
    $items = get_menus_by_parent_id($conn, $parent_id);

    if (count($items) == 0) {
        return;
    }

    echo "<ul class='navigate'>";
        foreach ($items as $id => $item) {
            echo "<li class='level" . $lvl . "'><a href='" . $base . $item->url . "'>" . $item->label . "</a></li>";

            $new_base = $base . $item->url . '/';

            generate_menu_html($conn, $id, $lvl++, $new_base);
        }
    echo "</ul>";


}

if ($conn->connect_error) {
    die("Connection failed : " . $conn->connect_error);
}

echo "<!DOCTYPE html>\n";
echo "<head><title>Links</title></head>\n";
echo "<body>\n";

generate_menu_html($conn);

echo "</body>\n";
echo "</html>\n";
