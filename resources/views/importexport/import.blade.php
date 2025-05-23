<div class="row-fluid">
    {!! form_open([
        'id' => 'form-widget',
        'class' => 'pt-3',
        'role' => 'form',
    ]) !!}

    <div id="importContainer">
        {!! $this->renderImport() !!}
    </div>

    {!! form_close() !!}
</div>
