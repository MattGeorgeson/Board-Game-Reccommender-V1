<?php
//INCLUDE CLASSES USED FOR PROCESSING
require "lib/dbConfig.php";
require "lib/criteria.php";
require "lib/gameResult.php";
require "lib/gSession.php";

//BUILDS BASIIC QUERY TO RETURN GAMES
$attempt1 = $attempt2 = 'SELECT * FROM games';;

$criteriaArray = array();
$gameResults = array();

//CHECKS IF USER SUBMITTED ANY FILTERING CRITERIA
function isCriteria()
{
    return !empty($_POST['numPlayers']) or !empty($_POST['playtime']) or !empty($_POST['categories']) or !empty($_POST['mechanics']);
}

//CHECKS IF USER ENTERED ANY ADDITIONAL CRITERIA TO QUERY - USED TO BUILD QUERY
if (isCriteria()) {
    $attempt1 .= " WHERE ";
    if (!empty($_POST['numPlayers'])) {
        $players = $_POST['numPlayers'];
        $temp = "maxPlayer >=" . $players . " AND ";
        $attempt1 .= $temp;
    }
    if (!empty($_POST['playtime'])) {
        $playtime = $_POST['playtime'] * 60;
        $temp = "playtime <=" . $playtime . " AND ";
        $attempt1 .= $temp;
    }
    if (!empty($_POST['mechanics'])) {
        $mechanics = $_POST['mechanics'];
        $temp = "";
        foreach ($mechanics as $m) {

            $temp .= "mechanics LIKE '%" . $m . "%' AND ";
        }
        $attempt1 .= $temp;;
    }
    if (!empty($_POST['categories'])) {
        $categories = $_POST['categories'];
        $temp = "";
        foreach ($categories as $c) {
                $temp .= "categories LIKE '%" . $c . "%' AND ";
        }
        $attempt1 .= $temp;
    }
    $attempt1 = substr($attempt1, 0, -4);
    $attempt1 .= "";
}

$gamesA1 = dbQuery($attempt1);

