<?php
/**
 * Created by PhpStorm.
 * User: DDS
 * Date: 2018.06.19
 * Time: 16:05
 */

require_once(__DIR__ . "/config.php");

function is_child(&$parents, $id, $parent_id) {
    if (($id == 0) && ($parent_id != 0)) {
        return false;
    }

    if (($parent_id == 0)) {
        return true;
    }

    if ($parents[$id] == $parent_id) {
        return true;
    }

    if ($id == $parent_id) {
        return true;
    }

    if ($parents[$id] == 0) {
        return false;
    }

    return is_child($parents, $parents[$id], $parent_id);
}

function get_parents(&$conn) {
    return get_param($conn, 'parent_id');
}

function get_param(&$conn, $param)
{
    return get_data_by_args($conn, "links", array("id", $param), "", $param);
}

function get_labels(&$conn) {
    return get_param($conn, 'label');
}

function get_urls(&$conn) {
    return get_param($conn, 'url');
}

function has_children(&$parents, $id) {
    if ($id == 0) {
        return false;
    }

    foreach ($parents as $parent) {
        if ($parent ==  $id) {
            return true;
        }
    }

    return false;
}

function update_has_child(&$conn, $id, $has_child) {
    $s_has_child = intval($has_child);
    $s_id        = intval($id);

    $sql = "UPDATE `links` SET has_child = " . $s_has_child ." WHERE id = " . $s_id;

    $result = $conn->query($sql);

    return $result;
}

function encode_label_as_url($label) {
    $ulabel = strtolower($label);
    $ulabel = str_replace(" ", "-", $ulabel);
    $ulabel = str_replace("/", "-", $ulabel);

    $ulabel = rawurlencode($ulabel);

    return $ulabel;
}

function redirect_to_main($error) {
    if ($error == true) {
        unset($_POST);
        header("Refresh: 3; url='admin.php'");
        echo "Redirecting to main page...\n";
    } else {
        header("Location: admin.php");
    }
}

function validate_url(&$parents, &$urls, $id, $parent, $url) {
    foreach ($urls as $uid => $uurl) {
        // check if this item has the same parent
        if (($parents[$uid] == $parent) && ($uid != $id)) {
            // if it does, check if the specified URL and this item's URL are the same
            // TODO: Question: CASE SENSITIVE ?
            if (strcmp($url, $uurl) == 0) {
                // if they are, return false
                return false;
            }
        }
    }

    return true;
}

function get_data_by_args(&$conn, $table, $what, $args = "", $field_id = "") {
    if (is_array($what)) {
        $what = count($what) > 0 ? implode(", ", $what) : "*";
    }

    $sql = "SELECT $what FROM `$table`";

    if (strlen($args) > 0) {
        $sql .= " WHERE $args";
    }

    $result = $conn->query($sql);

    $params = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_object()) {
                $params[$row->id] = strlen($field_id) > 0 ? $row->$field_id : $row;
        }
    }

    return $params;
}

function get_menus_by_parent_id(&$conn, $id) {
    return get_data_by_args($conn, "links", "*", "parent_id = $id && active = 1 ORDER BY sort");
}

function connect_db() {
    return new mysqli(SQL_SERVER_NAME, SQL_SERVER_USER, SQL_SERVER_PASS, SQL_SERVER_DBNAME);
}

function get_menu_types(&$conn) {
    return get_data_by_args($conn, "menutypes", "*", "", "name");
}

function generate_dropdown($name, &$items, $selected_item) {
    if (count($items) == 0) {
        return;
    }

    echo "<select name='$name'>\n";

    foreach ($items as $id => $item) {
        $selected = $id == $selected_item ? " selected='selected'" : "";

        echo "<option value='$id'$selected>$item</option>\n";
    }

    echo "</select>\n";
}

function sanitize_array(&$conn, &$arr, $ints = false) {
    if (count($arr) == 0) {
        return;
    }

    foreach ($arr as $id => $elem) {
        $arr[$id] = $ints ? intval($elem) : $conn->real_escape_string(strip_tags($elem));
    }
}