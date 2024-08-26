<div class="row-fluid">
    {!! form_open([
        'id' => 'form-widget',
        'role' => 'form',
        'enctype' => 'multipart/form-data',
        'method' => 'POST',
    ]) !!}

    <div id="importContainer">
        {!! $this->renderImport() !!}
    </div>

    {!! form_close() !!}
</div>
