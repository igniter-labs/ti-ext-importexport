<?php

declare(strict_types=1);

?>
{!! form_open([
    'role' => 'form',
    'data-request' => 'onLoadForm',
]) !!}
<input type="hidden" name="context" value="{{ $context }}">
<div class="modal-header">
    <h4 class="modal-title">@lang('igniterlabs.importexport::default.text_'.$context.'_title')</h4>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-hidden="true"></button>
</div>
<div class="modal-body">
    <div class="form-group">
        <label class="form-label">@lang('igniterlabs.importexport::default.label_'.$context.'_record')</label>
        <select class="form-select" name="code">
            <option value="">@lang('admin::lang.text_select')</option>
            @foreach ($importExports as $code => $config)
                <option
                    value="{{ $code }}"
                >{{ isset($config['label']) ? lang($config['label']) : $code }}</option>
            @endforeach
        </select>
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
<?php 
