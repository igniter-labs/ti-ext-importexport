<div class="form-widget">
    <div class="form-fields">
        <div class="d-flex flex-row w-100">
            <div class="flex-fill p-4 border">
                <h4>@lang('igniterlabs.importexport::default.text_import_created')</h4>
                <p>{{ $importResults->created }}</p>
            </div>
            <div class="flex-fill p-4 border">
                <h4>@lang('igniterlabs.importexport::default.text_import_updated')</h4>
                <p>{{ $importResults->updated }}</p>
            </div>
            @if ($importResults->skippedCount)
                <div class="flex-fill p-4 border">
                    <h4>@lang('igniterlabs.importexport::default.text_import_skipped')</h4>
                    <p>{{ $importResults->skippedCount }}</p>
                </div>
            @endif
            @if ($importResults->warningCount)
                <div class="flex-fill p-4 border">
                    <h4>@lang('igniterlabs.importexport::default.text_import_warnings')</h4>
                    <p>{{ $importResults->warningCount }}</p>
                </div>
            @endif
            <div class="flex-fill p-4 border">
                <h4>@lang('igniterlabs.importexport::default.text_import_errors')</h4>
                <p>{{ $importResults->errorCount }}</p>
            </div>
        </div>

        @if ($importResults->hasMessages)
            @php
                $tabs = [
                    'skipped' => lang('igniterlabs.importexport::default.text_import_skipped'),
                    'warnings' => lang('igniterlabs.importexport::default.text_import_warnings'),
                    'errors' => lang('igniterlabs.importexport::default.text_import_errors'),
                ];

                if (!$importResults->skippedCount) unset($tabs['skipped']);
                if (!$importResults->warningCount) unset($tabs['warnings']);
                if (!$importResults->errorCount) unset($tabs['errors']);
            @endphp
            <div class="form-tabs" data-control="tab">
                <ul class="form-nav nav nav-tabs" role="tablist">
                    @foreach ($tabs as $code => $tab)
                        <li class="nav-item">
                            <a
                                class="nav-link {{ $loop->index == 0 ? 'active' : '' }}"
                                href="#importTab{{ $code }}"
                                role="tab"
                                aria-controls="importTab{{ $code }}"
                                aria-selected="{{ $loop->index == 0 ? 'true' : '' }}"
                            >{{ $tab }}</a>
                        </li>
                    @endforeach
                </ul>
                <div class="tab-content">
                    @foreach ($tabs as $code => $tab)
                        <div
                            id="importTab{{ $code }}"
                            class="tab-pane {{ $loop->index == 0 ? 'active' : '' }}"
                            role="tabpanel"
                            aria-labelledby="importTab{{ $code }}-tab"
                        >
                            <ul class="list-group">
                                @foreach ($importResults->{$code} as $row => $message)
                                    <li class="list-group-item">
                                        <strong>{{ sprintf(lang('igniterlabs.importexport::default.text_import_row'), $row + 2) }}</strong>
                                        - {{ $message }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>

