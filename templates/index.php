<?php

/* This is a placeholder which exists solely for compatibility with WordPress */

if(!function_exists('get_bloginfo'))
{
    die('This is a template directory which should not be access directly.');
}

global $EREGANSU_TEMPLATE;
$EREGANSU_TEMPLATE = true;

$skin = $_REQUEST['template'];
$app_root = get_bloginfo('wpurl') . '/';
$skin_path = dirname(__FILE__) . '/';
$skin_iri = get_bloginfo('stylesheet_directory') . '/';

$CONFIG = array(
    'site' => array(
        'id' => 'default',
	 'name' => 'default'
    ),
    'compiled-css' => true,
);

$metaInfo = array(
    'site_name' => get_bloginfo('name'),
);

function _fn_comp($params)
{
}

function _block_widget($params, $content)
{
	return $content;
}

function e($str)
{
    echo str_replace('&quot;', '&#39;', htmlspecialchars($str));
}

require_once(dirname(__FILE__) . '/header.php');
?>
<h2>Introduction</h2>
<p>
	Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer venenatis egestas metus, sit amet ornare massa vulputate vitae. In quis ligula scelerisque urna tristique gravida vel non massa. Donec ac orci nisl, ac rutrum justo. Suspendisse potenti. Pellentesque condimentum, felis in consequat cursus, leo turpis hendrerit urna, ac sodales ante.
</p>
<p>
	Proin sed tortor eget nunc tristique placerat non pellentesque magna. Curabitur mattis venenatis diam. Duis convallis, sapien ac condimentum lobortis, nunc elit congue tortor, non consequat ante neque lobortis odio. Suspendisse consequat, mi quis gravida ultrices, urna massa accumsan ligula, vel pharetra tortor odio suscipit dui. Duis molestie eleifend lacus in scelerisque. Integer pellentesque viverra ullamcorper. Cum sociis natoque penatibus et magnis dis parturient montes, nascetur ridiculus mus. Etiam sed felis id turpis commodo feugiat. Nulla facilisi. Nulla pharetra auctor.
</p>

<?php
require_once(dirname(__FILE__) . '/footer.php');
