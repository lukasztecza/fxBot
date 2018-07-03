<!Doctype html>
<?php require_once('webpackAssetsFetcher.php'); ?>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="description" content="Sample site" />
    <meta name="keywords" content="Sample content" />
    <meta name="author" content="Somebody" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php echo fetchWebpackAsset('css'); ?>
    <link href="/favicon.ico" rel="icon" type="image/x-icon" />
    <title>fxBot</title>
</head>
<body>
<?php include('menu.php'); ?>
