<div id="importContainer" class="import-container">
    <?= $importPrimaryFormWidget->render() ?>
    <?php if ($importSecondaryFormWidget) { ?>
        <?= $importSecondaryFormWidget->render() ?>
    <?php } ?>
</div>
