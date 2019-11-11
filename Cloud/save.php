<?php
/**
 * Created by PhpStorm.
 * User: User
 * Date: 2018/11/28
 * Time: 16:19
 */
require_once "db.inc.php";
echo '<?xml version="1.0" encoding="UTF-8" ?>';
// Process in a function
process($_GET['user'], $_GET['pw'],$_GET['board'],$_GET['endgame']);


/**
 * Process the query
 * @param $user the user to look for
 * @param $password the user password
 */
function process($user,$password,$board,$status) {
    $pdo = pdo_connect();

    $userid = getUser($pdo, $user, $password);
    $query = "select gameid, time_player1, time_player2, player1id, player2id, game_field, game_state from connect4game where $userid IN(player1id, player2id)";
    $rows = $pdo->query($query);
    //should only return one row
    if($row = $rows->fetch()) {
        $gameid = $row['gameid'];
        $query = <<<QUERY
UPDATE connect4game
SET game_field = '$board', game_state=$status
WHERE gameid = $gameid
QUERY;
        if(!$pdo->query($query)) {
            echo '<game status="no" msg="insertfail">' . $query . '</game>';
            exit;
        }
        if ($row['player1id'] == $userid) { // if its player 1 saved
            $query = <<<QUERY
UPDATE connect4game
SET current_turn = 2
WHERE gameid = $gameid
QUERY;
            if (!$pdo->query($query)) {
                echo '<game status="no" msg="updatecurrenturnfail">' . $query . '</game>';
                exit;
            }
        }
        elseif ($row['player2id'] == $userid){
            $query = <<<QUERY
UPDATE connect4game
SET current_turn = 1
WHERE gameid = $gameid
QUERY;
            if (!$pdo->query($query)) {
                echo '<game status="no" msg="updatecurrentturnfail">' . $query . '</game>';
                exit;
            }
        }

            echo '<game status="yes"/>';
        exit;

    }
    echo '<game save="no" msg="no result found??" />';
}//end of process xml
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