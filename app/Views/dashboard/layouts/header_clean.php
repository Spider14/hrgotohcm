<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo \App\Helpers\Security::escape($pageTitle ?? 'HRGoTo HCM'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="<?php echo \App\Helpers\Security::escape($_ENV['APP_URL']); ?>/assets/css/style.css" rel="stylesheet">
    <link rel="icon" type="image/svg+xml" href="<?php echo \App\Helpers\Security::escape($_ENV['APP_URL']); ?>/assets/favicons/favicon.svg">
    <meta name="theme-color" content="#162C5B">
    <style>
        body { background: #fff; margin: 0; padding: 20px; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
