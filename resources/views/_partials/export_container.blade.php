<?php

declare(strict_types=1);

?>
<div id="exportContainer" class="export-container">
    {!! $exportPrimaryFormWidget->render() !!}
    @if ($exportSecondaryFormWidget)
        {!! $exportSecondaryFormWidget->render() !!}
    @endif
</div>
<?php 
