<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2018/11/13
 * Time: 19:13
 */
/*
 * Create user
 */
require_once "db.inc.php";
echo '<?xml version="1.0" encoding="UTF-8" ?>';
// Process in a function
process($_GET['user'], $_GET['pw']);

/**
 * Process the query
 * @param $user the user to look for
 * @param $password the user password
 */
function process($user, $password) {
    // Connect to the database
    $pdo = pdo_connect();

    $query = <<<QUERY
REPLACE INTO connect4user(user, password)
VALUES('$user', '$password')
QUERY;

    if(!$pdo->query($query)) {
        echo '<hatter status="no" msg="insertfail">' . $query . '</hatter>';
        exit;
    }

    echo "<hatter status=\"yes\">";
    echo "<tag user=\"$user\" pw=\"$password\" />";
    echo "</hatter>";
}
