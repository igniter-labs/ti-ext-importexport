<?php
$fieldOptions = []; //$field->options();
$checkedValues = (array)$field->value;
$isScrollable = count($exportColumns) > 10;
?>

<div class="export-columns <?= $isScrollable ? 'is-scrollable' : '' ?>">
    <?php if ($isScrollable) { ?>
    <small>
        <?= e(lang('admin::lang.text_select')) ?>:
        <a href="javascript:;" data-field-checkboxlist-all><?= e(lang('admin::lang.text_select_all')) ?></a>,
        <a href="javascript:;" data-field-checkboxlist-none><?= e(lang('admin::lang.text_select_none')) ?></a>
    </small>

    <div class="field-columns-scrollable">
        <div class="scrollbar">
            <?php } ?>

            <?php $index = 0;
            foreach ($exportColumns as $key => $column) { ?>
                <?php
                $index++;
                $checkboxId = 'checkbox_'.$field->getId().'_'.$index;
                ?>
                <div class="custom-control custom-checkbox mb-2">
                    <input
                        type="hidden"
                        name="<?= $field->arrayName ?>export_columns[]"
                        value="<?= $key ?>"
                    />
                    <input
                        type="checkbox"
                        id="<?= $checkboxId ?>"
                        class="custom-control-input"
                        name="<?= $field->getName() ?>visible_columns[]"
                        value="1"
                        checked="checked"
                    />
                    <label class="custom-control-label" for="<?= $checkboxId ?>">
                        <?= e(lang($column)) ?>
                    </label>
                </div>
            <?php } ?>

            <?php if ($isScrollable) { ?>
        </div>
    </div>
<?php } ?>

</div>
