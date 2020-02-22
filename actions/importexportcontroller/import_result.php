<div class="form-widget">
    <div class="form-fields">
        <div class="d-flex flex-row w-100">
            <div class="flex-fill p-4 border">
                <h4><?= e(lang('igniter.importexport::default.text_import_created')) ?></h4>
                <p><?= $importResults->created ?></p>
            </div>
            <div class="flex-fill p-4 border">
                <h4><?= e(lang('igniter.importexport::default.text_import_updated')) ?></h4>
                <p><?= $importResults->updated ?></p>
            </div>
            <?php if ($importResults->skippedCount) { ?>
                <div class="flex-fill p-4 border">
                    <h4><?= e(lang('igniter.importexport::default.text_import_skipped')) ?></h4>
                    <p><?= $importResults->skippedCount ?></p>
                </div>
            <?php } ?>
            <?php if ($importResults->warningCount) { ?>
                <div class="flex-fill p-4 border">
                    <h4><?= e(lang('igniter.importexport::default.text_import_warnings')) ?></h4>
                    <p><?= $importResults->warningCount ?></p>
                </div>
            <?php } ?>
            <div class="flex-fill p-4 border">
                <h4><?= e(lang('igniter.importexport::default.text_import_errors')) ?></h4>
                <p><?= $importResults->errorCount ?></p>
            </div>
        </div>

        <?php if ($importResults->hasMessages) { ?>
            <?php
            $tabs = [
                'skipped' => lang('igniter.importexport::default.text_import_skipped'),
                'warnings' => lang('igniter.importexport::default.text_import_warnings'),
                'errors' => lang('igniter.importexport::default.text_import_errors'),
            ];

            if (!$importResults->skippedCount) unset($tabs['skipped']);
            if (!$importResults->warningCount) unset($tabs['warnings']);
            if (!$importResults->errorCount) unset($tabs['errors']);
            ?>
            <div class="form-tabs" data-control="tab">
                <ul class="form-nav nav nav-tabs" role="tablist">
                    <?php $count = 0;
                    foreach ($tabs as $code => $tab) { ?>
                        <li class="nav-item">
                            <a
                                class="nav-link <?= $count++ == 0 ? 'active' : '' ?>"
                                href="#importTab<?= $code ?>"
                                role="tab"
                                aria-controls="importTab<?= $code ?>"
                                aria-selected="<?= $count++ == 0 ? 'true' : '' ?>"
                            ><?= $tab ?></a>
                        </li>
                    <?php } ?>
                </ul>
                <div class="tab-content">
                    <?php $count = 0;
                    foreach ($tabs as $code => $tab) { ?>
                        <div
                            id="importTab<?= $code ?>"
                            class="tab-pane <?= $count++ == 0 ? 'active' : '' ?>"
                            role="tabpanel"
                            aria-labelledby="importTab<?= $code ?>-tab"
                        >
                            <ul class="list-group">
                                <?php foreach ($importResults->{$code} as $row => $message) { ?>
                                    <li class="list-group-item">
                                        <strong><?= e(sprintf(lang('igniter.importexport::default.text_import_row'), $row + 2)) ?></strong>
                                        - <?= e($message) ?>
                                    </li>
                                <?php } ?>
                            </ul>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

