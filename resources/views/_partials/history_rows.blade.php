@foreach ($history as $record)
    <tr @if($record->isInProgress()) data-import-in-progress @endif>
        <td>{{ day_elapsed($record->created_at) }}</td>
        <td>{{ $record->label }}</td>
        <td>
            @lang('igniterlabs.importexport::default.statuses.'.($record->status ?: 'pending'))
            @if ($record->isInProgress())
                <div class="progress mt-1" style="height: 6px; min-width: 120px;">
                    <div
                        class="progress-bar progress-bar-striped progress-bar-animated"
                        role="progressbar"
                        style="width: {{ $record->progress_percent ?? 0 }}%"
                        aria-valuenow="{{ $record->progress_percent ?? 0 }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    ></div>
                </div>
                @if ($record->total_rows)
                    <small class="text-muted">
                        {{ sprintf(lang('igniterlabs.importexport::default.text_import_progress_rows'), $record->processed_rows, $record->total_rows) }}
                    </small>
                @endif
            @endif
        </td>
        <td>{{ html(nl2br(e((string)$record->error_message))) }}</td>
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
