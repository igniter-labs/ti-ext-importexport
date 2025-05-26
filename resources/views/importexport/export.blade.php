<div class="row-fluid">
    {!! form_open([
        'id' => 'form-widget',
        'class' => 'pt-3',
        'role' => 'form',
    ]) !!}

    {!! $this->renderExport() !!}

    {!! form_close() !!}
</div>
