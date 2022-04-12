<?php

namespace View;

class View
{
    function generate($content_view, $template_view, $data = null)
    {
        if (is_array($data)) {
            extract($data);
        }

        include './src/View/' . $content_view . '/Template.php';
    }
}
