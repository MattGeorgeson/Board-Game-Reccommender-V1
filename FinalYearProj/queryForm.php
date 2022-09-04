<?php
include "lib/dbConfig.php";

#contains query results for form data
$catResult  = dbQuery("SELECT * FROM categories");
$mechResult = dbQuery("SELECT * FROM mechanics");


$recommendGame = "SELECT * FROM games WHERE ";
?>
<!DOCTYPE html>
<html>
<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta content="utf-8" http-equiv="encoding">

    <style>
        * {
            box-sizing: border-box;
        }

        .row {
            display: flex;
        }

        /* Create two equal columns that sits next to each other */
        .column {
            flex: 50%;
            padding: 10px;
            height: 300px;
            /* Should be removed. Only for demonstration */
        }
    </style>

</head>
<body style="align-content: center;">
    <a href="home.php">Return to home</a>
    <form method="post" style="padding-left: 75px; padding-right: 75px;" action="results.php">
        <h1 style="text-align: center;">Number of Players</h1>
        <div>
            <input type="number" value=2 name="numPlayers" id="numPlayers" min=1 max=255>
        </div>
        <h1 style="text-align:center;padding-top: 50px;">Session Length (Hours)</h1>
        <div>
            <input type="number" value=2 name="playtime" id="playtime" min=1 max=24>
        </div>
        <h1 style="text-align:center;padding-top: 50px;">Themes/Categories</h1>
        <div>
            <?php
            $colCount = 0;
            if ($catResult->num_rows > 0) {
                #Iterate throguh categories
                while ($row = $catResult->fetch_assoc()) {
                    echo '<input type="checkbox" name="categories[]" id="cat' . $row["catId"] . '" value="' . $row["catName"] . '"> <label for="cat' . $row["catId"] . '" style="margin: 0px 30px 0px 5px">' . $row["catName"] . '</label>';
                    $colCount += 1;
                    if ($colCount == 5) {
                        echo "<br>";
                        $colCount = 0;
                    }
                }
            } else {
                echo "No Game Categories Found";
            }
            ?>
        </div>
        <h1 style="text-align:center;padding-top: 50px;">Mechanics</h1>
        <div>
            <?php
            $colCount = 0;
            if ($mechResult->num_rows > 0) {
                #Iterate through mechanics
                while ($row = $mechResult->fetch_assoc()) {
                    echo '<input type="checkbox" name="mechanics[]" id="mech' . $row["mechId"] . '" value="' . $row["mechName"] . '"> <label for="mech' . $row["mechId"] . '" style="margin: 0px 30px 0px 5px">' . $row["mechName"] . '</label>';
                    $colCount += 1;
                    if ($colCount == 6) {
                        echo "<br>";
                        $colCount = 0;
                    }
                }
            } else {
                echo "No Game Mechanics Found";
            }
            ?>
        </div>
        <div style="text-align:center;padding-top: 50px;">
            <input type="submit" value="submit">
        </div>
    </form>
</body>

</html>