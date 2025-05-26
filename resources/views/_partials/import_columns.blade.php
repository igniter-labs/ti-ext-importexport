<div class="panel panel-light mb-0">
    <div class="table-responsive mb-0">
        <table class="table mb-0">
            <thead>
            <tr>
                <th class="list-action"></th>
                <th>@lang('igniterlabs.importexport::default.label_file_columns')</th>
                <th>@lang('igniterlabs.importexport::default.label_db_columns')</th>
            </tr>
            </thead>
            <tbody>
            @if ($importFileColumns)
                @foreach ($importFileColumns as $index => $fileColumn)
                    <tr>
                        <td class="list-action">
                            <div class="form-check">
                                <input
                                    type="checkbox"
                                    id="checkbox_{{ $index }}"
                                    class="form-check-input"
                                    name="match_columns[{{ $index }}]"
                                    value="{{ $fileColumn }}"
                                    checked="checked"
                                />
                                <label class="form-check-label" for="checkbox_{{ $index }}"></label>
                            </div>
                        </td>
                        <td>{{ $fileColumn }}</td>
                        <td>
                            <select
                                name="import_columns[{{ $index }}]"
                                class="form-select"
                            >
                                <option value="">@lang('admin::lang.text_please_select')</option>
                                @foreach ($importColumns as $column => $columnName)
                                    <option value="{{ $column }}" @selected($loop->index === $index)>{{ $column}}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                @endforeach
            @else
                <tr class="repeater-item-placeholder">
                    <td colspan="99">@lang('igniterlabs.importexport::default.text_no_import_file')</td>
                </tr>
            @endif
            </tbody>
        </table>
    </div>
</div>
