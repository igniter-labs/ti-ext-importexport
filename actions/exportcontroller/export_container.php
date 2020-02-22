<div id="exportContainer" class="export-container">
    <?= $exportPrimaryFormWidget->render() ?>
    <?php if ($exportSecondaryFormWidget) { ?>
        <?= $exportSecondaryFormWidget->render() ?>
    <?php } ?>
</div>
