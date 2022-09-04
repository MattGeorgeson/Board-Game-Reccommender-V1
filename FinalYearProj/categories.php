<?php
include "lib/dbConfig.php";
$categories  = dbQuery("SELECT * FROM categories");
?>
<html>
<head>
</head>
<body>
    <a href="home.php">Return to home</a>
    <h1>Categories/Themes</h1>
    <?php
    foreach ($categories as $c) {
        echo $c["catName"] . "<br>" . $c["catDesc"] . "<br><br>";
    }
    ?>
</body>
</html>