//RUNS WHEN FIRST QUERY DOESN'T RETURN GAMES
if ($gamesA1->num_rows == 0) {
    //REBUILDS QUERY TO USE 'OR' OPERATORS RATHER THAN 'AND' OPERATORS IN QUERY
    $attempt2 .= " WHERE ";
    if (!empty($_POST['numPlayers'])) {
        $players = $_POST['numPlayers'];
        $temp = "maxPlayer >=" . $players . " AND ";
        $attempt2 .= $temp;
        //ADDS CRITERIA TO A CRITERIA ARRAY TO CHECK NEW GAMES AGAINST CRITERIA TO CALCULATE ACCURACY
        array_push($criteriaArray, new Criteria("NUMPLAYERS", $_POST['numPlayers']));
    }
    if (!empty($_POST['playtime'])) {
        $playtime = $_POST['playtime'] * 60;
        $temp = "playtime <=" . $playtime . " AND (";
        $attempt2 .= $temp;
        //ADDS CRITERIA TO A CRITERIA ARRAY TO CHECK NEW GAMES AGAINST CRITERIA TO CALCULATE ACCURACY
        array_push($criteriaArray, new Criteria("PLAYTIME", $_POST['playtime']));
    }
    if (!empty($_POST['mechanics'])) {
        $mechanics = $_POST['mechanics'];
        $temp = "";
        foreach ($mechanics as $m) {
            $temp .= "mechanics LIKE '%" . $m . "%' OR ";
            //ADDS CRITERIA TO A CRITERIA ARRAY TO CHECK NEW GAMES AGAINST CRITERIA TO CALCULATE ACCURACY
            array_push($criteriaArray, new Criteria("MECHANIC", $m));
        }
        $attempt2 .= $temp;
    }
    if (!empty($_POST['categories'])) {
        $categories = $_POST['categories'];
        $temp = "";
        foreach ($categories as $c) {
            if ($categories[count($categories) - 1] == $c) {
                $temp .= "categories LIKE '%" . $c . "%' OR ";
                //ADDS CRITERIA TO A CRITERIA ARRAY TO CHECK NEW GAMES AGAINST CRITERIA TO CALCULATE ACCURACY
                array_push($criteriaArray, new Criteria("CATEGORY", $c));
            }
        }
        $attempt2 .= $temp;
    }
    $attempt2 = substr($attempt2, 0, -3);
    $attempt2 .= ")";

    //RUN NEW QUERY
    $gamesA2 = dbQuery($attempt2);

    $gameArray = array();
    if ($gamesA1->num_rows == 0) {
        $gameArray = $gamesA2;
    } else {
        $gameArray = $gamesA1;
    }

    //LOOP TO CHECK CRITERIA ACCURACY OF EACH GAME
    foreach ($gameArray as $g) {
        $gameRecAccuracy = 0;
        $tempMatches = array();
        //ITERATES THROUGH ARRAY OF CRITERIA TO CHECK AGAINST EACH GAME RETURNED FROM SECOND QUERY
        foreach ($criteriaArray as $c) {
            switch ($c->getCType()) {
                case "NUMPLAYERS":
                    if ($c->getCData() <= $g["maxPlayer"]) {
                        $gameRecAccuracy++;
                        array_push($tempMatches, $c);
                    }
                    break;
                case "PLAYTIME":
                    if ($c->getCData() == $g["playtime"]) {
                        $gameRecAccuracy++;
                        array_push($tempMatches, $c);
                    }
                    break;
                case "MECHANIC":
                    if (str_contains($g["mechanics"], $c->getCData())) {
                        $gameRecAccuracy++;
                        array_push($tempMatches, $c);
                    }
                    break;
                case "CATEGORY":
                    if (str_contains($g["categories"], $c->getCData())) {
                        $gameRecAccuracy++;
                        array_push($tempMatches, $c);
                    }
                    break;
            }
        }
        //ONLY RECOMMEND GAMES TO USERS WHICH HAVE AN ACCURACY RATING MORE THAN 0%
        if ($gameRecAccuracy > 0) {
            array_push($gameResults, new Recommendation($g, ($gameRecAccuracy / sizeof($criteriaArray) * 100), $tempMatches));
        }
    }

    $gOrderedTime = array();
    $gOrderedTime = $gameResults;

    //ORDERED RECOMMENDED GAMES BY PLAYTIME
    usort($gOrderedTime, function ($a, $b) {
        return $a->getPlaytime() < $b->getPlaytime();
    });

    $gameSessions = array();

    //LOOP FOR CREATING GAME SESSIONS
    foreach ($gOrderedTime as $g1) {
        $tempArr = array();
        $gameTime = $_POST['playtime'] * 60;
        $score = 0;

        //ADDS CURRENT GAME IN ITERATION TO SESSION IF CAN FIT IN TIMEFRAME
        if ($g1->getPlaytime() <= $gameTime) {
            array_push($tempArr, $g1);
            $gameTime = $gameTime - $g1->getPlaytime();
            $score += $g1->getGame()['rating'];
        }

        //ITERATES THROUGH EVERY OTHER GAME IN LIST OF RETURNED GAMES
        foreach ($gOrderedTime as $g2) {    
            if (!($g1 === $g2)) {       //ENSURES THAT NO DUPLICATE GAMES ARE ADDED TO SESSION
                if ($g2->getPlaytime() <= $gameTime) {      //CHECKS CURRENT GAME FITS IN TIMESLOT
                    array_push($tempArr, $g2);      //ADDS GAME TO SESSION
                    $gameTime = $gameTime - $g2->getPlaytime();     //UPDATES TIME REMAINING
                    $score += $g2->getGame()['rating'];     //UPDATES TOTAL RATTING
                }
            }
        }

        //ORDERS GAMES IN SESSION BY APLABETICAL ORDER
        usort($tempArr, function ($a, $b) {
            return strcmp($a->getGame()['gameName'], $b->getGame()['gameName']);
        });

        //CREATES GAME SESSION OBJECT BASED ON DATA ABOVE
        if (sizeof($tempArr) > 1) { //WILL ONLY ADD SESSION IF CONTAINS MORE THAN 1 GAME
            $newSess = new gSession($tempArr, $gameTime, $score / sizeof($tempArr));
            if (sizeof($gameSessions) == 0) {   //ADDS FIRST SESSION TO FIRST PLACE IN ARRAY
                array_push($gameSessions, $newSess);
            } else if ($gameSessions[sizeof($gameSessions) - 1] <=> $newSess) { //CHECKS THAT GAME SESSION IN MOST LAST ARRAY IS NOT IDENTICAL FROM CURRENT SESSSION
                array_push($gameSessions, $newSess);
            }
        }
    }
} 
// RUNS WHEN FIRST QUERY RETURNS GAMES
else {
    $gOrderedTime = array();

    //ADDS EACH GAME RETURNED FROM QUERY INTO NEW ARRAY TO BE ORDERED BY TIME
    foreach ($gamesA1 as $g) {
        array_push($gOrderedTime, $g);
    }

    //SORT FUNCTION TO ARRANGE GAMES BY LONGEST PLAYTIME
    usort($gOrderedTime, function ($a, $b) {
        return $a['playtime'] < $b['playtime'];
    });

    //INIALISES ARRAY TO CONTAIN SESSIONS
    $gameSessions = array();

    //LOOP FOR CREATING GAME SESSIONS
    foreach ($gOrderedTime as $g1) {
        $tempArr = array();
        $gameTime = $_POST['playtime'] * 60;
        $score = 0;

        //ADDS CURRENT GAME IN ITERATION TO SESSION IF CAN FIT IN TIMEFRAME
        if ($g1['playtime'] <= $gameTime) {
            array_push($tempArr, $g1);
            $gameTime = $gameTime - $g1['playtime'];
            $score += $g1['rating'];
        }

        //ITERATES THROUGH EVERY OTHER GAME IN LIST OF RETURNED GAMES
        foreach ($gOrderedTime as $g2) {
            if (!($g1 === $g2)) {   //ENSURES THAT NO DUPLICATE GAMES ARE ADDED TO SESSION
                if ($g2['playtime'] <= $gameTime) {     //CHECKS CURRENT GAME FITS IN TIMESLOT
                    array_push($tempArr, $g2);      //ADDS GAME TO SESSION
                    $gameTime = $gameTime - $g2['playtime'];    //UPDATES TIME REMAINING
                    $score += $g2['rating'];    //UPDATES TOTAL RATTING
                }
            }
        }

        //SORTS GAMES IN SESSION ALPHABETICALLY
        usort($tempArr, function ($a, $b) {
            return strcmp($a['gameName'], $b['gameName']);
        });

        //CREATES GAME SESSION OBJECT BASED ON DATA ABOVE
        if (sizeof($tempArr) > 1) {     //WILL ONLY ADD SESSION IF CONTAINS MORE THAN 1 GAME
            $newSess = new gSession($tempArr, $gameTime, $score / sizeof($tempArr));
            if (sizeof($gameSessions) == 0) {   //ADDS FIRST SESSION TO FIRST PLACE IN ARRAY
                array_push($gameSessions, $newSess);
            } else if ($gameSessions[sizeof($gameSessions) - 1] <=> $newSess) {     //CHECKS THAT GAME SESSION IN MOST LAST ARRAY IS NOT IDENTICAL FROM CURRENT SESSSION
                array_push($gameSessions, $newSess);
            }
        }
    }
}

