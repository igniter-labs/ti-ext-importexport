{!! form_open([
    'role' => 'form',
    'data-request' => 'onLoadImportForm',
    'data-request-submit' => 'true',
    'enctype' => 'multipart/form-data',
    'method' => 'POST',
]) !!}
<div class="modal-header">
    <h4 class="modal-title">@lang('igniterlabs.importexport::default.text_import_title')</h4>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
</div>
<div class="modal-body">
    <div class="form-group">
        <label class="form-label">@lang('igniterlabs.importexport::default.label_import_record')</label>
        <select class="form-select" name="code">
            <option value="">@lang('admin::lang.text_select')</option>
            @foreach ($importExports as $code => $config)
                <option
                    value="{{ $code }}"
                >{{ isset($config['label']) ? lang($config['label']) : $code }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">@lang('igniterlabs.importexport::default.label_import_file')</label>
        <div class="input-group">
            <input
                type="text"
                class="form-control btn-file-input-value"
                value=""
                disabled="disabled"
            />
            <div class="btn btn-default btn-file-input">
                <span class="btn-file-input-choose">@lang('igniterlabs.importexport::default.button_choose')</span>
                <span class="btn-file-input-change hide">@lang('igniterlabs.importexport::default.button_change')</span>
                <input
                    type="file"
                    name="import_file"
                    accept="text/csv"
                    onchange="var file = this.files[0]
                    $('.btn-file-input-value').val(file.name)
                    $('.btn-file-upload').removeClass('hide')
                    $('.btn-file-input-change').removeClass('hide')
                    $('.btn-file-input-choose').addClass('hide')"
                    value=""
                />
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button
        type="button"
        class="btn btn-default"
        data-bs-dismiss="modal"
    >@lang('admin::lang.button_close')</button>
    <button
        type="submit"
        class="btn btn-primary"
    >@lang('admin::lang.button_continue')</button>
</div>
{!! form_close() !!}
