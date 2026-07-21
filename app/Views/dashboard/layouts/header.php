<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo \App\Helpers\Security::escape($pageTitle ?? 'HRGoTo HCM'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css" rel="stylesheet">
    <link href="<?php echo \App\Helpers\Security::escape($_ENV['APP_URL']); ?>/assets/css/style.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <link rel="icon" type="image/svg+xml" href="<?php echo \App\Helpers\Security::escape($_ENV['APP_URL']); ?>/assets/favicons/favicon.svg">
    <link rel="icon" type="image/png" sizes="96x96" href="<?php echo \App\Helpers\Security::escape($_ENV['APP_URL']); ?>/assets/favicons/favicon-96x96.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo \App\Helpers\Security::escape($_ENV['APP_URL']); ?>/assets/favicons/apple-touch-icon.png">
    <link rel="manifest" href="<?php echo \App\Helpers\Security::escape($_ENV['APP_URL']); ?>/assets/favicons/site.webmanifest">
    <meta name="theme-color" content="#162C5B">
</head>
<body class="bg-light dashboard-runtime-body">

<?php if (empty($suppressGlobalFlash)) require __DIR__ . '/flash.php'; ?>

<div class="wrapper d-flex align-items-stretch" style="position:relative;">
