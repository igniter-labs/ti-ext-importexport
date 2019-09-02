<div class="row-fluid">
    <?= form_open(current_url(),
        [
            'id' => 'form-widget',
            'role' => 'form',
            'method' => 'PATCH',
            'enctype' => 'multipart/form-data',
        ]
    ); ?>

    <?= $this->renderExport(); ?>

    <?= form_close(); ?>
</div>

