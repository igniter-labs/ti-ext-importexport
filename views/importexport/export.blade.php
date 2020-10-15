<div class="row-fluid">
    {!! form_open([
        'id' => 'form-widget',
        'role' => 'form',
        'method' => 'PATCH',
        'enctype' => 'multipart/form-data',
    ]) !!}

    {!! $this->renderExport() !!}

    {!! form_close() !!}
</div>

