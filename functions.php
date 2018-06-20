<?php
/**
 * Created by PhpStorm.
 * User: DDS
 * Date: 2018.06.19
 * Time: 16:05
 */

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
    return $label;
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