<?php
/**
 * Created by PhpStorm.
 * User: DDS
 * Date: 2018.06.19
 * Time: 14:58
 */

require_once(__DIR__ . "/functions.php");

$conn = connect_db();

if ($conn->connect_error) {
    die("Connection failed : " . $conn->connect_error);
}

$action     = isset($_GET['action'])   ? $_GET['action']   : false;

if ($action === false) {
    return;
}

$parents_urls = get_data_by_args($conn, "links", array("id" ,"parent_id", "url"));

$parents = get_parents($parents_urls);
$urls    = get_urls($parents_urls);

// --------- CREATE ACTION ---------

if ($action == 'create') {
    $label     = isset($_POST['new_label'])     ? $conn->real_escape_string(strip_tags($_POST['new_label'])) : false;
    $url       = isset($_POST['new_url'])       ? $conn->real_escape_string(strip_tags($_POST['new_url']))   : false;
    $menu_type = isset($_POST['new_menu_type']) ? intval($_POST['new_menu_type'])                            : 0;
    $sort      = isset($_POST['new_sort'])      ? intval($_POST['new_sort'])                                 : 0;
    $active    = isset($_POST['new_active'])    ? $_POST['new_active'] == "on" ? 1 : 0                       : 0;
    $parent    = isset($_POST['new_parent'])    ? intval($_POST['new_parent'])                               : 0;
    $has_child = 0;

    if ((strlen($label) == 0)) {
        echo "Missing or invalid parameters.\n";

        redirect_to_main(true);

        return;
    }

    if (strlen($url) == 0) {
        $url = encode_label_as_url($label);
    } else {
        $url = rawurlencode($url);
    }

    if (!validate_url($parents, $urls, $parent, $url)) {
        echo "Invalid URL for new menu - URLs must be unique on the same level.\n";

        redirect_to_main(true);

        return;
    }

    if (($parent != 0) && (!isset($parents[$parent]))) {
        echo "Invalid parent ID.\n";

        redirect_to_main(true);

        return;
    }

    $sql = "INSERT  INTO `links` (label, url, menutype, sort, active, has_child, parent_id)
VALUES ('$label', '$url', $menu_type, $sort, $active, $has_child, $parent)";

    $result = $conn->query($sql);

    if (!$result) {
        echo "Error creating new menu.\n";
        echo "SQL = $sql\n";

        redirect_to_main(true);

        return;
    }

    if (!has_children($parents, $parent)) {
        if (!update_has_child($conn, $parent, 1)) {
            echo "Error updating has_child for menu $parent";

            redirect_to_main(true);

            return;
        }
    }

    redirect_to_main(false);

    return;
}

// --------- UPDATE ACTION ---------

$was_error = false;

