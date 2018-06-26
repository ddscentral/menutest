<?php
/**
 * Created by PhpStorm.
 * User: DDS
 * Date: 6/18/2018
 * Time: 7:23 PM
 */

require_once(__DIR__ . "/functions.php");

$conn = connect_db();

if ($conn->connect_error) {
    die("Connection failed : " . $conn->connect_error);
}

$parents    = get_parents($conn);
$labels     = get_labels($conn);
$menu_types = get_menu_types($conn);

echo "<!DOCTYPE html>";
echo "<htm>\n";
echo "<head>\n";
echo "<title>Links Admin</title>\n";
echo "</head>\n";
echo "<body>\n";

$sql = "SELECT * FROM `links`";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<form action='admin_save.php?action=update' method='POST'>\n";
    echo "<table>\n";
    echo "<tr>\n";
        echo"<th>&#35;</th>\n";
        echo "<th>ID</th>\n";
        echo "<th>Label</th>\n";
        echo "<th>URL</th>\n";
        echo "<th>Menu Type</th>\n";
        echo "<th>Sort</th>\n";
        echo "<th>Active</th>\n";
        echo "<th>Has Children</th>\n";
        echo "<th>Parent</th>\n";
    echo "</tr>\n";

    while ($row = $result->fetch_object()) {
        echo "<tr>\n";
            echo "<td><input type='checkbox' name='menu_id[]' value='".$row->id."'></td>\n";
            echo "<td>".$row->id."</td>\n";
            echo "<td><input type='text' name='"   . $row->id . "_label' value='"    . $row->label    . "'></td>\n";
            echo "<td><input type='text' name='"   . $row->id . "_url' value='"      . $row->url      . "'></td>\n";
            echo "<td>\n";

            //echo "<option value='0'>---------</option>";

            generate_dropdown($row->id . "_menutype", $menu_types, $row->menutype);

            echo "</td>\n";
            echo "<td><input type='text' name='"   . $row->id . "_sort' value='"     . $row->sort     . "'></td>\n";

            $checked = $row->active == 1 ? "checked" : "";

            echo "<td><input type='checkbox' name='" . $row->id . "_active' " . $checked . "></td>\n";

            $checked = $row->has_child == 1 ? "checked" : "";

            echo "<td><input type='checkbox' name='" . $row->id . "_has_child' " . $checked . " disabled></td>\n";
            echo "<td>\n";
               echo "<select name='".$row->id."_parent'>\n";
                    echo "<option value='0'>---------</option>\n";
                    
                    foreach($labels as $id => $label) {
                        if (is_child($parents, $id, $row->id)) {
                            continue;
                        }

                        $selected = $row->parent_id == $id ? " selected='selected'" : "";
                        echo "<option value='" . $id . "'" . $selected . ">" . $label . "</option>\n";
                    }
                echo "</select>\n";
            echo "</td>\n";
        echo "</tr>\n";
    }

    echo "<tr>\n";
        echo "<td colspan='4'><input type='submit' value='Save' name='subact'></td>\n";
        echo "<td colspan='5'><input type='submit' value='Delete' name='subact'></td>\n";
    echo "</tr>\n";

    $result->close();

    echo "</table>\n";
    echo "</form>\n";
}

echo "<hr />\n";

echo "<form action='admin_save.php?action=create' method='POST'>\n";;
    echo "<label for='new_label'>Label</label>\n";
    echo "<input type='text' placeholder='text' name='new_label'>";
    echo "<label for='new_url'>URL</label>\n";
    echo "<input type='text' placeholder='text' name='new_url'>\n";
    echo "<label for='new_menu_type'>Menu Type</label>\n";
    generate_dropdown("new_menu_type", $menu_types, -1);
    echo "<label for='new_sort'>Sort</label>\n";
    echo "<input type='number' placeholder='number' name='new_sort'>\n";
    echo "<label for='new_active'>Active</label>\n";
    echo "<input type='checkbox' name='new_active'>\n";

    echo "<label for='new_parent'>Parent</label>\n";

    $d_labels = $labels;
    $d_labels[0] = '---------';
    generate_dropdown("new_parent", $d_labels, 0);
    echo "<input type='submit' value='Create'>\n";
echo "</form>\n";

echo "<hr />\n";

echo "<form action='admin_save.php?action=update_menus' method='POST'>\n";
echo "<table>\n";
echo "<tr>\n</tr>";
    echo "<th>&#35;</th>\n";
    echo "<th>ID</th>\n";
    echo "<th>Menu Type Name</th>\n";
echo "</tr>\n";

foreach ($menu_types as $id => $menu_type_name) {
    echo "<tr>\n";
        echo "<td><input type='checkbox' name='menu_types[]' value='$id'></td>\n";
        echo "<td>$id</td>\n";
        echo "<td><input type='text' name='menu_type_$id' value='$menu_type_name'></td>\n";
    echo "</tr>\n";

}

echo "<tr>\n";
    echo "<td><input type='submit' name='subact' value='Save'></td>\n";
    echo "<td><input type='submit' name='subact' value='Delete'></td>\n";
echo "</tr>\n";
echo "</table>\n";
echo "</form>\n";

echo "<hr />\n";

echo "<form action='admin_save.php?action=create_menu' method='POST'>\n";
    echo "<label for='menu_type_name'>Menu Type Name</label>\n";
    echo "<input type='text' name='menu_type_name' placeholder='Enter menu type name'>\n";
    echo "<input type='submit' value='Create'>\n";
echo "</form>\n";
echo "</body>\n";
echo "</html>\n";

$conn->close();