<?php require_once 'includes/version.php'; ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="#">Book Manager v<?php echo SCRIPT_VERSION; ?></a>
        <div class="d-flex flex-wrap text-white">
            <div class="me-3">Total Books: <?php echo $stats['total']; ?></div>
            <div class="me-3">With Metadata: <?php echo $stats['withMetadata']; ?></div>
            <div>Last Update: <?php echo $stats['lastUpdate']; ?></div>
            <div>Last HTML Update: <?php echo date('Y-m-d H:i:s', filemtime('ksiazki.html')); ?></div>
        </div>
    </div>
</nav>