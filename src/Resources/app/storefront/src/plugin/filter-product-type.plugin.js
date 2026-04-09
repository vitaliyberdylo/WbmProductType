import FilterMultiSelectPlugin from 'src/plugin/listing/filter-multi-select.plugin';

export default class FilterProductTypePlugin extends FilterMultiSelectPlugin {
    getValues() {
        const checkedCheckboxes = this.el.querySelectorAll(`${this.options.checkboxSelector}:checked`);

        const selection = [];

        if (checkedCheckboxes) {
            checkedCheckboxes.forEach((checkbox) => {
                selection.push(checkbox.dataset.value || checkbox.id);
            });
        }

        this.selection = selection;
        this._updateCount();

        const values = {};
        values[this.options.name] = selection;

        return values;
    }

    setValuesFromUrl(params = {}) {
        let stateChanged = false;
        const properties = params[this.options.name];
        const values = properties ? properties.split('|') : [];

        const currentValues = this._getSelectedValues();
        const uncheckValues = currentValues.filter(v => !values.includes(v));
        const checkValues = values.filter(v => !currentValues.includes(v));

        if (uncheckValues.length > 0 || checkValues.length > 0) {
            stateChanged = true;
        }

        checkValues.forEach(value => {
            const checkbox = this._findCheckboxByValue(value);
            if (checkbox) {
                checkbox.checked = true;
                this.selection.push(value);
            }
        });

        uncheckValues.forEach(value => {
            const checkbox = this._findCheckboxByValue(value);
            if (checkbox) {
                checkbox.checked = false;
            }
            this.selection = this.selection.filter(item => item !== value);
        });

        this._updateCount();

        return stateChanged;
    }

    getLabels() {
        const checked = this.el.querySelectorAll(`${this.options.checkboxSelector}:checked`);
        const labels = [];

        if (checked) {
            checked.forEach((checkbox) => {
                labels.push({
                    label: checkbox.dataset.label,
                    id: checkbox.dataset.value || checkbox.id,
                });
            });
        }

        return labels;
    }

    reset(value) {
        const checkbox = this._findCheckboxByValue(value);
        if (checkbox) {
            checkbox.checked = false;
        }
    }

    _getSelectedValues() {
        const checked = this.el.querySelectorAll(`${this.options.checkboxSelector}:checked`);
        return Array.from(checked).map(cb => cb.dataset.value || cb.id);
    }

    _findCheckboxByValue(value) {
        const checkboxes = this.el.querySelectorAll(this.options.checkboxSelector);

        for (const checkbox of checkboxes) {
            if (checkbox.dataset.value === value) {
                return checkbox;
            }
        }

        return null;
    }

    _updateCount() {
        const counter = this.el.querySelector(this.options.countSelector);
        if (counter) {
            counter.textContent = this.selection.length ? `(${this.selection.length})` : '';
        }
    }
}
