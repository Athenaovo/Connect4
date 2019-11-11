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
    $userQ = $pdo->quote($user);// need to quote user
    $query = "select id, user, password from connect4user where user=$userQ";
    $rows = $pdo->query($query);
    //printf($query);

    if($row = $rows->fetch()) {
        // We found the record in the database
        // Check the password
        if($row['password'] != $password) {
            echo '<hatter status="no" msg="passworderror" />';
            exit;
        }
        $userid = $row['id'];
        checkgames($pdo, $userid);

        echo "<hatter status=\"yes\">"; //dont want to change that "hatter" to something else since it doesnt matter too much
        echo "<tag user=\"$user\" pw=\"$password\" />";
        echo "</hatter>";
        exit;
    }
    echo '<tag status="no" msg="user error" />';
    exit;
}
/**
 * Ask the database for the user ID. If the user exists, the password
 * must match.
 * @param $pdo PHP Data Object
 * @param $user The user name
 * @param $password Password
 * @return id if successful or exits if not
 */
function checkgames($pdo, $userid) {
    // Does the user exist in the database?
    $useridQ = $pdo->quote($userid);
    $query = "SELECT gameid from connect4game where $useridQ IN(player1id, player2id) AND game_state IN (-2,-1,1,2,3)";
    $rows = $pdo->query($query);
    if($row = $rows->fetch()) {
        $gameid = $row['gameid'];
        //var_dump($gameid);
        $query = <<<QUERY
DELETE FROM connect4game
WHERE gameid = '$gameid'
QUERY;
        if (!$pdo->query($query)) {
            echo '<game status="no" msg="deletefail">' . $query . '</game>';
            exit;
        }
     //   echo '<hatter status="yes" msg="duplicate game found and deleted " />';

    }
    else{
     //   echo '<hatter status="no" msg="duplicate game found " />';
    }


}
