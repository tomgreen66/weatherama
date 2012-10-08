<?php
/**
* $Id: dbtree_visual_demo.php,v 2.0 2005/09/09 19:32:45 Kuzma Exp $
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

ob_start();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>DB Tree - Demo</title>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
<meta name="author" content="Kuzma Feskov (kuzma@russofile.ru)">
</head>
<body>
<h2>TB Tree class demo by Kuzma Feskov</h2>
[<a href="dbtree_demo.php">Manage demo</a>] [<a href="dbtree_visual_demo.php?mode=map">Visual demo (Map)</a>] [<a href="dbtree_visual_demo.php?mode=ajar">Visual demo (Ajar)</a>] [<a href="dbtree_visual_demo.php?mode=branch">Visual demo (Branch)</a>]
<?php

// DB CLASS: adodb or db_mysql
define('DB_CLASS', 'db_mysql');

/** DB Host */
define('DB_HOST', 'localhost');

/** DB user name */
$DB_USER = 'tester';

/** DB user password */
$DB_PASSWORD = 'tester';

/** DB name */
define('DB_BASE_NAME', 'test');

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
$dbtree = new dbtree('test_sections', 'section', $db);

/* ------------------------ NAVIGATOR ------------------------ */
$navigator = 'You are here: ';
if (!empty($_GET['section_id'])) {
    $dbtree->Parents((int)$_GET['section_id'], array('section_id', 'section_name'));

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
        if (@$_GET['section_id'] <> $item['section_id']) {
            $navigator .= '<a href="dbtree_visual_demo.php?mode=' . $_GET['mode'] . '&section_id=' . $item['section_id'] . '">' . $item['section_name'] . '</a> > ';
        } else {
            $navigator .= '<strong>' . $item['section_name'] . '</strong>';
        }
    }
}

/* ------------------------ BRANCH ------------------------ */
if (!empty($_GET['mode']) && 'branch' == $_GET['mode']) {

    if (!isset($_GET['section_id'])) {
        $_GET['section_id'] = 1;
    }
    
    // Prepare data to view ajar tree
    $dbtree->Branch((int)$_GET['section_id'], array('section_id', 'section_level', 'section_name'));

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
    <h3>Manage tree (BRANCH):</h3>
    <table border="1" cellpadding="5" width="100%">
        <tr>
            <td>

        <?php
        echo $navigator . '<br><br>';
        while ($item = $dbtree->NextRow()) {
            if (@$_GET['section_id'] <> $item['section_id']) {
                echo str_repeat('&nbsp;', 6 * $item['section_level']) . '<a href="dbtree_visual_demo.php?mode=branch&section_id=' . $item['section_id'] . '">' . $item['section_name'] . '</a><br>';
            } else {
                echo str_repeat('&nbsp;', 6 * $item['section_level']) . '<strong>' . $item['section_name'] . '</strong><br>';
            }
        }

        ?>
            </td>
        </tr>
    </table>
    
    <?php
}

/* ------------------------ AJAR ------------------------ */
if (!empty($_GET['mode']) && 'ajar' == $_GET['mode']) {

    if (!isset($_GET['section_id'])) {
        $_GET['section_id'] = 1;
    }
    
    // Prepare data to view ajar tree
    $dbtree->Ajar((int)$_GET['section_id'], array('section_id', 'section_level', 'section_name'));

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
    <h3>Manage tree (AJAR):</h3>
    <table border="1" cellpadding="5" width="100%">
        <tr>
            <td>

        <?php
        echo $navigator . '<br><br>';
        while ($item = $dbtree->NextRow()) {
            if (@$_GET['section_id'] <> $item['section_id']) {
                echo str_repeat('&nbsp;', 6 * $item['section_level']) . '<a href="dbtree_visual_demo.php?mode=ajar&section_id=' . $item['section_id'] . '">' . $item['section_name'] . '</a><br>';
            } else {
                echo str_repeat('&nbsp;', 6 * $item['section_level']) . '<strong>' . $item['section_name'] . '</strong><br>';
            }
        }

        ?>
            </td>
        </tr>
    </table>
    
    <?php
}

/* ------------------------ MAP ------------------------ */
if (!empty($_GET['mode']) && 'map' == $_GET['mode']) {

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
    <h3>Manage tree (MAP):</h3>
    <table border="1" cellpadding="5" width="100%">
        <tr>
            <td>

        <?php
        echo $navigator . '<br><br>';
        while ($item = $dbtree->NextRow()) {
            if (@$_GET['section_id'] <> $item['section_id']) {
                echo str_repeat('&nbsp;', 6 * $item['section_level']) . '<a href="dbtree_visual_demo.php?mode=map&section_id=' . $item['section_id'] . '">' . $item['section_name'] . '</a><br>';
            } else {
                echo str_repeat('&nbsp;', 6 * $item['section_level']) . '<strong>' . $item['section_name'] . '</strong><br>';
            }
        }

        ?>
            </td>
        </tr>
    </table>
    
    <?php
}

    ?>
</body>
</html>
<?php
ob_flush();
$db->Close();
?>