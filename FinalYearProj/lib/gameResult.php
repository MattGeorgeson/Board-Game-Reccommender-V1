<?php 

class Recommendation{
    private $game;
    private $accuracy;
    private $criteriaMatch = array ();

    function __construct($game,$accuracy,$matches) {
        $this->game = $game;
        $this->accuracy = $accuracy;
        $this->criteriaMatch = $matches;
    }

    function getGame(){
        return $this->game;
    }

    function getAccuracy(){
        return $this->accuracy;
    }

    function getCriteriaMatch(){
        return $this->criteriaMatch;
    }

    function getPlaytime(){
        return $this->game['playtime'];
    }
}
?>