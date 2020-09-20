<?= form_open(current_url()) ?>
    <input type="hidden" name="context" value="<?= $context; ?>">
    <div class="modal-header">
        <h4 class="modal-title"><?= e(lang('igniterlabs.importexport::default.text_'.$context.'_title')) ?></h4>
        <button type="button" class="close" data-dismiss="modal">&times;</button>
    </div>
    <div class="modal-body">
        <div class="form-group">
            <label class="control-label"><?= e(lang('igniterlabs.importexport::default.label_'.$context.'_record')) ?></label>
            <select class="form-control" name="code">
                <option value=""><?= e(lang('admin::lang.text_select')) ?></option>
                <?php foreach ($importExports as $code => $config) { ?>
                    <option
                        value="<?= e($code) ?>"><?= isset($config['label']) ? e(trans($config['label'])) : $code ?></option>
                <?php } ?>
            </select>
        </div>
    </div>
    <div class="modal-footer">
        <button
            type="button"
            class="btn btn-default"
            data-dismiss="modal"
        ><?= e(trans('admin::lang.button_close')) ?></button>
        <button
            type="button"
            class="btn btn-primary"
            data-request="onLoadForm"
        ><?= e(trans('admin::lang.button_continue')) ?></button>
    </div>
<?= form_close() ?>