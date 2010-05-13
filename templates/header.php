<?php

if(!isset($page_type)) $page_type = '';
if(!isset($site_title)) $site_title = 'Blog';
if(!isset($site_description)) $site_description = 'Just another Ignite blog';
if(!isset($rss2_url)) $rss2_url = '';
if(!isset($comments_rss2_url)) $comments_rss2_url = '';
if(!isset($text_direction)) $text_direction = 'ltr';
if(empty($isSingle)) $isSingle = false;

?><!DOCTYPE html>
<html>
	<head>
		<title><?php e($page_title); ?></title>
		<link rel="stylesheet" type="text/css" href="<?php e($skin_iri); ?>style.css" />
		<style type="text/css">
<?php
if($isSingle)
{
	echo '#page { background: url("' . _e($skin_iri) . 'images/kubrickbgwide.jpg") repeat-y top; border: none;' . "\n";
}
else
{
	echo '#page { background: url("' . _e($skin_iri) . 'images/kubrickbg-' . _e($text_direction) . '.jpg") repeat-y top; border: none;' . "\n";
}
?>
		</style>
	</head>
	<body class="<?php e(trim($page_type)); ?>">
		<div id="page">
			<div id="header" role="banner">
			<div id="headerimg">
				<h1><a href="<?php e($app_root); ?>"><?php e($site_title); ?></a></h1>
				<div class="description"><?php echo $site_description; ?></div>
			</div>
		</div>
		<hr />
