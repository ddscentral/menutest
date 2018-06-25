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
    $sql = "SELECT id,$param FROM `links`";

    $result = $conn->query($sql);

    $params = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_object()) {
            $params[$row->id] = $row->$param;
        }
    }

    return $params;
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

function get_data_by_args(&$conn, $args = "") {
    $sql = "SELECT * FROM `links`";

    if (strlen($args) > 0) {
        $sql .= " WHERE $args";
    }

    $result = $conn->query($sql);

    $params = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_object()) {
            $params[$row->id] = $row;
        }
    }

    return $params;
}

function get_menus_by_parent_id(&$conn, $id) {
    return get_data_by_args($conn, "parent_id = $id && active = 1 ORDER BY sort");
}

function connect_db() {
    return new mysqli(SQL_SERVER_NAME, SQL_SERVER_USER, SQL_SERVER_PASS, SQL_SERVER_DBNAME);
}

function get_menu_types(&$conn) {
    $sql = "SELECT * FROM `menutypes`";

    $result = $conn->query($sql);

    $menu_types = array();

    while ($row = $result->fetch_object()) {
        $menu_types[$row->id] = $row->name;
    }

    return $menu_types;
}