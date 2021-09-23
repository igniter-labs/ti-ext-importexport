@php
    $fieldOptions = []; //$field->options();
    $checkedValues = (array)$field->value;
    $isScrollable = count($exportColumns) > 10;
@endphp

<div class="export-columns {{ $isScrollable ? 'is-scrollable' : '' }}">
    @if ($isScrollable)
        <small>
            @lang('admin::lang.text_select'):
            <a href="javascript:;" data-field-checkboxlist-all>@lang('admin::lang.text_select_all')</a>,
            <a href="javascript:;" data-field-checkboxlist-none>@lang('admin::lang.text_select_none')</a>
        </small>

        <div class="field-columns-scrollable">
            <div class="scrollbar">
                @endif

                @foreach ($exportColumns as $key => $column)
                    @php $checkboxId = 'checkbox_'.$field->getId().'_'.$loop->index; @endphp
                    <div class="custom-control custom-checkbox mb-2">
                        <input
                            type="hidden"
                            name="{{ $field->arrayName }}export_columns[]"
                            value="{{ $key }}"
                        />
                        <input
                            type="checkbox"
                            id="{{ $checkboxId }}"
                            class="custom-control-input"
                            name="{{ $field->getName() }}visible_columns[]"
                            value="1"
                            checked="checked"
                        />
                        <label class="custom-control-label" for="{{ $checkboxId }}">
                            @lang($column)
                        </label>
                    </div>
                @endforeach

                @if ($isScrollable)
            </div>
        </div>
    @endif

</div>
