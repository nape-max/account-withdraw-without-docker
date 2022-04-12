<?php

namespace View;

class View
{
    function generate(string $content_view, string $template_view, ?array $data = null)
    {
        if (is_array($data)) {
            extract($data);
        }

        include './src/View/' . $content_view . '/Template.php';
    }
}
