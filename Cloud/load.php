<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2018/11/13
 * Time: 19:13
 */

require_once "db.inc.php";
echo '<?xml version="1.0" encoding="UTF-8" ?>';
process($_GET['user'], $_GET['pw']);
/*
 * load board and status to client
 */


/**
 * Process the query
 */
function process($user,$pw) {
    // Connect to the database
    $pdo = pdo_connect();
    $userid = getUser($pdo, $user, $pw);
    $useridQ = $pdo->quote($userid);
    $query = "select gameid, time_player1, time_player2, player1id, player2id,game_field, game_state from connect4game where $useridQ IN(player1id, player2id)";

    $rows = $pdo->query($query);

    if($row = $rows->fetch()) {

        $status = $row['game_state'];
        $board = $row['game_field'];
        echo "<game status=\"yes\" endgame='$status'>";
        echo "<tag board='$board'/>";
        echo "</game>";
    }
    else{
        echo "<game status=\"no\" msg='no database found for load'>";
        echo "</game>";
        exit;
    }

}


// bottom is added functions
/**
 * Ask the database for the user ID. If the user exists, the password
 * must match.
 * @param $pdo PHP Data Object
 * @param $user The user name
 * @param $password Password
 * @return id if successful or exits if not
 */
function getUser($pdo, $user, $password) {
    // Does the user exist in the database?
    $userQ = $pdo->quote($user);
    $query = "SELECT id, password from connect4user where user=$userQ";

    $rows = $pdo->query($query);
    if($row = $rows->fetch()) {
        // We found the record in the database
        // Check the password
        if($row['password'] != $password) {
            echo '<hatter status="no" msg="password error" />';
            exit;
        }

        return $row['id'];
    }

    echo '<hatter status="no" msg="user error" />';
    exit;
}

function getName($pdo, $userid) {
    // Does the user exist in the database?
    $useridQ = $pdo->quote($userid);
    $query = "SELECT user from connect4user where id = $useridQ";

    $rows = $pdo->query($query);
    if($row = $rows->fetch()) {
        // We found the record in the database
        return $row['user'];
    }

    echo '<hatter status="no" msg="no username found error" />';
    exit;
}
