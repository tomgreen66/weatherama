<?php
/**
* $Id: dbtree_demo.php,v 2.0 2005/09/08 19:32:45 Kuzma Exp $
*
* Copyright (C) 2005 Kuzma Feskov <kuzma@russofile.ru>
*
* This file may be distributed and/or modified under the terms of the
* "GNU General Public License" version 2 as published by the Free
* Software Foundation and appearing in the file LICENSE included in
* the packaging of this file.
*
* This file is provided AS IS with NO WARRANTY OF ANY KIND, INCLUDING
* THE WARRANTY OF DESIGN, MERCHANTABILITY AND FITNESS FOR A PARTICULAR
* PURPOSE.
*
* The "GNU General Public License" (GPL) is available at
* http:*www.gnu.org/copyleft/gpl.html.
*/
//ini_set("display_errors","1");
//ERROR_REPORTING(E_ALL);
//ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Nested set category tree</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
</head>
<body>
<pre><?
	//var_dump($_SERVER["REDIRECT_URL"]);
	?></pre>
<h2>Manage category tree</h2>
<!--
[<a href="dbtree_visual_demo.php?mode=map">Visual demo (Site map)</a>] [<a href="dbtree_visual_demo.php?mode=ajar">Visual demo (Ajar tree)</a>] [<a href="dbtree_visual_demo.php?mode=branch">Visual demo (Branch)</a>]
-->
<?php

if( isset($_GET['tbl']) && in_array($_GET['tbl'], array('listing_category','listing_approach')) ){
	define('DB_TABLE', $_GET['tbl']);
	$redirect = $_SERVER["REDIRECT_URL"];
}else{
	die('no tab');
}

define('DB_PREFIX', 'section');

// DB CLASS: adodb or db_mysql
define('DB_CLASS', 'db_mysql');

/** DB Host */
define('DB_HOST', 'localhost');

/** DB user name */
$DB_USER = 'root';

/** DB user password */
$DB_PASSWORD = 'floyd';

/** DB name */
// need to change this depending on category

define('DB_BASE_NAME', 'condiment');

/** Global cache settings */
define('DB_CACHE', FALSE);

/* ------------------------ ADDOB SETTINGS ------------------------ */
if (DB_CLASS == 'adodb') {
    /** Path to ADODB */
    define('ADODB_DIR', '../adodb');

    /** ADODB driver */
    define('DB_DRIVER', 'mysql');

    require_once(ADODB_DIR . '/adodb.inc.php');
    $ADODB_FETCH_MODE = 2; // ASSOC
    $ADODB_CACHE_DIR = ADODB_DIR . '/ADOdbcache';
    $db = &ADONewConnection(DB_DRIVER);
    $db->Connect(DB_HOST, $DB_USER, $DB_PASSWORD, DB_BASE_NAME);
    //$db->debug = TRUE; // Debugg
    if ('mysql' == DB_DRIVER) {
        $sql = 'SET NAMES utf8';
        $res = $db->Execute($sql);
    }
}

/* ------------------------ DB_MYSQL SETTINGS ------------------------ */
if (DB_CLASS == 'db_mysql') {
    require_once('db_mysql.class.php');

    $db = new db(DB_HOST, $DB_USER, $DB_PASSWORD, DB_BASE_NAME);

    $sql = 'SET NAMES utf8';
    $db->Execute($sql);

}
unset($DB_PASSWORD, $DB_USER);


/* ------------------------ NEW OBJECT ------------------------ */

require_once('dbtree.class.php');

// Create new object
$dbtree = new dbtree(DB_TABLE, DB_PREFIX, $db); //table , prefix

/* ------------------------ MOVE ------------------------ */

/* ------------------------ MOVE 2 ------------------------ */

// Method 2: Assigns a node with all its children to another parent.
if (!empty($_GET['action']) && 'move_2' == $_GET['action']) {

    // Move node ($_GET['section_id']) and its children to new parent ($_POST['section2_id'])
    $dbtree->MoveAll((int)$_GET['section_id'], (int)$_POST['section2_id']);

    // Check errors
    if (!empty($dbtree->ERRORS_MES)) {
        echo 'DB Tree Error!';
        echo '<pre>';
        print_r($dbtree->ERRORS_MES);
        if (!empty($dbtree->ERRORS)) {
            print_r($dbtree->ERRORS);
        }
        echo '</pre>';
        exit;
    }

    header('Location:' . $redirect);
    exit;
}

/* ------------------------ MOVE 1 ------------------------ */

// Method 1: Swapping nodes within the same level and limits of one parent with all its children.
if (!empty($_GET['action']) && 'move_1' == $_GET['action']) {

    // Change node ($_GET['section_id']) position and all its childrens to
    // before or after ($_POST['position']) node 2 ($_POST['section2_id'])
    $dbtree->ChangePositionAll((int)$_GET['section_id'], (int)$_POST['section2_id'], $_POST['position']);

    // Check class errors
    if (!empty($dbtree->ERRORS_MES)) {
        echo 'DB Tree Error!';
        echo '<pre>';
        print_r($dbtree->ERRORS_MES);
        if (!empty($dbtree->ERRORS)) {
            print_r($dbtree->ERRORS);
        }
        echo '</pre>';
        exit;
    }

    header('Location:' . $redirect);
    exit;
}

