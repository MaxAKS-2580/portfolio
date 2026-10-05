<?php
function render_nav(string $active): void
{
    $items = [
        'home'     => ['index.php', 'Portfolio'],
        'academic' => ['academic.php', 'Academic Records'],
        'skills'   => ['skills.php', 'Skills & Certifications'],
    ];
    echo '<nav class="top"><div class="wrap">';
    foreach ($items as $key => [$href, $label]) {
        $style = $key === $active ? ' style="color:var(--accent)"' : '';
        echo "<a href=\"{$href}\"{$style}>{$label}</a>";
    }
    echo '</div></nav>';
}
