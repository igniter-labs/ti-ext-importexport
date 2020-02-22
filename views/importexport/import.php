<div class="row-fluid">
    <?= form_open(current_url(),
        [
            'id' => 'form-widget',
            'role' => 'form',
            'enctype' => 'multipart/form-data',
            'method' => 'POST',
        ]
    ); ?>

    <?= $this->renderImport(); ?>

    <?= form_close(); ?>
</div>