/* ------------------------ MOVE FORM------------------------ */

// Move section form
if (!empty($_GET['action']) && 'move' == $_GET['action']) {

    // Prepare the restrictive data for the first method:
    // Swapping nodes within the same level and limits of one parent with all its children
    $current_section = $dbtree->GetNodeInfo((int)$_GET['section_id']);
    $dbtree->Parents((int)$_GET['section_id'], array('section_id'), array('and' => array('section_level = ' . ($current_section[2] - 1))));

    // Check class errors
    if (!empty($dbtree->ERRORS_MES)) {
        echo 'DB Tree Error!';
        echo '<pre>';
        print_r($dbtree->ERRORS_MES);
        if (!empty($dbtree->ERRORS)) {
            print_r($dbtree->ERRORS);
        }
        echo '</pre>';
        exit;
    }

    $item = $dbtree->NextRow();
    $dbtree->Branch($item['section_id'], array('section_id', 'section_name'), array('and' => array('section_level = ' . $current_section[2])));

    // Create form
    ?>
    <table border="1" cellpadding="5" align="center">
        <tr>
            <td>
                Move section
            </td>
        </tr>
        <tr>
            <td>
                <form action="?action=move_1&section_id=<?=$_GET['section_id']?>" method="POST">
                <strong>1) Swapping nodes within the same level and limits of one parent with all its children.</strong><br>
                Choose second section:
                <select name="section2_id">
    <?php

    while ($item = $dbtree->NextRow()) {

        ?>
                    <option value="<?=$item['section_id']?>"><?=$item['section_name']?> <?php echo $item['section_id'] == (int)$_GET['section_id'] ? '<<<' : ''?></option>
        <?php

    }

    ?>
                </select><br>
                Choose position:
                <select name="position">
                    <option value="after">After</option>
                    <option value="before">Before</option>
                </select><br>
                <center><input type="submit" value="Apply"></center><br>
                </form>
                <form action="?action=move_2&section_id=<?=$_GET['section_id']?>" method="POST">
                <strong>2) Assigns a node with all its children to another parent.</strong><br>
                Choose second section:
                <select name="section2_id">
    <?php

    // Prepare the data for the second method:
    // Assigns a node with all its children to another parent
    $dbtree->Full(array('section_id', 'section_level', 'section_name'), array('or' => array('section_left <= ' . $current_section[0], 'section_right >= ' . $current_section[1])));

    // Check class errors
    if (!empty($dbtree->ERRORS_MES)) {
        echo 'DB Tree Error!';
        echo '<pre>';
        print_r($dbtree->ERRORS_MES);
        if (!empty($dbtree->ERRORS)) {
            print_r($dbtree->ERRORS);
        }
        echo '</pre>';
        exit;
    }

    while ($item = $dbtree->NextRow()) {

        ?>
                    <option value="<?=$item['section_id']?>"><?=str_repeat('&nbsp;', 6 * $item['section_level'])?><?=$item['section_name']?> <?php echo $item['section_id'] == (int)$_GET['section_id'] ? '<<<' : ''?></option>
        <?php

    }

    ?>
                </select><br>
                <center><input type="submit" value="Apply"></center><br>
                </form>
            </td>
        </tr>
    </table>
    <?php

}

/* ------------------------ DELETE ------------------------ */

// Delete node ($_GET['section_id']) from the tree wihtout deleting it's children
// All children apps to one level
if (!empty($_GET['action']) && 'delete' == $_GET['action']) {
    $dbtree->Delete((int)$_GET['section_id']);

    // Check class errors
    if (!empty($dbtree->ERRORS_MES)) {
        echo 'DB Tree Error!';
        echo '<pre>';
        print_r($dbtree->ERRORS_MES);
        if (!empty($dbtree->ERRORS)) {
            print_r($dbtree->ERRORS);
        }
        echo '</pre>';
        exit;
    }

    header('Location:' . $redirect);
    exit;
}

/* ------------------------ EDIT ------------------------ */

/* ------------------------ EDIT OK ------------------------ */

// Update node ($_GET['section_id']) info
if (!empty($_GET['action']) && 'edit_ok' == $_GET['action']) {
    $sql = 'SELECT * FROM ' . DB_TABLE . '  WHERE section_id = ' . (int)$_GET['section_id'];
    $res = $db->Execute($sql);

    // Check adodb errors
    if (FALSE === $res) {
        echo 'internal_error';
        echo '<pre>';
        print_r(array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . $db->ErrorMsg()));
        echo '</pre>';
        exit;
    }

    if (0 == $res->RecordCount()) {
        echo 'section_not_found';
        exit;
    }
    $sql = $db->GetUpdateSQL($res, $_POST['section']);
    if (!empty($sql)) {
        $res = $db->Execute($sql);

        // Check adodb errors
        if (FALSE === $res) {
            echo 'internal_error';
            echo '<pre>';
            print_r(array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . $db->ErrorMsg()));
            echo '</pre>';
            exit;
        }

    }
    header('Location:' . $redirect);
    exit;
}