//SORT SESSIONS BY HIGHEST SCORE
usort($gameSessions, function ($a, $b) {
    return $a->getScore() < $b->getScore();
});

?>
<!DOCTYPE html>
<html>
<head>
    <style>
        * {
            box-sizing: border-box;
        }

        .row {
            display: flex;
        }
        .column {
            flex: 50%;
            padding: 10px;
            height: 300px;
        }
    </style>
</head>
<body>
    <a href="home.php">Return to home</a>
    <p>Search query: "<?php echo $attempt1 ?>"</p>

    <?php
    if (sizeof($criteriaArray) > 0) {
        echo "<br> Numer of Criteria: " . sizeof($criteriaArray) . "<br>";}
    ?>
    <div class="row">
        <div class="column">
            <?php
            echo "Games found matching ALL criteria: " . $gamesA1->num_rows . "<br><br>";
            if ($gamesA1->num_rows > 0) {
                foreach ($gamesA1 as $g) {
                    echo "<hr style=\"width:50%;text-align:left;margin-left:0\">
                    <br><h2>" . $g["gameName"] . "</h2>
                    <p>" . $g["gameDesc"] . "</p>
                    <p>Min Players: " . $g["minPlayer"] . "</p>
                    <p>Max Players: " . $g["maxPlayer"] . "</p>
                    <p>Minimum Age: " . $g["minAge"] . "</p>
                    <p>Length: " . $g["playtime"] . " minutes</p>
                    <p>Rating: " . $g["rating"] . "/10</p>
                    <p>Categories: " . $g["categories"] . "</p>
                    <p>Mechanics: " . $g["mechanics"] . "</p><br>";
                }
            } else {
                echo "Games could not be found that match ALL criteria. Here are games that may match SOME of your search criteria<br>Games found: " . sizeof($gameResults) . "<br>";
            
            foreach ($gameResults as $gr) {
                echo "<hr style=\"width:50%;text-align:left;margin-left:0\">
                    <br><h2>" . $gr->getGame()["gameName"] . "</h2>
                    <h4>Match Accuracy:" . $gr->getAccuracy() . "%</h4>
                    <p>" . $gr->getGame()["gameDesc"] . "</p>
                    <p>Min Players: " . $gr->getGame()["minPlayer"] . "</p>
                    <p>Max Players: " . $gr->getGame()["maxPlayer"] . "</p>
                    <p>Minimum Age: " . $gr->getGame()["minAge"] . "</p>
                    <p>Length: " . $gr->getGame()["playtime"] . " minutes</p>
                    <p>Rating: " . $gr->getGame()["rating"] . "/10</p>
                    <p>Categories: " . $gr->getGame()["categories"] . "</p>
                    <p>Mechanics: " . $gr->getGame()["mechanics"] . "</p>
                    <br>Matched on:<br>";
                foreach ($gr->getCriteriaMatch() as $cm) {
                    echo $cm->getCType() . ": " . $cm->getCData() . "<br>";
                }
                echo "<br><br><br>";
            }
        }
            ?>
        </div>
        <div class="column">
            <?php echo "<hr style=\"width:50%;text-align:left;margin-left:0\"><h1>GAME SESSIONS</h1>";

            echo "Sessions made: " . sizeof($gameSessions);
            $x = 1;
            foreach ($gameSessions as $gs) {
                echo "<hr style=\"width:25%;text-align:left;margin-left:0\"><br>";
                echo "Session " . $x . "<br>";
                echo "Number of Games in session: " . sizeof($gs->getGames()) . "<br>";
                echo "AVG game score of session: " . $gs->getScore() . "<br>";
                echo "Length of session: " . $_POST['playtime'] * 60 - $gs->getTimeRemaining() . " minutes <br> Games in session: <br>";

                $games = $gs->getGames();

                foreach ($games as $g) {
                    if ($gamesA1->num_rows == 0) {
                        echo "---" . $g->getGame()['gameName'] . "<br>";
                    } else {
                        echo "---" . $g['gameName'] . "<br>";
                    }
                }
                echo "<br>";
                $x++;
            }
            ?>
        </div>
    </div>



</body>
</html>