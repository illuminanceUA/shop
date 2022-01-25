<?php

function printArray($array) {
    echo '<pre>';
    print_r($array);
    echo '<pre>';
}

if(!function_exists('mb_str_replace')){

    function mb_str_replace($needle, $textReplace, $haystack){
        return implode($textReplace, explode($needle, $haystack));
    }

}