/* ------------------------ EDIT FORM ------------------------ */

// Node edit form
if (!empty($_GET['action']) && 'edit' == $_GET['action']) {
    $sql = 'SELECT section_name FROM ' . DB_TABLE . ' WHERE section_id = ' . (int)$_GET['section_id'];
    $res = $db->GetOne($sql);

    // Check adodb errors
    if (FALSE === $res) {
        echo 'internal_error';
        echo '<pre>';
        print_r(array(2, 'SQL query error.', __FILE__ . '::' . __CLASS__ . '::' . __FUNCTION__ . '::' . __LINE__, 'SQL QUERY: ' . $sql, 'SQL ERROR: ' . $db->ErrorMsg()));
        echo '</pre>';
        exit;
    }

    ?>
    <table border="1" cellpadding="5" align="center">
        <tr>
            <td>
                Edit section
            </td>
        </tr>
        <tr>
            <td align="center">
                <form action="?action=edit_ok&section_id=<?=$_GET['section_id']?>" method="POST">
                Section name:<br>
                <input type="text" name="section[section_name]" value="<?=$res?>"><br><br>
                <input type="submit" name="submit" value="Submit">
                </form>
            </td>
        </tr>
    </table>
    <?php
}

/* ------------------------ ADD ------------------------ */

/* ------------------------ ADD OK ------------------------ */

// Add new node as children to selected node ($_GET['section_id'])
if (!empty($_GET['action']) && 'add_ok' == $_GET['action']) {

    // Add new node
    $dbtree->Insert((int)$_GET['section_id'], '', $_POST['section']);

    // Check class errors
    if (!empty($dbtree->ERRORS_MES)) {
        echo 'DB Tree Error!';
        echo '<pre>';
        print_r($dbtree->ERRORS_MES);
        if (!empty($dbtree->ERRORS)) {
            print_r($dbtree->ERRORS);
        }
        echo '</pre>';
        exit;
    }

    header('Location:' . $redirect);
    exit;
}

/* ------------------------ ADD FORM ------------------------ */

// Add new node form
if (!empty($_GET['action']) && 'add' == $_GET['action']) {

    ?>
    <table border="1" cellpadding="5" align="center">
        <tr>
            <td>
                New section
            </td>
        </tr>
        <tr>
            <td align="center">
                <form action="?action=add_ok&section_id=<?=$_GET['section_id']?>" method="POST">
                Section name:<br>
                <input type="text" name="section[section_name]" value=""><br><br>
                <input type="submit" name="submit" value="Submit">
                </form>
            </td>
        </tr>
    </table>
    <?php

}

/* ------------------------ LIST ------------------------ */

// Prepare data to view all tree
$dbtree->Full('');

// Check class errors
if (!empty($dbtree->ERRORS_MES)) {
    echo 'DB Tree Error!';
    echo '<pre>';
    print_r($dbtree->ERRORS_MES);
    if (!empty($dbtree->ERRORS)) {
        print_r($dbtree->ERRORS);
    }
    echo '</pre>';
    exit;
}

    ?>
    <h3>Manage tree:</h3>
    <table border="1" cellpadding="5" width="100%">
        <tr>
            <td width="100%">Section name</td>
            <td colspan="4">Actions</td>
        </tr>
    <?php

    $counter = 1;
    while ($item = $dbtree->NextRow()) {
        if ($counter % 2) {
            $bgcolor = 'lightgreen';
        } else {
            $bgcolor = 'yellow';
        }
        $counter++;

        ?>
        <tr>
            <td bgcolor="<?=$bgcolor?>">
                <?=str_repeat('&nbsp;', 6 * $item['section_level']) . '<strong>' . $item['section_name']?></strong> [<strong><?=$item['section_left']?></strong>, <strong><?=$item['section_right']?></strong>, <strong><?=$item['section_level']?></strong>]
            </td>
            <td bgcolor="<?=$bgcolor?>">
                <a href="?action=add&section_id=<?=$item['section_id']?>">Add</a>
            </td>
            <td bgcolor="<?=$bgcolor?>">
                <a href="?action=edit&section_id=<?=$item['section_id']?>">Edit</a>
            </td>
            <td bgcolor="<?=$bgcolor?>">
            
            <?php
            if (0 == $item['section_level']) {
                echo 'Delete';
            } else {

                ?>
                <a href="?action=delete&section_id=<?=$item['section_id']?>">Delete</a>
                <?php
            }
            ?>
            
            </td>
            <td bgcolor="<?=$bgcolor?>">
            
            <?php
            if (0 == $item['section_level']) {
                echo 'Move';
            } else {

                ?>
                <a href="?action=move&section_id=<?=$item['section_id']?>">Move</a>
                <?php
            }
            ?>

            </td>
        </tr>
        <?php
    }

    ?>
    </table>
</body>
</html>
<?php
ob_flush();
$db->Close();
?>