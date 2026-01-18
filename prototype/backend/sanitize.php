<?php
function s_string($v) {
    return trim(filter_var($v, FILTER_SANITIZE_SPECIAL_CHARS));
}

function s_email($v) {
    return trim(filter_var($v, FILTER_SANITIZE_EMAIL));
}

function s_int($v) {
    return trim(filter_var($v, FILTER_SANITIZE_NUMBER_INT));
}
// function to sanitize inputs from user