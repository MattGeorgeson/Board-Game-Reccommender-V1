<?php

class Criteria{
    private $cType;
    private $cData;

    function __construct($cType,$cData) {
        $this->cType = $cType;
        $this->cData = $cData;
    }

    function getCType(){
        return $this->cType;
    }

    function getCData(){
        return $this->cData;
    }
}
?>
