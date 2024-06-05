<?php
// app/helpers.php
if (!function_exists('make_links_clickable')) {
    function make_links_clickable($text)
    {
        return preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" target="_blank">$1</a>',
            $text
        );
    }
}
