<div id="exportContainer" class="export-container">
    {!! $exportPrimaryFormWidget->render() !!}
    @if ($exportSecondaryFormWidget)
        {!! $exportSecondaryFormWidget->render() !!}
    @endif
</div>
