<div class="row-fluid">
    <div class="form-widget mt-4">
        <div class="form-fields">
            <div class="form-group section-field span-full">
                <div class="field-section">
                    <h5 class="text-muted"><?= e(lang('igniterlabs.importexport::default.text_import_export_title')) ?></h5>
                </div>
            </div>
            <div class="form-group">
                <a
                    class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#importExportModal"
                    data-backdrop="static"
                    data-request="onLoadPopup"
                    data-request-data="context: 'import'"
                ><i class="fa fa-upload"></i> <?= e(lang('igniterlabs.importexport::default.button_import_records')) ?></a>
                <a
                    class="btn btn-primary"
                    data-toggle="modal"
                    data-target="#importExportModal"
                    data-backdrop="static"
                    data-request="onLoadPopup"
                    data-request-data="context: 'export'"
                ><i class="fa fa-download"></i> <?= e(lang('igniterlabs.importexport::default.button_export_records')) ?>
                </a>
            </div>

            <div class="modal slideInDown fade"
                 id="importExportModal"
                 tabindex="-1"
                 role="dialog"
                 aria-labelledby="importExportModalTitle"
                 aria-hidden="true"
            >
                <div class="modal-dialog" role="document">
                    <div id="importExportModalContent" class="modal-content">
                        <div class="modal-body">
                            <div class="progress-indicator">
                                <span class="spinner"><span class="ti-loading fa-3x fa-fw"></span></span>
                                <?= e(lang('admin::lang.text_loading')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="form-fields">
            <div class="form-group section-field span-full">
                <div class="field-section">
                    <h5 class="text-muted"><?= e(lang('igniterlabs.importexport::default.text_history_title')) ?></h5>
                </div>
            </div>
            <div id="import-export-history" class="tab-pane fade" role="tabpanel">
                <div class="panel panel-light">
                </div>
            </div>
        </div>
    </div>
</div>