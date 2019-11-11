<?php
require_once "db.inc.php";
echo '<?xml version="1.0" encoding="UTF-8" ?>';
// Process in a function
process($_GET['user'], $_GET['pw']);

/**
 * Process the query
 * @param $user the user to look for
 * @param $password the user password
 */
function process($user,$password) {

    $currdate = date('Y-m-d H:i:s'); //date('m/d/Y h:i:s a', time());
    $pdo = pdo_connect();

    $userid = getUser($pdo, $user, $password);
    $useridQ=$pdo->quote($userid);
    $query = "select gameid, player1id, player2id, game_state,time_player1,time_player2 from connect4game where $userid IN(player1id, player2id)";
    // 1 (player 1 won), 2 (player 2 won), 3 (it was a tie), 0 (game is still going), -1 (player 1 disconnected), and -2 (player 2 disconnected), -3(waiting)
    $rows = $pdo->query($query);
    //if accidenntally quit ( join back)
    if($row = $rows->fetch()) {
        $status = $row['game_state'];
        $gameid = $row['gameid'];
        if($row['player1id']==$userid){
            $query = <<<QUERY
UPDATE connect4game
SET time_player1 = '$currdate'
WHERE gameid = $gameid
QUERY;
            if (!$pdo->query($query)) {
                echo '<game status="no" msg="updatefail">' . $query . '</game>';
                exit;
            }
            if($status ==0){
                $opponent = $row['player2id'];
                $opponame =getName($pdo, $opponent);
                echo "<game status=\"connected\">";
                echo "<tag username='$user' playerid='1' oppoid=\"$opponent\" opponame=\"$opponame\"/>";
                echo "</game>";
            }
            elseif ($status ==-3){
                echo "<game status=\"waiting\">";
                echo "<tag username='$user' playerid ='1' oppoid='' opponame=''/>"; // this means whhether its player 1 or player 2
                echo "</game>";
            }

        }
        elseif($row['player2id']==$userid){
            $query = <<<QUERY
UPDATE connect4game
SET time_player2 = '$currdate'
WHERE gameid = $gameid
QUERY;
            if (!$pdo->query($query)) {
                echo '<game status="no" msg="updatefail">' . $query . '</game>';
                exit;
            }
            if($status ==0){
                $opponent = $row['player1id'];
                $opponame =getName($pdo, $opponent);
                echo "<game status=\"connected\">";
                echo "<tag username='$user' playerid='2' oppoid=\"$opponent\" opponame=\"$opponame\"/>";
                echo "</game>";
            }
            }
    }
    else{ // no existing enrolled game join a new game / create new one
        $query = "select gameid, player1id from connect4game where player2id IS NULL AND game_state = -3";
        // 1 (player 1 won), 2 (player 2 won), 3 (it was a tie), 0 (game is still going), -1 (player 1 disconnected), and -2 (player 2 disconnected), -3(waiting)
        $rows = $pdo->query($query);
        // var_dump($rows->fetch());
        if($row = $rows->fetch()) { // two situation: one : player1 is waiting ( first one will always be player1); the game board has already have two players);
            //insert into an exisiting game table
            $gameid = $row['gameid'];
            $opponent = $row['player1id'];
            // var_dump($opponent);
            if($opponent != $userid) { //make sure player1 and player 2 won't be the same person( like refreshed again)

                $query = <<<QUERY
UPDATE connect4game
SET player2id = $userid, time_player2 = '$currdate', game_state =0
WHERE gameid = $gameid
QUERY;
                if (!$pdo->query($query)) {
                    echo '<game status="no" msg="insertfail">' . $query . '</game>';
                    exit;
                }
                $opponame =getName($pdo, $opponent);
                echo "<game status=\"connected\">";
                echo "<tag username='$user' playerid='2' oppoid=\"$opponent\" opponame=\"$opponame\"/>";
                echo "</game>";
            }
        }
        else {
            // no more waiting games, create a new one

            //cant use replace .. seems that it will turned to be errors
            // just gonna make a new game i think :| know how to fix that but doesn't wanna take that much effort
            // gonna do that if having spare time
            $query = <<<QUERY
INSERT INTO connect4game(player1id, time_player1, game_field, game_state)
VALUES('$userid', '$currdate','',-3)
QUERY;

            if(!$pdo->query($query)) {
                echo '<game status="no" msg="insertfail">' . $query . '</game>';
                exit;
            }
            echo "<game status=\"waiting\">";
            echo "<tag username='$user' playerid ='1' oppoid='' opponame=''/>"; // this means whhether its player 1 or player 2
            echo "</game>";
        }

    }

}
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
