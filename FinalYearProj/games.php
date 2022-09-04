<?php
include "lib/dbConfig.php";
$games  = dbQuery("SELECT * FROM games");
?>
<html>
<head>
</head>
<body>
    <a href="home.php">Return to home</a>
    <h1>Games</h1>
    <?php
    foreach ($games as $g) {
        echo $g["gameName"] . "<br>" . $g["gameDesc"] . "<br><br>";
    }
    ?>
</body>
</html>