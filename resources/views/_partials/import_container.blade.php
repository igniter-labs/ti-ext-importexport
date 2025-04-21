<?php

declare(strict_types=1);

?>
<div class="import-container">
    {!! $importPrimaryFormWidget->render() !!}
    @if ($importSecondaryFormWidget)
        {!! $importSecondaryFormWidget->render() !!}
    @endif
</div>
<?php 
