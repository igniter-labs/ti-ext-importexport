<div class="panel panel-light mb-0">
    <div class="panel-body">
        <p>{{ sprintf(lang('igniterlabs.importexport::default.text_import_preview'), $importSourceLabel) }}</p>
        <ul class="list-unstyled mb-0">
            @foreach (array_except($importPreview, 'samples') as $key => $count)
                <li>@lang('igniterlabs.importexport::default.preview.'.$key): <strong>{{ $count }}</strong></li>
            @endforeach
        </ul>
        @if (!empty($importPreview['samples']))
            <p class="text-muted mt-3 mb-0">
                @lang('igniterlabs.importexport::default.text_import_preview_samples')
                {{ implode(', ', $importPreview['samples']) }}
            </p>
        @endif
    </div>
</div>
