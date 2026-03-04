<?php
// app/Views/home/sections/custom_html.php

$config = $section->config ?? [];
$html = $config['html'] ?? '';
$css = $config['css'] ?? '';
$js = $config['js'] ?? '';
?>
<?php if ($css): ?>
    <style>
        <?php echo $css; ?>
    </style>
<?php endif; ?>

<section class="custom-html-section">
    <?php echo $html; ?>
</section>

<?php if ($js): ?>
    <script>
        <?php echo $js; ?>
    </script>
<?php endif; ?>