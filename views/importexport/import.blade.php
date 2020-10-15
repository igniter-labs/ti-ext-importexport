<div class="row-fluid">
    {!! form_open([
        'id' => 'form-widget',
        'role' => 'form',
        'enctype' => 'multipart/form-data',
        'method' => 'POST',
    ]) !!}

    {!! $this->renderImport() !!}

    {!! form_close() !!}
</div>