if ($action == 'update') {
    $save_menus = isset($_POST['menu_id']) ? $_POST['menu_id'] : false;

    if (!is_array($save_menus)) {
        echo "Missing parameters.\n";

        redirect_to_main(true);

        return;
    }

    sanitize_array($conn,$save_menus, true);

    $subact = isset($_POST['subact']) ? $_POST['subact'] : "Save";

    if ($subact == "Save") {
        $statement = $conn->prepare("UPDATE `links` SET label = ?, url = ?, menutype = ?,
 sort = ?, active = ?, has_child = ?, parent_id = ? WHERE id = ?");

        $bound = false;

        foreach ($save_menus as $id) {
            $id        = intval($id);
            $label     = isset($_POST[$id . "_label"])    ? strip_tags($_POST[$id . "_label"])      : "";
            $url       = isset($_POST[$id . "_url"])      ? strip_tags($_POST[$id . "_url"])        : "";
            $menu_type = isset($_POST[$id . "_menutype"]) ? intval($_POST[$id . "_menutype"])       : 0;
            $sort      = isset($_POST[$id . "_sort"])     ? intval($_POST[$id . "_sort"])           : "";
            $active    = isset($_POST[$id . "_active"])   ? $_POST[$id . "_active"] == "on" ? 1 : 0 : 0;
            $parent    = isset($_POST[$id . "_parent"])   ? intval($_POST[$id . "_parent"])         : 0;
            $has_child = has_children($parents, $id)  ? 1                                       : 0;

            if (!isset($parents[$id])) {
                echo "Menu $id does not exist.\n";

                $was_error = true;

                continue;
            }

            if (is_child($parents, $parent, $id)) {
                echo "Menu $id - menu cannot be the child of itself ($parent).\n";

                $was_error = true;

                continue;
            }

            if ((strlen($label) == 0)) {
                echo "Missing label for menu $id.\n";

                $was_error = true;

                continue;
            }

            if (strlen($url) == 0) {
                $url = encode_label_as_url($label);
            } else {
                $url = rawurlencode($url);
            }

            if (!validate_url($parents, $urls, $id, $parent, $url)) {
                echo "Invalid URL for menu $id - URLs must be unique on the same level.\n";

                $was_error = true;

                continue;
            }

            if ($bound == false) {
                $statement->bind_param("ssiiiiii", $label, $url, $menu_type, $sort, $active, $has_child, $parent, $id);

                $bound = true;
            }

            $result = $statement->execute();

            if (!$result) {
                echo "Error updating menu $id.\n";

                $was_error = true;
            } else {
                // if all is OK, update parents
                $parents[$id] = $parent;
            }
        }

        $statement->close();
    }

    if ($subact == "Delete") {
        $set = implode(", ", $save_menus);

        $sql = "DELETE FROM `links` WHERE id IN ($set)";

        $result = $conn->query($sql);

        if (!$result) {
            echo "Error deleting items: $set\n";
            echo "SQL = $sql\n";

            redirect_to_main(true);

            return;
        }

        // remove from parents array
        foreach($save_menus as $id) {
            unset($parents[$id]);
        }
    }

    $ids_with_children = array();

    // get all ids with children
    foreach ($parents as $id => $parent) {
        if (has_children($parents, $id)) {
            $ids_with_children[] = $id;
        }
    }

    // update has_child values
    if (count($ids_with_children) > 0) {
        $set = implode(", ", $ids_with_children);

        $sql1 = "UPDATE `links` SET has_child = 1 WHERE id IN ($set)";
        $sql2 = "UPDATE `links` SET has_child = 0 WHERE id NOT IN ($set)";

        $result1 = $conn->query($sql1);
        $result2 = $conn->query($sql2);

        if (!$result1 || !$result2) {
            echo "Error updating has_child.\n";
            echo "SQL1 = $sql1\n";
            echo "SQL2 = $sql2\n";

            redirect_to_main(true);

            return;
        }
    } else {
        $sql1 = "UPDATE `links` SET has_child = 0";

        $result1 = $conn->query($sql1);

        if (!$result1) {
            echo "Error updating has_child.\n";
            echo "SQL = $sql\n";

            redirect_to_main(true);

            return;
        }
    }

    redirect_to_main($was_error);

    return;
}

// --------- CREATE MENU ACTION ---------

if ($action == 'create_menu') {
    $menu_type_name = isset($_POST['menu_type_name']) ? $_POST['menu_type_name'] : false;

    if (strlen($menu_type_name) == 0) {
        echo "No menu type name entered.\n";

        redirect_to_main(true);

        return;
    }

    $menu_type_name = $conn->real_escape_string(strip_tags($menu_type_name));

    $sql = "INSERT INTO `menutypes` (name) VALUES (\"" . $menu_type_name . "\")";

    $result = $conn->query($sql);

    if (!$result) {
        echo "Error creating new menu type.\n";
        echo "SQL = $sql\n";

        redirect_to_main(true);

        return;
    }

    redirect_to_main(false);

    return;
}

// --------- UPDATE MENU ACTION ---------

if ($action == 'update_menus') {
    $save_menu_types = isset($_POST['menu_types']) ? $_POST['menu_types'] : false;

    if (!is_array($save_menu_types)) {
        echo "Missing parameters.\n";

        redirect_to_main(true);

        return;
    }

    sanitize_array($conn,$save_menu_types, true);

    $subact = isset($_POST['subact']) ? $_POST['subact'] : false;

    if ($subact == "Save") {
        $statement = $conn->prepare("UPDATE `menutypes` SET name = ? WHERE id = ?");
        $bound = false;

        foreach ($save_menu_types as $id) {
            $id = intval($id);
            $menu_type_name = isset($_POST['menu_type_' . $id]) ? $conn->real_escape_string(strip_tags($_POST['menu_type_' . $id])) : false;

            if (strlen($menu_type_name) == 0) {
                echo "Missing name for menu type $id.\n";

                $was_error = true;

                continue;
            }

            if (!$bound) {
                $statement->bind_param("si", $menu_type_name, $id);
                $bound = true;
            }

            $result = $statement->execute();

            if (!$result) {
                echo "Error updating menu type $id.\n";

                $was_error = true;

                continue;
            }
        }
    }

    if ($subact == "Delete") {
        $set = implode(", ", $save_menu_types);

        $sql = "DELETE FROM `menutypes` WHERE id IN ($set)";

        $result = $conn->query($sql);

        if (!$result) {
            echo "Error deleting menu types. Make sure none of these menu types are used.\n";
            echo "SQL = $sql\n";

            redirect_to_main(true);

            return;
        }
    }

    redirect_to_main($was_error);

    return;
}
