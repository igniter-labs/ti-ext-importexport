<div class="panel panel-light mb-0">
    <div class="table-responsive mb-0">
        <table class="table mb-0">
            <thead>
            <tr>
                <th class="list-action"></th>
                <th><?= e(lang('igniterlabs.importexport::default.label_file_columns')) ?></th>
                <th><?= e(lang('igniterlabs.importexport::default.label_db_columns')) ?></th>
            </tr>
            </thead>
            <tbody>
            <?php if ($importFileColumns) { ?>
                <?php foreach ($importFileColumns as $index => $fileColumn) { ?>
                    <tr>
                        <td class="list-action">
                            <div class="custom-control custom-checkbox">
                                <input
                                    type="checkbox"
                                    id="checkbox_<?= $index ?>"
                                    class="custom-control-input"
                                    name="match_columns[<?= $index ?>]"
                                    value="<?= $fileColumn ?>"
                                    checked="checked"
                                />
                                <label class="custom-control-label" for="checkbox_<?= $index ?>"></label>
                            </div>
                        </td>
                        <td><?= $fileColumn ?></td>
                        <td>
                            <select
                                name="import_columns[<?= $index ?>]"
                                class="form-control"
                            >
                                <option value=""><?= e(lang('admin::lang.text_please_select')) ?></option>
                                <?php foreach ($importColumns as $column => $columnName) { ?>
                                    <option value="<?= $column ?>"><?= $column ?></option>
                                <?php } ?>
                            </select>
                        </td>
                    </tr>
                <?php } ?>
            <?php } else { ?>
                <tr class="repeater-item-placeholder">
                    <td colspan="99"><?= lang('igniterlabs.importexport::default.text_no_import_file') ?></td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
    </div>
</div>