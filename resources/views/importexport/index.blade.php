<div class="container-fluid mt-4">
    <div class="form-fields p-2">
        <div class="form-group section-field span-full">
            <div class="field-section">
                <h5 class="text-muted">@lang('igniterlabs.importexport::default.text_import_export_title')</h5>
            </div>
        </div>
        <div class="form-group">
            <a
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#importExportModal"
                data-bs-backdrop="static"
                data-request="onLoadPopup"
                data-request-data="context: 'import'"
            ><i class="fa fa-upload"></i> @lang('igniterlabs.importexport::default.button_import_records')</a>
            <a
                class="btn btn-primary"
                data-bs-toggle="modal"
                data-bs-target="#importExportModal"
                data-bs-backdrop="static"
                data-request="onLoadPopup"
                data-request-data="context: 'export'"
            ><i class="fa fa-download"></i> @lang('igniterlabs.importexport::default.button_export_records')
            </a>
        </div>
        <div
            class="modal slideInDown fade"
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
                            <span class="ti-loading spinner-border fa-3x fa-fw"></span>
                            @lang('admin::lang.text_loading')
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group section-field span-full">
            <div class="field-section">
                <h5 class="text-muted">@lang('igniterlabs.importexport::default.text_history_title')</h5>
            </div>
        </div>
        <div id="import-export-history" class="form-group" role="tabpanel">
            <div class="card card-light p-3">
                <div class="table-responsive rounded">
                    <table
                        id="import-export-history-table"
                        class="table table-hover mb-0 border-bottom"
                    >
                        <thead id="import-export-history-table-head">
                            <tr>
                                <th>@lang('igniterlabs.importexport::default.column_history_date')</th>
                                <th>@lang('igniterlabs.importexport::default.column_history_type')</th>
                                <th>@lang('igniterlabs.importexport::default.column_history_status')</th>
                                <th>@lang('igniterlabs.importexport::default.column_history_message')</th>
                                <th class="text-end"></th>
                                <th class="text-end">@lang('igniterlabs.importexport::default.column_history_id')</th>
                            </tr>
                        </thead>
                        <tbody id="import-export-history-table-body">
                            @if ($history)
                                @foreach ($history as $record)
                                    <tr>
                                        <td>{{ day_elapsed($record->created_at) }}</td>
                                        <td>{{ $record->label }}</td>
                                        <td>{{ ucfirst($record->status) }}</td>
                                        <td>{{ html(nl2br($record->error_message)) }}</td>
                                        <td class="text-end">
                                            @if($record->download_url)
                                                <a
                                                    href="{{ $record->download_url }}"
                                                >@lang('igniterlabs.importexport::default.button_download')</a>
                                            @endif
                                        </td>
                                        <td class="text-end">{{ $record->id }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
