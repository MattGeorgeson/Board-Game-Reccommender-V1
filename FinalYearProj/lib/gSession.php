<?php 
class gSession{
    private $games;
    private $timeRemaining;
    private $avgScore;

    function __construct($g ,$t, $s) {
        $this->games = $g;
        $this->timeRemaining = $t;
        $this->avgScore = $s;
    }

    function getGames(){
        return $this->games;
    }

    function getTimeRemaining(){
        return $this->timeRemaining;
    }

    function getScore(){
        return $this->avgScore;
    }
}
?>
