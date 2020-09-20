<div class="input-group">
    <input type="text" class="form-control btn-file-input-value" disabled="disabled">
    <div class="input-group-btn">
        <div class="btn btn-default btn-file-input">
            <span class="btn-file-input-choose"><?= lang('system::lang.extensions.button_choose'); ?></span>
            <span class="btn-file-input-change hide"><?= lang('system::lang.extensions.button_change'); ?></span>
            <input
                type="file"
                name="<?= $field->getName(); ?>"
                accept="text/csv"
                onchange="var file = this.files[0]
                $('.btn-file-input-value').val(file.name)
                $('.btn-file-upload').removeClass('hide')
                $('.btn-file-input-change').removeClass('hide')
                $('.btn-file-input-choose').addClass('hide')"
                value="<?= $value; ?>"/>
        </div>
        <button
            type="submit"
            class="btn btn-primary btn-file-upload hide"
            data-request="onImportUploadFile"
            data-request-submit="true"
        >
            <i class="fa fa-fw fa-upload"></i>&nbsp;&nbsp;
            <?= lang('igniterlabs.importexport::default.button_upload'); ?>
        </button>
    </div>
</div>
