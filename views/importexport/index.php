<div class="row-fluid">
    <div class="panel panel-light">
        <div class="list-group">
            <a
                class="list-group-item list-group-item-action"
                href="<?= admin_url('igniter/importexport/importexport/import') ?>"
                role="button"
            >
                <h5>
                    <i class="text-muted fa-fw"></i>&nbsp;&nbsp;
                    <?= e(strtoupper(lang('igniter.importexport::default.text_import_title'))) ?>
                </h5>
            </a>
        </div>
        <div class="list-group">
            <a
                class="list-group-item list-group-item-action"
                href="<?= admin_url('igniter/importexport/importexport/export') ?>"
                role="button"
            >
                <h5>
                    <i class="text-muted fa-fw"></i>&nbsp;&nbsp;
                    <?= e(lang('igniter.importexport::default.text_export_title')) ?>
                </h5>
            </a>
        </div>
    </div>
</div>

