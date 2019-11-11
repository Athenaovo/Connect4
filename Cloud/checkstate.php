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
    $useridQ = $pdo->quote($userid);

    $query = "select gameid, time_player1, time_player2, player1id, player2id, game_state, current_turn from connect4game where $userid IN(player1id, player2id)";
    $rows = $pdo->query($query);
    //should only return one row
    if($row = $rows->fetch()) {
        //var_dump("im in ");
        $status = $row['game_state'];
        $gameid = $row['gameid'];
        $current=$row['current_turn'];
        if ($row['player1id'] == $userid) { // if its player 1 saved and server checking state rn
            $opponent = $row['player2id'];
            $opponame = getName($pdo, $opponent);

            $query = <<<QUERY
UPDATE connect4game
SET time_player1 = '$currdate'
WHERE gameid = $gameid
QUERY;
            if (!$pdo->query($query)) {
                echo '<game status="no" msg="updateusertimefail">' . $query . '</game>';
                exit;
            }
            if ($status == 0) {
                $opponenttime = strtotime($row['time_player2']);
                $differtime = strtotime($currdate)-$opponenttime;

                if($differtime>60){ // more than two minutes
                    $query = <<<QUERY
UPDATE connect4game
SET game_state = -2
WHERE gameid = $gameid
QUERY;
                    if (!$pdo->query($query)) {
                        echo '<game status="no" msg="updatedisconnect2fail">' . $query . '</game>';
                        exit;
                    }
                    echo "<game status=\"disconnected2\">"; // player 2 is disconnected
                    echo "<tag username='$user' playerid='1' oppoid=\"$opponent\" opponame=\"$opponame\" endgame='-2' current ='$current'/>";
                    echo "</game>";
                } // end of more than 120
                else{ //player2 doesnt disconnect; keep playing game

                    echo "<game status=\"connected\">";
                    echo "<tag username='$user' playerid='1' oppoid=\"$opponent\" opponame=\"$opponame\" endgame='0' current ='$current'/>";
                    echo "</game>";
                }
            }
            else{ //other state: 1, 2, 3 (which means game has already finished , just print that
                echo "<game status=\"result\">";
                echo "<tag username='$user' playerid='1' oppoid=\"$opponent\" opponame=\"$opponame\" endgame='$status' current ='$current'/>";
                echo "</game>";
            }
        } // end of payer1 saved
        elseif ($row['player2id'] == $userid){ //if its player 2 doing this
            $opponent = $row['player1id'];
            $opponame = getName($pdo, $opponent);

            $query = <<<QUERY
UPDATE connect4game
SET time_player2 = '$currdate'
WHERE gameid = $gameid
QUERY;
            //select gameid, time_player1, time_player2, player1id. playerr2id, game_state
            if (!$pdo->query($query)) {
                echo '<game status="no" msg="updateusertimefail">' . $query . '</game>';
                exit;
            }
            if ($status == 0) {
                $opponenttime = strtotime($row['time_player1']);
                $differtime = strtotime($currdate)-$opponenttime;

                if($differtime>60){ // more than two minutes, player 1 is disconnected
                    $query = <<<QUERY
UPDATE connect4game
SET game_state = -1
WHERE gameid = $gameid
QUERY;
                    if (!$pdo->query($query)) {
                        echo '<game status="no" msg="updatedisconnect2fail">' . $query . '</game>';
                        exit;
                    }
                    echo "<game status=\"disconnected1\">"; // player 2 is disconnected
                    echo "<tag username='$user' playerid='2' oppoid=\"$opponent\" opponame=\"$opponame\" endgame='-1' current ='$current'/>";
                    echo "</game>";
                } // end of more than 120
                else{ //player2 doesnt disconnect; keep playing game

                    echo "<game status=\"connected\">";
                    echo "<tag username='$user' playerid='2' oppoid=\"$opponent\" opponame=\"$opponame\" endgame='0' current ='$current'/>";
                    echo "</game>";
                }
            }
            else{ //other state: 1, 2, 3 (which means game has already finished , just print that
                echo "<game status=\"result\">";
                echo "<tag username='$user' playerid='2' oppoid=\"$opponent\" opponame=\"$opponame\" endgame='$status' current ='$current'/>";
                echo "</game>";
            }
        }//end of player 2 saved
        else{
            echo "<game status=\"no\" msg='no database found when checkstate'>";
            echo "</game>";
            exit;
        }

    }

}//end of function process


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
