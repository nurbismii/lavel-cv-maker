(function () {
    var repeatIndex = null;
    var photoCropper = null;
    var photoCropModal = null;
    var pendingPhotoInput = null;
    var pendingPhotoObjectUrl = null;
    var pendingPhotoApplied = false;
    var previewPhotoObjectUrl = null;
    var activeWizardIndex = 0;
    var GUIDE_STORAGE_KEY = 'vitae.cv-form-guide.seen.v1';
    var CV_GUIDE_STEPS = [
        {
            title: 'Isi CV Bertahap',
            selector: '[data-guide-target="wizard-steps"]',
            html: 'Gunakan step di bagian atas untuk mengisi CV sesuai urutan. Step berikutnya baru bisa dibuka setelah step sebelumnya lengkap.',
        },
        {
            title: 'Tombol Berikutnya dan Sebelumnya',
            selector: '[data-guide-target="save-actions"]',
            html: 'Gunakan Berikutnya untuk lanjut dan Sebelumnya untuk kembali. Jika ada data wajib yang kosong, sistem akan menahan Anda di step yang perlu dilengkapi.',
        },
        {
            title: 'Tambah Pengalaman',
            selector: '[data-guide-target="add-experience"]',
            html: 'Jika memiliki pengalaman kerja lebih dari satu, buka step Pengalaman lalu klik tombol Tambah untuk membuat baris pengalaman baru.',
        },
        {
            title: 'Simpan Draft dan Preview',
            selector: '[data-guide-target="save-actions"]',
            html: 'Simpan Draft menyimpan data sementara. Simpan & Preview menyimpan data lalu membuka tampilan CV untuk dicek sebelum download PDF.',
        },
    ];
    var WIZARD_VALIDATION_RULES = {
        personal: {
            fields: [
                { selector: '[name="birth_place"]', label: 'Tempat lahir' },
                { selector: '[name="gender"]', label: 'Jenis kelamin' },
                { selector: '[name="marital_status"]', label: 'Status pernikahan' },
                { selector: '[name="address"]', label: 'Alamat lengkap' },
                { selector: '[name="phone"]', label: 'No. HP' },
                { selector: '[name="email"]', label: 'Email' },
            ],
        },
        summary: {
            fields: [
                { selector: '[name="profile_summary"]', label: 'Ringkasan profil' },
            ],
        },
        skills: {
            fields: [
                { selector: '[name="technical_skills"]', label: 'Keahlian teknis' },
            ],
        },
    };

    function highestRepeatIndex() {
        var highest = -1;

        document.querySelectorAll('#cvForm [name]').forEach(function (field) {
            var pattern = /\[(\d+)\]/g;
            var match;

            while ((match = pattern.exec(field.name)) !== null) {
                highest = Math.max(highest, parseInt(match[1], 10));
            }
        });

        return highest;
    }

    function uniqueIndex() {
        if (repeatIndex === null) {
            repeatIndex = highestRepeatIndex() + 1;
        }

        var index = repeatIndex;
        repeatIndex += 1;

        return String(index);
    }

    function addRepeatItem(type) {
        var template = document.querySelector('[data-repeat-template="' + type + '"]');
        var list = document.querySelector('[data-repeat-list="' + type + '"]');

        if (!template || !list) {
            return;
        }

        var index = uniqueIndex();
        var html = template.innerHTML.replace(/__INDEX__/g, index);
        var wrapper = document.createElement('div');
        var item = null;

        wrapper.innerHTML = html.trim();
        item = wrapper.firstElementChild;
        list.appendChild(item);
        applyCurrentToggles(list);
    }

    function clearInputs(container) {
        container.querySelectorAll('input, textarea, select').forEach(function (field) {
            if (field.type === 'checkbox' || field.type === 'radio') {
                field.checked = false;
                return;
            }

            if (field.tagName === 'SELECT') {
                field.selectedIndex = 0;
                return;
            }

            field.value = '';
        });
    }

    function removeRepeatItem(button) {
        var item = button.closest('[data-repeat-item]');
        var list = button.closest('[data-repeat-list]');

        if (!item || !list) {
            return;
        }

        if (list.querySelectorAll('[data-repeat-item]').length <= 1) {
            clearInputs(item);
            return;
        }

        item.remove();
    }

    function locationPlaceholder(select) {
        return select.dataset.locationPlaceholder || 'Pilih data';
    }

    function setLocationPlaceholder(select, text) {
        select.innerHTML = '';
        select.appendChild(new Option(text || locationPlaceholder(select), ''));
    }

    function clearLocationSelect(select) {
        setLocationPlaceholder(select, locationPlaceholder(select));
        select.disabled = true;
    }

    function clearLocationChildren(select) {
        var childSelector = select.dataset.locationChild;
        var child = childSelector ? document.querySelector(childSelector) : null;

        if (!child) {
            return;
        }

        clearLocationSelect(child);
        clearLocationChildren(child);
    }

    function buildLocationUrl(select, parentValue) {
        var separator = select.dataset.locationUrl.indexOf('?') === -1 ? '?' : '&';

        return select.dataset.locationUrl + separator + encodeURIComponent(select.dataset.locationParam) + '=' + encodeURIComponent(parentValue);
    }

    function setLocationOptions(select, items) {
        setLocationPlaceholder(select, locationPlaceholder(select));

        items.forEach(function (item) {
            select.appendChild(new Option(item.name, item.id));
        });

        select.disabled = items.length === 0;
    }

    function loadLocationChild(parentSelect) {
        var childSelector = parentSelect.dataset.locationChild;
        var child = childSelector ? document.querySelector(childSelector) : null;

        if (!child) {
            return;
        }

        clearLocationSelect(child);
        clearLocationChildren(child);

        if (!parentSelect.value || !child.dataset.locationUrl || !child.dataset.locationParam) {
            return;
        }

        setLocationPlaceholder(child, 'Memuat data...');

        fetch(buildLocationUrl(child, parentSelect.value), {
            headers: {
                'Accept': 'application/json',
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Gagal memuat master wilayah.');
                }

                return response.json();
            })
            .then(function (payload) {
                setLocationOptions(child, payload.data || []);
            })
            .catch(function () {
                setLocationPlaceholder(child, 'Gagal memuat data');
                child.disabled = true;
            });
    }

    function organizationPlaceholder(select) {
        return select.dataset.organizationPlaceholder || 'Pilih data';
    }

    function organizationCustomOption(select) {
        return select.dataset.organizationCustom ? new Option('Lainnya', '__custom__') : null;
    }

    function selectedOrganizationOptionId(select) {
        var option = select && select.selectedOptions && select.selectedOptions.length ? select.selectedOptions[0] : null;

        return option && option.dataset.optionId ? option.dataset.optionId : '';
    }

    function setOrganizationPlaceholder(select, text) {
        select.innerHTML = '';
        select.appendChild(new Option(text || organizationPlaceholder(select), ''));
    }

    function appendOrganizationCustomOption(select) {
        var option = organizationCustomOption(select);

        if (option) {
            select.appendChild(option);
        }
    }

    function clearOrganizationSelect(select) {
        setOrganizationPlaceholder(select, organizationPlaceholder(select));
        appendOrganizationCustomOption(select);
        select.disabled = true;
        syncOrganizationChoice(select);
    }

    function clearOrganizationChildren(select) {
        var childSelector = select.dataset.organizationChild;
        var child = childSelector ? document.querySelector(childSelector) : null;

        if (!child) {
            return;
        }

        clearOrganizationSelect(child);
        clearOrganizationChildren(child);
    }

    function setOrganizationOptions(select, items, allowCustom) {
        setOrganizationPlaceholder(select, organizationPlaceholder(select));

        items.forEach(function (item) {
            var option = new Option(item.name, item.name);

            option.dataset.optionId = item.id;
            select.appendChild(option);
        });

        if (allowCustom) {
            appendOrganizationCustomOption(select);
        }

        select.disabled = items.length === 0 && !allowCustom;
        syncOrganizationChoice(select);
    }

    function organizationUrl(url, params) {
        var query = Object.keys(params)
            .filter(function (key) {
                return params[key] !== null && params[key] !== undefined && params[key] !== '';
            })
            .map(function (key) {
                return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
            })
            .join('&');

        if (!query) {
            return url;
        }

        return url + (url.indexOf('?') === -1 ? '?' : '&') + query;
    }

    function organizationRequestParams(parentSelect, child) {
        var departmentSelect = document.querySelector('#departmentSelect');
        var divisionSelect = document.querySelector('#divisionSelect');
        var params = {};

        if (child && child.id === 'divisionSelect') {
            params.department_id = selectedOrganizationOptionId(parentSelect);
            return params;
        }

        params.department_id = selectedOrganizationOptionId(departmentSelect);

        if (divisionSelect && divisionSelect.value !== '__custom__') {
            params.division_id = selectedOrganizationOptionId(divisionSelect);
        }

        return params;
    }

    function loadOrganizationChild(parentSelect) {
        var childSelector = parentSelect.dataset.organizationChild;
        var child = childSelector ? document.querySelector(childSelector) : null;
        var params = child ? organizationRequestParams(parentSelect, child) : {};
        var hasRequiredParent = Object.keys(params).some(function (key) {
            return params[key];
        });

        if (!child) {
            return;
        }

        clearOrganizationSelect(child);
        clearOrganizationChildren(child);

        if (!parentSelect.dataset.organizationUrl || !hasRequiredParent) {
            return;
        }

        setOrganizationPlaceholder(child, 'Memuat data...');

        fetch(organizationUrl(parentSelect.dataset.organizationUrl, params), {
            headers: {
                'Accept': 'application/json',
            },
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Gagal memuat master organisasi.');
                }

                return response.json();
            })
            .then(function (payload) {
                setOrganizationOptions(child, payload.data || [], !!child.dataset.organizationCustom);
            })
            .catch(function () {
                setOrganizationOptions(child, [], !!child.dataset.organizationCustom);

                if (child.options.length) {
                    child.options[0].textContent = 'Gagal memuat data';
                }
            });
    }

    function syncOrganizationChoice(select) {
        var hidden = select && select.dataset.organizationTarget ? document.querySelector(select.dataset.organizationTarget) : null;
        var custom = select && select.dataset.organizationCustom ? document.querySelector(select.dataset.organizationCustom) : null;
        var isCustom = select && select.value === '__custom__';

        if (custom) {
            custom.hidden = !isCustom;
            custom.disabled = !isCustom;

            if (!isCustom) {
                custom.value = '';
            }
        }

        if (hidden) {
            hidden.value = isCustom && custom ? custom.value.trim() : (select.value || '');
        }
    }

    function syncOrganizationCustomInput(input) {
        var selector = '#' + input.id;
        var select = document.querySelector('[data-organization-custom="' + selector + '"]');

        if (select) {
            syncOrganizationChoice(select);
        }
    }

    function initOrganizationFields() {
        document.querySelectorAll('[data-organization-target]').forEach(function (select) {
            syncOrganizationChoice(select);
        });
    }

    function applyCurrentToggles(root) {
        (root || document).querySelectorAll('[data-current-checkbox]').forEach(function (checkbox) {
            var item = checkbox.closest('[data-repeat-item]');
            var endMonth = item ? item.querySelector('[data-current-target]') : null;

            if (!endMonth) {
                return;
            }

            endMonth.disabled = checkbox.checked;

            if (checkbox.checked) {
                endMonth.value = '';
            }
        });
    }

    function updateCounters() {
        document.querySelectorAll('.js-countable').forEach(function (field) {
            var counter = document.querySelector(field.dataset.counter);

            if (counter) {
                counter.textContent = field.value.length;
            }
        });
    }

    function initGuideTooltips() {
        if (!window.bootstrap || !window.bootstrap.Tooltip) {
            return;
        }

        document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (element) {
            if (!bootstrap.Tooltip.getInstance(element)) {
                new bootstrap.Tooltip(element, {
                    trigger: 'hover focus',
                });
            }
        });
    }

    function storageGet(key) {
        try {
            return window.localStorage ? window.localStorage.getItem(key) : null;
        } catch (exception) {
            return null;
        }
    }

    function storageSet(key, value) {
        try {
            if (window.localStorage) {
                window.localStorage.setItem(key, value);
            }
        } catch (exception) {
            return;
        }
    }

    function clearGuideHighlight() {
        document.querySelectorAll('.cv-guide-highlight').forEach(function (element) {
            element.classList.remove('cv-guide-highlight');
        });
    }

    function isGuideTargetVisible(element) {
        return !!(element && element.getClientRects && element.getClientRects().length);
    }

    function highlightGuideTarget(selector) {
        var target = selector ? document.querySelector(selector) : null;

        clearGuideHighlight();

        if (!isGuideTargetVisible(target)) {
            return;
        }

        target.classList.add('cv-guide-highlight');
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    function finishGuide(markSeen) {
        clearGuideHighlight();

        if (markSeen) {
            storageSet(GUIDE_STORAGE_KEY, '1');
        }
    }

    function showGuideStep(index, markSeenOnClose) {
        var step = CV_GUIDE_STEPS[index];
        var isLastStep = index >= CV_GUIDE_STEPS.length - 1;

        if (!step || !window.Swal || typeof window.Swal.fire !== 'function') {
            finishGuide(markSeenOnClose);
            return;
        }

        highlightGuideTarget(step.selector);

        window.Swal.fire({
            icon: 'info',
            title: step.title,
            html: '<div class="text-start">' + step.html + '</div>',
            confirmButtonText: isLastStep ? 'Selesai' : 'Lanjut',
            denyButtonText: 'Kembali',
            cancelButtonText: 'Tutup',
            showDenyButton: index > 0,
            showCancelButton: true,
            reverseButtons: true,
            allowOutsideClick: false,
        }).then(function (result) {
            clearGuideHighlight();

            if (result.isConfirmed) {
                if (isLastStep) {
                    finishGuide(markSeenOnClose);
                    return;
                }

                showGuideStep(index + 1, markSeenOnClose);
                return;
            }

            if (result.isDenied) {
                showGuideStep(Math.max(0, index - 1), markSeenOnClose);
                return;
            }

            finishGuide(markSeenOnClose);
        });
    }

    function startCvGuide(markSeenOnClose) {
        showGuideStep(0, markSeenOnClose);
    }

    function shouldAutoStartGuide() {
        var form = document.getElementById('cvForm');

        if (!form || storageGet(GUIDE_STORAGE_KEY) === '1') {
            return false;
        }

        return !document.querySelector('.alert-danger.app-alert');
    }

    function isFocusableCvField(element) {
        return element.matches('#cvForm input:not([type="hidden"]), #cvForm textarea, #cvForm select');
    }

    function fieldFocusGroup(field) {
        var rowColumn = field.closest('.row > [class*="col-"]');

        if (rowColumn) {
            return rowColumn;
        }

        var groupedField = field.closest('.mb-3');

        if (groupedField) {
            return groupedField;
        }

        var cardBody = field.closest('.app-card-body');
        var directChild = field;

        while (cardBody && directChild && directChild.parentElement !== cardBody) {
            directChild = directChild.parentElement;
        }

        if (directChild && directChild !== field) {
            return directChild;
        }

        return cardBody;
    }

    function clearFieldFocusMode(form) {
        var targetForm = form || document.getElementById('cvForm');

        if (!targetForm) {
            return;
        }

        targetForm.classList.remove('is-field-focus-mode');
        targetForm.querySelectorAll('.is-field-active, .is-field-dimmed').forEach(function (fieldGroup) {
            fieldGroup.classList.remove('is-field-active', 'is-field-dimmed');
        });
    }

    function applyFieldFocusMode(field) {
        var form = field.closest('#cvForm');
        var panel = field.closest('[data-wizard-panel]');
        var activeGroup = fieldFocusGroup(field);

        if (!form || !panel || !activeGroup) {
            return;
        }

        var groups = [];

        panel.querySelectorAll('input:not([type="hidden"]), textarea, select').forEach(function (control) {
            var group = fieldFocusGroup(control);

            if (group && groups.indexOf(group) === -1) {
                groups.push(group);
            }
        });

        clearFieldFocusMode(form);
        form.classList.add('is-field-focus-mode');

        groups.forEach(function (group) {
            group.classList.toggle('is-field-active', group === activeGroup);
            group.classList.toggle('is-field-dimmed', group !== activeGroup);
        });
    }

    function wizardElements() {
        var root = document.querySelector('[data-cv-wizard]');
        var form = root ? root.closest('form') : null;

        if (!root || !form) {
            return null;
        }

        return {
            root: root,
            form: form,
            panels: Array.prototype.slice.call(root.querySelectorAll('[data-wizard-panel]')),
            steps: Array.prototype.slice.call(root.querySelectorAll('[data-wizard-step-target]')),
            prevButton: form.querySelector('[data-wizard-prev]'),
            nextButton: form.querySelector('[data-wizard-next]'),
            currentLabel: root.querySelector('[data-wizard-current]'),
            totalLabel: root.querySelector('[data-wizard-total]'),
            progressBar: root.querySelector('[data-wizard-progress]'),
            activeTitle: root.querySelector('[data-wizard-active-title]'),
        };
    }

    function wizardPanelIndexByKey(panels, key) {
        var index = -1;

        panels.forEach(function (panel, panelIndex) {
            if (panel.dataset.wizardPanel === key) {
                index = panelIndex;
            }
        });

        return index;
    }

    function wizardStepForPanel(elements, panel) {
        var key = panel ? panel.dataset.wizardPanel : null;

        if (!key) {
            return null;
        }

        return elements.steps.find(function (step) {
            return step.dataset.wizardStepTarget === key;
        }) || null;
    }

    function fieldValue(field) {
        if (!field) {
            return '';
        }

        if (field.type === 'checkbox' || field.type === 'radio') {
            return field.checked ? field.value : '';
        }

        return String(field.value || '').trim();
    }

    function fieldHasValue(field) {
        if (!field) {
            return false;
        }

        if (field.disabled) {
            return true;
        }

        return fieldValue(field) !== '';
    }

    function fieldErrorContainer(field) {
        if (!field) {
            return null;
        }

        return field.closest('.form-check') || fieldFocusGroup(field) || field.parentElement;
    }

    function clearFieldWizardError(field) {
        var container = fieldErrorContainer(field);

        if (!field || !field.classList.contains('is-wizard-invalid')) {
            return;
        }

        field.classList.remove('is-wizard-invalid');

        if (field.dataset.wizardHadInvalid !== '1') {
            field.classList.remove('is-invalid');
        }

        delete field.dataset.wizardHadInvalid;

        if (container) {
            container.querySelectorAll('.js-wizard-feedback').forEach(function (feedback) {
                feedback.remove();
            });
        }
    }

    function clearPanelWizardErrors(panel) {
        panel.querySelectorAll('.is-wizard-invalid').forEach(function (field) {
            clearFieldWizardError(field);
        });

        panel.querySelectorAll('.js-wizard-feedback').forEach(function (feedback) {
            feedback.remove();
        });
    }

    function addWizardFieldError(errors, field, message, options) {
        var container = fieldErrorContainer(field);
        var feedback = null;

        errors.push({
            field: field,
            message: message,
        });

        if (!field || (options && options.silent)) {
            return;
        }

        if (!field.classList.contains('is-wizard-invalid')) {
            field.dataset.wizardHadInvalid = field.classList.contains('is-invalid') ? '1' : '0';
        }

        field.classList.add('is-invalid', 'is-wizard-invalid');

        if (!container) {
            return;
        }

        feedback = container.querySelector('.js-wizard-feedback');

        if (!feedback) {
            feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block js-wizard-feedback';
            container.appendChild(feedback);
        }

        feedback.textContent = message;
    }

    function requiredMessage(label) {
        return label + ' wajib diisi sebelum lanjut ke step berikutnya.';
    }

    function validateRequiredField(container, selector, label, options, errors) {
        var field = container ? container.querySelector(selector) : null;

        if (!field || fieldHasValue(field)) {
            return true;
        }

        addWizardFieldError(errors, field, requiredMessage(label), options);

        return false;
    }

    function repeatRows(panel, type) {
        var list = panel ? panel.querySelector('[data-repeat-list="' + type + '"]') : null;

        return list ? Array.prototype.slice.call(list.querySelectorAll('[data-repeat-item]')) : [];
    }

    function repeatField(row, name) {
        return row ? row.querySelector('[name$="[' + name + ']"]') : null;
    }

    function repeatRowHasValue(row, names) {
        return names.some(function (name) {
            return fieldHasValue(repeatField(row, name));
        });
    }

    function validateSimpleWizardPanel(panel, options, errors) {
        var rule = WIZARD_VALIDATION_RULES[panel.dataset.wizardPanel];

        if (!rule || !rule.fields) {
            return;
        }

        rule.fields.forEach(function (fieldRule) {
            validateRequiredField(panel, fieldRule.selector, fieldRule.label, options, errors);
        });
    }

    function validateEducationWizardPanel(panel, options, errors) {
        var rows = repeatRows(panel, 'educations');
        var hasStartedRow = false;
        var completeRows = 0;

        rows.forEach(function (row) {
            var started = repeatRowHasValue(row, ['level', 'institution', 'major', 'graduation_year']);
            var before = errors.length;
            var level = fieldValue(repeatField(row, 'level'));

            if (!started) {
                return;
            }

            hasStartedRow = true;

            validateRequiredField(row, '[name$="[level]"]', 'Jenjang pendidikan', options, errors);
            validateRequiredField(row, '[name$="[institution]"]', 'Nama institusi', options, errors);
            validateRequiredField(row, '[name$="[graduation_year]"]', 'Tahun lulus', options, errors);

            if (level && ['SD', 'SMP'].indexOf(level) === -1) {
                validateRequiredField(row, '[name$="[major]"]', 'Jurusan', options, errors);
            }

            if (errors.length === before) {
                completeRows += 1;
            }
        });

        if (!completeRows && !hasStartedRow && rows.length) {
            addWizardFieldError(
                errors,
                repeatField(rows[0], 'level'),
                'Isi minimal satu pendidikan lengkap sebelum lanjut ke step berikutnya.',
                options
            );
        }
    }

    function validateExperienceWizardPanel(panel, options, errors) {
        var rows = repeatRows(panel, 'experiences');
        var hasStartedRow = false;
        var completeRows = 0;

        rows.forEach(function (row) {
            var currentField = repeatField(row, 'is_current');
            var started = repeatRowHasValue(row, ['position', 'company', 'department', 'division', 'start_month', 'end_month', 'responsibilities'])
                || (currentField && currentField.checked);
            var before = errors.length;

            if (!started) {
                return;
            }

            hasStartedRow = true;

            validateRequiredField(row, '[name$="[position]"]', 'Nama posisi/jabatan', options, errors);
            validateRequiredField(row, '[name$="[company]"]', 'Nama perusahaan', options, errors);
            validateRequiredField(row, '[name$="[start_month]"]', 'Bulan mulai', options, errors);
            validateRequiredField(row, '[name$="[responsibilities]"]', 'Job description', options, errors);

            if (!currentField || !currentField.checked) {
                validateRequiredField(row, '[name$="[end_month]"]', 'Bulan selesai', options, errors);
            }

            if (errors.length === before) {
                completeRows += 1;
            }
        });

        if (!completeRows && !hasStartedRow && rows.length) {
            addWizardFieldError(
                errors,
                repeatField(rows[0], 'position'),
                'Isi minimal satu pengalaman kerja lengkap sebelum lanjut ke step berikutnya.',
                options
            );
        }
    }

    function validateCertificationsWizardPanel(panel, options, errors) {
        repeatRows(panel, 'certifications').forEach(function (row) {
            var hasLifetime = repeatField(row, 'is_lifetime') && repeatField(row, 'is_lifetime').checked;
            var started = repeatRowHasValue(row, ['name', 'issuer', 'year', 'valid_until_year']) || hasLifetime;

            if (!started) {
                return;
            }

            validateRequiredField(row, '[name$="[name]"]', 'Nama sertifikasi/pelatihan', options, errors);
            validateRequiredField(row, '[name$="[issuer]"]', 'Penerbit/penyelenggara', options, errors);
            validateRequiredField(row, '[name$="[year]"]', 'Tahun sertifikasi/pelatihan', options, errors);
        });
    }

    function validateWizardPanel(panel, options) {
        var errors = [];
        var panelKey = panel ? panel.dataset.wizardPanel : null;

        if (!panel) {
            return {
                valid: true,
                errors: errors,
                panel: panel,
            };
        }

        if (!options || !options.silent) {
            clearPanelWizardErrors(panel);
        }

        validateSimpleWizardPanel(panel, options || {}, errors);

        if (panelKey === 'education') {
            validateEducationWizardPanel(panel, options || {}, errors);
        }

        if (panelKey === 'experience') {
            validateExperienceWizardPanel(panel, options || {}, errors);
        }

        if (panelKey === 'certifications') {
            validateCertificationsWizardPanel(panel, options || {}, errors);
        }

        return {
            valid: errors.length === 0,
            errors: errors,
            panel: panel,
        };
    }

    function highestReachableWizardIndex(elements) {
        var reachableIndex = elements.panels.length - 1;

        elements.panels.some(function (panel, index) {
            if (index === elements.panels.length - 1) {
                return true;
            }

            if (!validateWizardPanel(panel, { silent: true }).valid) {
                reachableIndex = index;
                return true;
            }

            return false;
        });

        return reachableIndex;
    }

    function updateWizardAccessState(elements) {
        var maxReachableIndex = highestReachableWizardIndex(elements);

        elements.steps.forEach(function (step) {
            var targetIndex = wizardPanelIndexByKey(elements.panels, step.dataset.wizardStepTarget);
            var isLocked = targetIndex > maxReachableIndex;

            step.classList.toggle('is-locked', isLocked);
            step.setAttribute('aria-disabled', isLocked ? 'true' : 'false');
            step.title = isLocked ? 'Lengkapi step sebelumnya terlebih dahulu.' : '';
        });
    }

    function focusWizardValidationError(result) {
        var firstError = result.errors.find(function (error) {
            return error.field && typeof error.field.focus === 'function';
        });

        if (firstError && firstError.field) {
            firstError.field.focus({ preventScroll: true });
        }
    }

    function notifyWizardValidation(panel) {
        var title = panel && panel.dataset.wizardTitle ? panel.dataset.wizardTitle : 'step ini';

        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'warning',
                title: 'Step belum lengkap',
                text: 'Lengkapi ' + title + ' sebelum lanjut ke step berikutnya.',
                confirmButtonText: 'Mengerti',
            });
        }
    }

    function validateWizardPath(elements, targetIndex) {
        var result = null;
        var invalidIndex = -1;

        elements.panels.some(function (panel, index) {
            if (index >= targetIndex) {
                return true;
            }

            result = validateWizardPanel(panel);

            if (!result.valid) {
                invalidIndex = index;
                return true;
            }

            return false;
        });

        if (invalidIndex === -1) {
            return true;
        }

        setWizardStep(invalidIndex, { scroll: true });
        focusWizardValidationError(result);
        notifyWizardValidation(result.panel);

        return false;
    }

    function goToWizardStep(index, options) {
        var elements = wizardElements();

        if (!elements || !elements.panels.length) {
            return;
        }

        if (index > activeWizardIndex && !validateWizardPath(elements, index)) {
            return;
        }

        setWizardStep(index, options);
    }

    function refreshWizardAccessState() {
        var elements = wizardElements();

        if (!elements) {
            return;
        }

        updateWizardAccessState(elements);
        markWizardErrors(elements);
    }

    function markWizardErrors(elements) {
        elements.panels.forEach(function (panel) {
            var step = wizardStepForPanel(elements, panel);
            var hasError = !!panel.querySelector('.is-invalid, .invalid-feedback.d-block');

            if (step) {
                step.classList.toggle('has-error', hasError);
            }
        });
    }

    function firstWizardErrorIndex(elements) {
        var errorElement = elements.root.querySelector('.is-invalid, .invalid-feedback.d-block');
        var panel = errorElement ? errorElement.closest('[data-wizard-panel]') : null;

        if (!panel) {
            return -1;
        }

        return elements.panels.indexOf(panel);
    }

    function initialWizardIndex(elements) {
        var initialStep = elements.root.dataset.initialStep || '';

        return initialStep ? wizardPanelIndexByKey(elements.panels, initialStep) : -1;
    }

    function setWizardStep(index, options) {
        var elements = wizardElements();

        if (!elements || !elements.panels.length) {
            return;
        }

        clearFieldFocusMode(elements.form);

        var total = elements.panels.length;
        var boundedIndex = Math.max(0, Math.min(index, total - 1));
        var activePanel = elements.panels[boundedIndex];
        var activeKey = activePanel.dataset.wizardPanel;
        var progress = total > 1 ? ((boundedIndex + 1) / total) * 100 : 100;

        activeWizardIndex = boundedIndex;

        elements.panels.forEach(function (panel, panelIndex) {
            var isActive = panelIndex === boundedIndex;

            panel.hidden = !isActive;
            panel.classList.toggle('is-active', isActive);
            panel.setAttribute('aria-hidden', isActive ? 'false' : 'true');
        });

        elements.steps.forEach(function (step) {
            var targetIndex = wizardPanelIndexByKey(elements.panels, step.dataset.wizardStepTarget);
            var isActive = step.dataset.wizardStepTarget === activeKey;
            var targetPanel = targetIndex > -1 ? elements.panels[targetIndex] : null;
            var isComplete = targetPanel && targetIndex < boundedIndex
                && validateWizardPanel(targetPanel, { silent: true }).valid;

            step.classList.toggle('is-active', isActive);
            step.classList.toggle('is-complete', !!isComplete);
            step.setAttribute('aria-selected', isActive ? 'true' : 'false');
            step.setAttribute('tabindex', isActive ? '0' : '-1');
        });

        if (elements.currentLabel) {
            elements.currentLabel.textContent = String(boundedIndex + 1);
        }

        if (elements.totalLabel) {
            elements.totalLabel.textContent = String(total);
        }

        if (elements.progressBar) {
            elements.progressBar.style.width = progress + '%';
        }

        if (elements.activeTitle) {
            elements.activeTitle.textContent = activePanel.dataset.wizardTitle || '';
        }

        if (elements.prevButton) {
            elements.prevButton.disabled = boundedIndex === 0;
        }

        if (elements.nextButton) {
            elements.nextButton.classList.toggle('d-none', boundedIndex === total - 1);
        }

        markWizardErrors(elements);
        updateWizardAccessState(elements);

        if (options && options.scroll) {
            elements.root.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    function initWizard() {
        var elements = wizardElements();

        if (!elements || !elements.panels.length) {
            return;
        }

        elements.panels.forEach(function (panel, index) {
            var step = wizardStepForPanel(elements, panel);
            var panelId = panel.id || 'cvWizardPanel' + (index + 1);

            panel.id = panelId;
            panel.setAttribute('role', 'tabpanel');

            if (step) {
                step.setAttribute('role', 'tab');
                step.setAttribute('aria-controls', panelId);
            }
        });

        var errorIndex = firstWizardErrorIndex(elements);
        var initialIndex = initialWizardIndex(elements);
        var requestedIndex = errorIndex > -1 ? errorIndex : Math.max(initialIndex, 0);
        var reachableIndex = highestReachableWizardIndex(elements);

        setWizardStep(Math.min(requestedIndex, reachableIndex), {
            scroll: errorIndex > -1,
        });
    }

    function photoElements() {
        return {
            input: document.querySelector('[data-photo-input]'),
            remove: document.querySelector('[data-photo-remove]'),
            frame: document.querySelector('[data-photo-frame]'),
            preview: document.querySelector('[data-photo-preview]'),
            placeholder: document.querySelector('[data-photo-placeholder]'),
            cropModal: document.getElementById('photoCropModal'),
            cropImage: document.querySelector('[data-photo-crop-image]'),
        };
    }

    function setPhotoPreview(src) {
        var elements = photoElements();

        if (!elements.frame || !elements.preview) {
            return;
        }

        if (previewPhotoObjectUrl && previewPhotoObjectUrl !== src) {
            URL.revokeObjectURL(previewPhotoObjectUrl);
            previewPhotoObjectUrl = null;
        }

        if (src) {
            if (src.indexOf('blob:') === 0) {
                previewPhotoObjectUrl = src;
            }

            elements.preview.src = src;
            elements.preview.classList.remove('d-none');
            elements.frame.classList.remove('is-empty');
            elements.frame.classList.add('has-photo');

            if (elements.placeholder) {
                elements.placeholder.classList.add('d-none');
            }

            return;
        }

        elements.preview.removeAttribute('src');
        elements.preview.classList.add('d-none');
        elements.frame.classList.add('is-empty');
        elements.frame.classList.remove('has-photo');

        if (elements.placeholder) {
            elements.placeholder.classList.remove('d-none');
        }
    }

    function ensurePhotoCropModal() {
        var elements = photoElements();

        if (!elements.cropModal || !window.bootstrap || !window.Cropper) {
            return null;
        }

        if (!photoCropModal) {
            photoCropModal = new bootstrap.Modal(elements.cropModal);
        }

        return photoCropModal;
    }

    function destroyPhotoCropper() {
        if (photoCropper) {
            photoCropper.destroy();
            photoCropper = null;
        }

        if (pendingPhotoObjectUrl) {
            URL.revokeObjectURL(pendingPhotoObjectUrl);
            pendingPhotoObjectUrl = null;
        }
    }

    function croppedFileName(file) {
        var baseName = file.name.replace(/\.[^.]+$/, '') || 'foto-cv';

        return baseName + '-cropped.jpg';
    }

    function assignPhotoFile(input, file) {
        if (window.DataTransfer) {
            var dataTransfer = new DataTransfer();
            dataTransfer.items.add(file);
            input.files = dataTransfer.files;
        }
    }

    function applyPhotoInputPreview(input) {
        var elements = photoElements();
        var file = input.files && input.files.length ? input.files[0] : null;

        if (!file) {
            return;
        }

        if (elements.remove) {
            elements.remove.checked = false;
        }

        setPhotoPreview(URL.createObjectURL(file));
    }

    function openPhotoCrop(input) {
        var elements = photoElements();
        var file = input.files && input.files.length ? input.files[0] : null;
        var modal = ensurePhotoCropModal();

        if (!file) {
            return;
        }

        if (!modal || !elements.cropImage) {
            applyPhotoInputPreview(input);
            return;
        }

        destroyPhotoCropper();

        pendingPhotoInput = input;
        pendingPhotoApplied = false;
        pendingPhotoObjectUrl = URL.createObjectURL(file);
        elements.cropImage.src = pendingPhotoObjectUrl;

        elements.cropImage.onload = function () {
            if (photoCropper) {
                photoCropper.destroy();
            }

            photoCropper = new Cropper(elements.cropImage, {
                aspectRatio: 4 / 5,
                viewMode: 1,
                dragMode: 'move',
                autoCropArea: 0.94,
                background: false,
                responsive: true,
                checkOrientation: true,
                minContainerWidth: 720,
                minContainerHeight: 520,
                minCropBoxWidth: 240,
                minCropBoxHeight: 300,
            });
        };

        modal.show();

        elements.cropModal.addEventListener('shown.bs.modal', function handleShown() {
            elements.cropModal.removeEventListener('shown.bs.modal', handleShown);

            if (photoCropper) {
                photoCropper.reset();
                photoCropper.crop();
            }
        });
    }

    function applyPhotoCrop() {
        if (!photoCropper || !pendingPhotoInput) {
            return;
        }

        var sourceFile = pendingPhotoInput.files && pendingPhotoInput.files.length ? pendingPhotoInput.files[0] : null;
        var canvas = photoCropper.getCroppedCanvas({
            width: 480,
            height: 600,
            fillColor: '#ffffff',
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high',
        });

        if (!canvas || !sourceFile) {
            return;
        }

        canvas.toBlob(function (blob) {
            if (!blob) {
                return;
            }

            var croppedFile = new File([blob], croppedFileName(sourceFile), {
                type: 'image/jpeg',
                lastModified: Date.now(),
            });

            assignPhotoFile(pendingPhotoInput, croppedFile);

            if (photoElements().remove) {
                photoElements().remove.checked = false;
            }

            pendingPhotoApplied = true;
            setPhotoPreview(URL.createObjectURL(croppedFile));

            if (photoCropModal) {
                photoCropModal.hide();
            }
        }, 'image/jpeg', 0.9);
    }

    function runPhotoCropAction(action) {
        if (!photoCropper) {
            return;
        }

        if (action === 'zoom-in') {
            photoCropper.zoom(0.1);
        }

        if (action === 'zoom-out') {
            photoCropper.zoom(-0.1);
        }

        if (action === 'rotate-left') {
            photoCropper.rotate(-90);
        }

        if (action === 'rotate-right') {
            photoCropper.rotate(90);
        }

        if (action === 'reset') {
            photoCropper.reset();
        }
    }

    document.addEventListener('click', function (event) {
        var guideButton = event.target.closest('[data-guide-start]');
        var wizardStepButton = event.target.closest('[data-wizard-step-target]');
        var wizardPrevButton = event.target.closest('[data-wizard-prev]');
        var wizardNextButton = event.target.closest('[data-wizard-next]');
        var addButton = event.target.closest('[data-repeat-add]');
        var removeButton = event.target.closest('[data-repeat-remove]');

        if (guideButton) {
            var tooltip = window.bootstrap && window.bootstrap.Tooltip
                ? bootstrap.Tooltip.getInstance(guideButton)
                : null;

            event.preventDefault();

            if (tooltip) {
                tooltip.hide();
            }

            startCvGuide(false);
            return;
        }

        if (wizardStepButton) {
            var stepElements = wizardElements();
            var targetIndex = stepElements ? wizardPanelIndexByKey(stepElements.panels, wizardStepButton.dataset.wizardStepTarget) : -1;

            event.preventDefault();

            if (targetIndex > -1) {
                goToWizardStep(targetIndex, { scroll: true });
            }

            return;
        }

        if (wizardPrevButton) {
            event.preventDefault();
            goToWizardStep(activeWizardIndex - 1, { scroll: true });
            return;
        }

        if (wizardNextButton) {
            event.preventDefault();
            goToWizardStep(activeWizardIndex + 1, { scroll: true });
            return;
        }

        if (addButton) {
            addRepeatItem(addButton.dataset.repeatAdd);
            refreshWizardAccessState();
        }

        if (removeButton) {
            removeRepeatItem(removeButton);
            refreshWizardAccessState();
        }

        if (event.target.closest('[data-photo-crop-apply]')) {
            applyPhotoCrop();
        }

        var cropActionButton = event.target.closest('[data-photo-crop-action]');

        if (cropActionButton) {
            runPhotoCropAction(cropActionButton.dataset.photoCropAction);
        }
    });

    document.addEventListener('change', function (event) {
        if (event.target.matches('#cvForm input, #cvForm textarea, #cvForm select')) {
            clearFieldWizardError(event.target);
        }

        if (event.target.matches('[data-current-checkbox]')) {
            applyCurrentToggles(event.target.closest('[data-repeat-item]'));

            if (event.target.closest('[data-repeat-item]')) {
                clearFieldWizardError(event.target.closest('[data-repeat-item]').querySelector('[data-current-target]'));
            }
        }

        if (event.target.matches('[data-location-child]')) {
            loadLocationChild(event.target);
        }

        if (event.target.matches('[data-organization-target]')) {
            syncOrganizationChoice(event.target);
        }

        if (event.target.matches('[data-organization-child]')) {
            loadOrganizationChild(event.target);
        }

        if (event.target.matches('[data-photo-remove]')) {
            setPhotoPreview(event.target.checked ? null : (photoElements().frame.dataset.photoOriginal || null));
        }

        if (event.target.matches('[data-photo-input]')) {
            openPhotoCrop(event.target);
        }

        refreshWizardAccessState();
    });

    document.addEventListener('input', function (event) {
        if (event.target.matches('#cvForm input, #cvForm textarea, #cvForm select')) {
            clearFieldWizardError(event.target);
            refreshWizardAccessState();
        }

        if (event.target.matches('[data-organization-custom-input]')) {
            syncOrganizationCustomInput(event.target);
        }

        if (event.target.matches('.js-countable')) {
            updateCounters();
        }
    });

    document.addEventListener('focusin', function (event) {
        if (isFocusableCvField(event.target)) {
            applyFieldFocusMode(event.target);
        }
    });

    document.addEventListener('focusout', function () {
        window.setTimeout(function () {
            var form = document.getElementById('cvForm');
            var activeElement = document.activeElement;

            if (!form || !activeElement || !form.contains(activeElement) || !isFocusableCvField(activeElement)) {
                clearFieldFocusMode(form);
            }
        }, 0);
    });

    document.addEventListener('DOMContentLoaded', function () {
        applyCurrentToggles(document);
        initOrganizationFields();
        initGuideTooltips();
        updateCounters();
        initWizard();

        if (shouldAutoStartGuide()) {
            window.setTimeout(function () {
                startCvGuide(true);
            }, 500);
        }

        var elements = photoElements();

        if (elements.cropModal) {
            elements.cropModal.addEventListener('hidden.bs.modal', function () {
                if (!pendingPhotoApplied && pendingPhotoInput) {
                    pendingPhotoInput.value = '';
                }

                destroyPhotoCropper();

                if (elements.cropImage) {
                    elements.cropImage.removeAttribute('src');
                }

                pendingPhotoInput = null;
                pendingPhotoApplied = false;
            });
        }
    });
})();
