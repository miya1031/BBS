<?php
function h($value){
    return htmlspecialchars($value, ENT_QUOTES);
}

function url_check($value){
    return preg_replace('/((http|https):\/\/[-_.!~*\'()a-zA-Z0-9;\/?:@&=+$,%#]+)/','<a href="$1" target="_blank">$1</a>',$value);
}
?>