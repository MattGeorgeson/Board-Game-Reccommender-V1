<?php
include "lib/dbConfig.php";
$mechanics  = dbQuery("SELECT * FROM mechanics");
?>
<html>
<head>
</head>
<body>
    <a href="home.php">Return to home</a>
    <h1>Game Mechanics</h1>
    <?php
    foreach ($mechanics as $m) {
        echo $m["mechName"] . "<br>" . $m["mechDesc"] . "<br><br>";
    }
    ?>
</body>
</html>