(function () {
    var repeatIndex = null;
    var photoCropper = null;
    var photoCropModal = null;
    var pendingPhotoInput = null;
    var pendingPhotoObjectUrl = null;
    var pendingPhotoApplied = false;
    var previewPhotoObjectUrl = null;
    var activeWizardIndex = 0;
    var livePreviewEnabled = true;
    var livePreviewUpdateTimer = null;
    var guideReadTimer = null;
    var guideInitialWizardIndex = null;
    var GUIDE_STORAGE_KEY = 'vitae.cv-form-guide.seen.v5';
    var GUIDE_READ_DELAY_MS = 1400;
    var CV_GUIDE_STEPS = [
        {
            title: 'Isi CV Bertahap',
            selector: '[data-guide-target="wizard-steps"]',
            mobilePosition: 'bottom',
            icon: 'bi-list-check',
            eyebrow: 'Alur Pengisian',
            text: 'Isi CV dari kiri ke kanan supaya data yang masuk rapi dan mudah dicek.',
            tips: [
                'Step berikutnya akan terbuka setelah step sebelumnya lengkap.',
                'Klik nama step untuk kembali mengecek bagian yang sudah diisi.',
            ],
        },
        {
            title: 'Tombol Berikutnya dan Sebelumnya',
            selector: '[data-guide-target="wizard-next"]',
            mobilePosition: 'top',
            icon: 'bi-arrow-left-right',
            eyebrow: 'Navigasi',
            text: 'Gunakan tombol navigasi untuk pindah step dengan kontrol yang jelas.',
            tips: [
                'Berikutnya akan mengecek kelengkapan data sebelum pindah.',
                'Sebelumnya aman digunakan untuk memperbaiki data lama.',
            ],
        },
        {
            title: 'Tambah Pengalaman',
            panel: 'experience',
            selector: '[data-guide-target="add-experience"]',
            mobilePosition: 'top',
            icon: 'bi-plus-circle',
            eyebrow: 'Riwayat Kerja',
            text: 'Jika memiliki pengalaman kerja lebih dari satu, gunakan tombol Tambah di step Pengalaman.',
            tips: [
                'Satu pengalaman sebaiknya diisi dalam satu baris pengalaman.',
                'Departemen dan divisi pada pengalaman boleh ditulis bebas sesuai riwayat kerja.',
            ],
        },
        {
            title: 'Simpan Draft dan Preview',
            selector: '[data-guide-target="save-draft"]',
            mobilePosition: 'top',
            icon: 'bi-save2',
            eyebrow: 'Penyimpanan',
            text: 'Simpan pekerjaan Anda secara berkala agar data tidak hilang.',
            tips: [
                'Simpan Draft bisa digunakan walau semua step belum selesai.',
                'Simpan & Preview dipakai untuk mengecek tampilan CV sebelum download PDF.',
            ],
        },
    ];
    var WIZARD_VALIDATION_RULES = {
        personal: {
            fields: [
                { selector: '[name="full_name"]', label: 'Nama lengkap' },
                { selector: '[name="birth_date"]', label: 'Tanggal lahir' },
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
        syncCopyCurrentJobToggleAvailability();
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
            applyCurrentToggles(item);
            syncCopyCurrentJobToggleAvailability();
            return;
        }

        item.remove();
        syncCopyCurrentJobToggleAvailability();
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
        var workAreaSelect = document.querySelector('#workAreaSelect');
        var departmentSelect = document.querySelector('#departmentSelect');
        var divisionSelect = document.querySelector('#divisionSelect');
        var params = {};

        if (child && child.id === 'departmentSelect') {
            params.work_area = workAreaSelect ? workAreaSelect.value : parentSelect.value;
            return params;
        }

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

    function maritalStatusNeedsFamilyDetails(value) {
        value = String(value || '').trim().toLowerCase();

        return value !== '' && value.indexOf('belum') === -1;
    }

    function syncFamilyDetails() {
        var status = document.querySelector('[data-family-status]');
        var details = document.querySelector('[data-family-details]');
        var childrenToggle = details ? details.querySelector('[data-children-toggle]') : null;
        var childrenFields = details ? details.querySelector('[data-children-fields]') : null;
        var showDetails = maritalStatusNeedsFamilyDetails(status ? status.value : '');
        var showChildren = showDetails && childrenToggle && childrenToggle.checked;

        if (!details) {
            return;
        }

        details.hidden = !showDetails;

        details.querySelectorAll('input, textarea, select').forEach(function (field) {
            field.disabled = !showDetails;
        });

        if (!childrenFields) {
            return;
        }

        childrenFields.hidden = !showChildren;

        childrenFields.querySelectorAll('input, textarea, select').forEach(function (field) {
            field.disabled = !showChildren;
        });
    }

    function syncDomicileAddressFields() {
        var toggle = document.querySelector('[data-domicile-same-toggle]');
        var ktpField = document.querySelector('[data-ktp-address]');
        var domicileField = document.querySelector('[data-domicile-address]');
        var domicilePanel = document.querySelector('[data-domicile-address-panel]');
        var hasKtpAddress = ktpField && ktpField.value.trim() !== '';
        var useKtpAddress;

        if (!toggle || !ktpField || !domicileField) {
            return;
        }

        if (!hasKtpAddress) {
            toggle.checked = false;
        }

        toggle.disabled = !hasKtpAddress;
        useKtpAddress = toggle.checked && hasKtpAddress;

        if (useKtpAddress) {
            domicileField.value = ktpField.value;
            clearFieldWizardError(domicileField);
        }

        domicileField.readOnly = useKtpAddress;
        domicileField.classList.toggle('readonly-field', useKtpAddress);

        if (domicilePanel) {
            domicilePanel.classList.toggle('is-address-synced', useKtpAddress);
        }
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

    function currentJobExperience() {
        var form = document.getElementById('cvForm');

        if (!form) {
            return {};
        }

        return {
            position: form.dataset.currentJobPosition || '',
            company: form.dataset.currentJobCompany || '',
            department: form.dataset.currentJobDepartment || '',
            division: form.dataset.currentJobDivision || '',
            start_month: form.dataset.currentJobStartMonth || '',
        };
    }

    function currentJobHasValue(data) {
        return ['position', 'company', 'department', 'division', 'start_month'].some(function (key) {
            return data[key] && String(data[key]).trim() !== '';
        });
    }

    function normalizedCurrentJobValue(value) {
        return String(value || '').trim().replace(/\s+/g, ' ').toLowerCase();
    }

    function rowMatchesCurrentJob(row, data) {
        return ['position', 'company', 'department', 'division', 'start_month'].every(function (key) {
            var field = repeatField(row, key);

            return normalizedCurrentJobValue(field ? field.value : '') === normalizedCurrentJobValue(data[key]);
        });
    }

    function currentJobExistsInAnotherRow(currentRow, data) {
        var list = currentRow ? currentRow.closest('[data-repeat-list="experiences"]') : null;
        var rows = list ? Array.prototype.slice.call(list.querySelectorAll('[data-repeat-item]')) : [];

        return rows.some(function (row) {
            return row !== currentRow && rowMatchesCurrentJob(row, data);
        });
    }

    function setRepeatFieldValue(row, name, value) {
        var field = repeatField(row, name);

        if (!field) {
            return;
        }

        field.value = value || '';
        clearFieldWizardError(field);
    }

    function fillCurrentJobExperience(row) {
        var data = currentJobExperience();
        var currentCheckbox = repeatField(row, 'is_current');
        var endMonth = repeatField(row, 'end_month');

        setRepeatFieldValue(row, 'position', data.position);
        setRepeatFieldValue(row, 'company', data.company);
        setRepeatFieldValue(row, 'department', data.department);
        setRepeatFieldValue(row, 'division', data.division);
        setRepeatFieldValue(row, 'start_month', data.start_month);

        if (endMonth) {
            endMonth.value = '';
            clearFieldWizardError(endMonth);
        }

        if (currentCheckbox) {
            currentCheckbox.checked = true;
            clearFieldWizardError(currentCheckbox);
            applyCurrentToggles(row);
        }
    }

    function notifyCurrentJobUnavailable() {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'warning',
                title: 'Data V-People belum lengkap',
                text: 'Pekerjaan sekarang belum bisa dicopy karena data V-People tidak tersedia.',
                confirmButtonText: 'Mengerti',
            });
        }
    }

    function notifyCurrentJobDuplicate() {
        if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire({
                icon: 'warning',
                title: 'Riwayat sudah ada',
                text: 'Pekerjaan sekarang sudah ada di baris pengalaman lain. Autofill tidak dilakukan agar data tidak duplikat.',
                confirmButtonText: 'Mengerti',
            });
        }
    }

    function activeCopyCurrentJobToggle() {
        var activeToggle = document.querySelector('[data-copy-current-job][data-copy-current-active="1"]');

        if (activeToggle && !activeToggle.checked) {
            delete activeToggle.dataset.copyCurrentActive;
            return null;
        }

        return activeToggle;
    }

    function syncCopyCurrentJobToggleAvailability() {
        var activeToggle = activeCopyCurrentJobToggle();

        document.querySelectorAll('[data-copy-current-job]').forEach(function (toggle) {
            toggle.disabled = !!activeToggle && toggle !== activeToggle;
        });
    }

    function handleCopyCurrentJobToggle(toggle) {
        var row = toggle.closest('[data-repeat-item]');
        var data = currentJobExperience();
        var activeToggle = activeCopyCurrentJobToggle();

        if (!toggle.checked || !row) {
            delete toggle.dataset.copyCurrentActive;
            syncCopyCurrentJobToggleAvailability();
            return;
        }

        if (activeToggle && activeToggle !== toggle) {
            toggle.checked = false;
            delete toggle.dataset.copyCurrentActive;
            syncCopyCurrentJobToggleAvailability();
            return;
        }

        if (!currentJobHasValue(data)) {
            toggle.checked = false;
            delete toggle.dataset.copyCurrentActive;
            syncCopyCurrentJobToggleAvailability();
            notifyCurrentJobUnavailable();
            return;
        }

        if (currentJobExistsInAnotherRow(row, data)) {
            toggle.checked = false;
            delete toggle.dataset.copyCurrentActive;
            syncCopyCurrentJobToggleAvailability();
            notifyCurrentJobDuplicate();
            return;
        }

        toggle.dataset.copyCurrentActive = '1';
        fillCurrentJobExperience(row);
        syncCopyCurrentJobToggleAvailability();
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
        document.body.classList.remove('is-cv-guide-focus-mode');

        document.querySelectorAll('.cv-guide-focus-layer').forEach(function (element) {
            element.classList.remove('cv-guide-focus-layer');
        });

        document.querySelectorAll('.cv-guide-highlight').forEach(function (element) {
            element.classList.remove('cv-guide-highlight');

            if (element.dataset.guideTempTabindex === '1') {
                element.removeAttribute('tabindex');
                delete element.dataset.guideTempTabindex;
            }
        });
    }

    function isGuideTargetVisible(element) {
        return !!(element && element.getClientRects && element.getClientRects().length);
    }

    function applyGuideFocusLayer(target) {
        ['.app-savebar'].forEach(function (selector) {
            var layer = target.closest(selector);

            if (layer) {
                layer.classList.add('cv-guide-focus-layer');
            }
        });
    }

    function guideTargetForStep(step) {
        if (!step) {
            return null;
        }

        if (step.panel) {
            var elements = wizardElements();
            var panelIndex = elements ? wizardPanelIndexByKey(elements.panels, step.panel) : -1;

            if (panelIndex > -1) {
                setWizardStep(panelIndex, { scroll: false });
            }
        }

        return step.selector ? document.querySelector(step.selector) : null;
    }

    function focusGuideTarget(target) {
        if (!target || typeof target.focus !== 'function') {
            return;
        }

        if (!target.matches('button, a, input, select, textarea, [tabindex]')) {
            target.setAttribute('tabindex', '-1');
            target.dataset.guideTempTabindex = '1';
        }

        window.setTimeout(function () {
            target.focus({ preventScroll: true });
        }, 220);
    }

    function isMobileGuide() {
        return !!(window.matchMedia && window.matchMedia('(max-width: 768px)').matches);
    }

    function guidePopupPosition(step, target) {
        var rect = target && target.getBoundingClientRect ? target.getBoundingClientRect() : null;

        if (window.matchMedia && window.matchMedia('(max-width: 768px)').matches) {
            if (step.mobilePosition) {
                return step.mobilePosition;
            }

            return rect && rect.top < (window.innerHeight / 2) ? 'bottom' : 'top';
        }

        return 'top-end';
    }

    function scrollGuideTargetIntoView(target, popupPosition) {
        if (!target || !target.getBoundingClientRect || typeof window.scrollTo !== 'function') {
            return;
        }

        var previousHtmlScrollBehavior = document.documentElement.style.scrollBehavior;
        var previousBodyScrollBehavior = document.body.style.scrollBehavior;
        var rect = target.getBoundingClientRect();
        var currentScroll = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
        var scrollTop = currentScroll + rect.top - ((window.innerHeight - rect.height) / 2);

        if (isMobileGuide()) {
            scrollTop = popupPosition === 'top'
                ? currentScroll + rect.bottom - window.innerHeight + 176
                : currentScroll + rect.top - 24;
        }

        scrollTop = Math.max(0, scrollTop);
        document.documentElement.style.scrollBehavior = 'auto';
        document.body.style.scrollBehavior = 'auto';

        try {
            document.documentElement.scrollTop = scrollTop;
            document.body.scrollTop = scrollTop;
            window.scrollTo(0, scrollTop);
        } finally {
            document.documentElement.style.scrollBehavior = previousHtmlScrollBehavior;
            document.body.style.scrollBehavior = previousBodyScrollBehavior;
        }
    }

    function scheduleGuideTargetScroll(target, popupPosition) {
        scrollGuideTargetIntoView(target, popupPosition);

        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                scrollGuideTargetIntoView(target, popupPosition);
            });
            return;
        }

        window.setTimeout(function () {
            scrollGuideTargetIntoView(target, popupPosition);
        }, 0);
    }

    function highlightGuideTarget(target, popupPosition) {
        clearGuideHighlight();

        if (!isGuideTargetVisible(target)) {
            return;
        }

        target.classList.add('cv-guide-highlight');
        applyGuideFocusLayer(target);
        document.body.classList.add('is-cv-guide-focus-mode');
        scrollGuideTargetIntoView(target, popupPosition);
        focusGuideTarget(target);
    }

    function finishGuide(markSeen) {
        clearGuideHighlight();

        if (guideInitialWizardIndex !== null) {
            setWizardStep(guideInitialWizardIndex, { scroll: false });
            guideInitialWizardIndex = null;
        }

        if (markSeen) {
            storageSet(GUIDE_STORAGE_KEY, '1');
        }
    }

    function guideStepHtml(step, index) {
        var total = CV_GUIDE_STEPS.length;
        var progress = Math.round(((index + 1) / total) * 100);
        var tips = (step.tips || []).map(function (tip) {
            return '<li><i class="bi bi-check2-circle"></i><span>' + tip + '</span></li>';
        }).join('');

        return [
            '<div class="cv-guide-modal">',
            '<div class="cv-guide-progress" aria-hidden="true"><span style="width: ' + progress + '%"></span></div>',
            '<div class="cv-guide-step-count">Panduan ' + (index + 1) + ' dari ' + total + '</div>',
            '<div class="cv-guide-icon"><i class="bi ' + step.icon + '"></i></div>',
            '<div class="cv-guide-eyebrow">' + step.eyebrow + '</div>',
            '<p class="cv-guide-text">' + step.text + '</p>',
            '<ul class="cv-guide-tips">' + tips + '</ul>',
            '<div class="cv-guide-read-hint" data-guide-read-hint>Mohon baca sebentar, tombol lanjut akan aktif otomatis.</div>',
            '</div>',
        ].join('');
    }

    function holdGuideConfirmButton(isLastStep) {
        var confirmButton = window.Swal.getConfirmButton();
        var hint = document.querySelector('[data-guide-read-hint]');
        var remaining = Math.ceil(GUIDE_READ_DELAY_MS / 1000);

        if (!confirmButton) {
            return;
        }

        if (guideReadTimer) {
            window.clearInterval(guideReadTimer);
            guideReadTimer = null;
        }

        confirmButton.disabled = true;
        confirmButton.classList.add('disabled');
        confirmButton.textContent = (isLastStep ? 'Selesai' : 'Lanjut') + ' (' + remaining + ')';

        guideReadTimer = window.setInterval(function () {
            remaining -= 1;

            if (remaining > 0) {
                confirmButton.textContent = (isLastStep ? 'Selesai' : 'Lanjut') + ' (' + remaining + ')';
                return;
            }

            window.clearInterval(guideReadTimer);
            guideReadTimer = null;
            confirmButton.disabled = false;
            confirmButton.classList.remove('disabled');
            confirmButton.textContent = isLastStep ? 'Selesai' : 'Lanjut';

            if (hint) {
                hint.textContent = 'Silakan lanjut setelah memahami bagian ini.';
            }
        }, 1000);
    }

    function showGuideStep(index, markSeenOnClose) {
        var step = CV_GUIDE_STEPS[index];
        var isLastStep = index >= CV_GUIDE_STEPS.length - 1;

        if (!step || !window.Swal || typeof window.Swal.fire !== 'function') {
            finishGuide(markSeenOnClose);
            return;
        }

        var target = guideTargetForStep(step);
        var popupPosition = guidePopupPosition(step, target);

        highlightGuideTarget(target, popupPosition);

        window.setTimeout(function () {
            window.Swal.fire({
                title: step.title,
                html: guideStepHtml(step, index),
                position: popupPosition,
                backdrop: false,
                confirmButtonText: isLastStep ? 'Selesai' : 'Lanjut',
                denyButtonText: 'Kembali',
                cancelButtonText: 'Tutup',
                showDenyButton: index > 0,
                showCancelButton: true,
                reverseButtons: true,
                allowOutsideClick: false,
                customClass: {
                    popup: 'cv-guide-swal',
                    htmlContainer: 'cv-guide-swal-html',
                },
                didOpen: function () {
                    holdGuideConfirmButton(isLastStep);
                    scheduleGuideTargetScroll(target, popupPosition);
                },
                willClose: function () {
                    if (guideReadTimer) {
                        window.clearInterval(guideReadTimer);
                        guideReadTimer = null;
                    }
                },
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
        }, 280);
    }

    function startCvGuide(markSeenOnClose) {
        guideInitialWizardIndex = activeWizardIndex;
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

    function validateRepeatRows(panel, type, fieldRules, options, errors) {
        var fieldNames = fieldRules.map(function (fieldRule) {
            return fieldRule.name;
        });

        repeatRows(panel, type).forEach(function (row) {
            if (!repeatRowHasValue(row, fieldNames)) {
                return;
            }

            fieldRules.forEach(function (fieldRule) {
                validateRequiredField(row, '[name$="[' + fieldRule.name + ']"]', fieldRule.label, options, errors);
            });
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

    function validateEmergencyContactsWizardPanel(panel, options, errors) {
        repeatRows(panel, 'emergency_contacts').forEach(function (row) {
            var started = repeatRowHasValue(row, ['phone', 'name', 'relationship']);

            if (!started) {
                return;
            }

            validateRequiredField(row, '[name$="[phone]"]', 'Nomor kontak darurat', options, errors);
            validateRequiredField(row, '[name$="[name]"]', 'Nama kontak darurat', options, errors);
            validateRequiredField(row, '[name$="[relationship]"]', 'Hubungan kontak darurat', options, errors);
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
            validateRequiredField(row, '[name$="[department]"]', 'Departemen', options, errors);
            validateRequiredField(row, '[name$="[division]"]', 'Divisi', options, errors);
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

    function validateExtrasWizardPanel(panel, options, errors) {
        validateRepeatRows(panel, 'languages', [
            { name: 'language', label: 'Bahasa' },
            { name: 'level', label: 'Tingkat bahasa' },
        ], options, errors);

        validateRepeatRows(panel, 'projects', [
            { name: 'name', label: 'Nama proyek' },
            { name: 'year', label: 'Tahun proyek' },
        ], options, errors);

        validateRepeatRows(panel, 'organizations', [
            { name: 'organization_name', label: 'Organisasi' },
            { name: 'role', label: 'Jabatan/peran organisasi' },
            { name: 'start_year', label: 'Tahun mulai organisasi' },
            { name: 'end_year', label: 'Tahun selesai organisasi' },
        ], options, errors);
    }

    function documentCardHasValidFile(card) {
        var input = card ? card.querySelector('input[type="file"]') : null;
        var remove = card ? card.querySelector('input[name^="remove_documents"]') : null;
        var hasExistingFile = card && card.dataset.documentHasFile === '1';
        var hasNewFile = input && fieldHasValue(input);

        return hasNewFile || (hasExistingFile && !(remove && remove.checked));
    }

    function validateDocumentsWizardPanel(panel, options, errors) {
        panel.querySelectorAll('[data-document-required="1"]').forEach(function (card) {
            var input = card.querySelector('input[type="file"]');
            var label = card.dataset.documentLabel || 'Dokumen wajib HR';

            if (documentCardHasValidFile(card)) {
                return;
            }

            addWizardFieldError(errors, input, requiredMessage(label), options);
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

        if (panelKey === 'personal') {
            validateEmergencyContactsWizardPanel(panel, options || {}, errors);
        }

        if (panelKey === 'education') {
            validateEducationWizardPanel(panel, options || {}, errors);
        }

        if (panelKey === 'experience') {
            validateExperienceWizardPanel(panel, options || {}, errors);
        }

        if (panelKey === 'certifications') {
            validateCertificationsWizardPanel(panel, options || {}, errors);
        }

        if (panelKey === 'extras') {
            validateExtrasWizardPanel(panel, options || {}, errors);
        }

        if (panelKey === 'documents' && !(options || {}).skipDocuments) {
            validateDocumentsWizardPanel(panel, options || {}, errors);
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

    function validateWizardPath(elements, targetIndex, options) {
        var result = null;
        var invalidIndex = -1;

        elements.panels.some(function (panel, index) {
            if (index >= targetIndex) {
                return true;
            }

            result = validateWizardPanel(panel, options || {});

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

    function submitterSkipsWizardValidation(event) {
        var submitter = event.submitter || document.activeElement;

        return !!(submitter && submitter.hasAttribute && submitter.hasAttribute('data-wizard-submit-skip-validation'));
    }

    function submitterSkipsDocumentsValidation(event) {
        var submitter = event.submitter || document.activeElement;

        return !!(submitter && submitter.hasAttribute && submitter.hasAttribute('data-wizard-submit-skip-documents'));
    }

    function validateWizardSubmit(event) {
        var elements = wizardElements();
        var options = submitterSkipsDocumentsValidation(event) ? { skipDocuments: true } : {};

        if (!event.target || event.target.id !== 'cvForm' || submitterSkipsWizardValidation(event) || !elements) {
            return;
        }

        if (validateWizardPath(elements, elements.panels.length, options)) {
            return;
        }

        event.preventDefault();
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

    function scrollActiveWizardStepIntoView(elements, activeKey) {
        var rail = elements && elements.root ? elements.root.querySelector('.cv-wizard-steps') : null;
        var activeStep = elements && elements.steps ? elements.steps.find(function (step) {
            return step.dataset.wizardStepTarget === activeKey;
        }) : null;
        var maxScroll = rail ? rail.scrollWidth - rail.clientWidth : 0;
        var targetLeft;
        var reducedMotionQuery;
        var behavior = 'smooth';

        if (!rail || !activeStep || maxScroll <= 0) {
            return;
        }

        targetLeft = activeStep.offsetLeft - ((rail.clientWidth - activeStep.offsetWidth) / 2);
        targetLeft = Math.max(0, Math.min(targetLeft, maxScroll));

        if (window.matchMedia) {
            reducedMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            behavior = reducedMotionQuery.matches ? 'auto' : 'smooth';
        }

        if (typeof rail.scrollTo === 'function') {
            rail.scrollTo({
                left: targetLeft,
                behavior: behavior,
            });
            return;
        }

        rail.scrollLeft = targetLeft;
    }

    function scheduleActiveWizardStepScroll(elements, activeKey) {
        if (typeof window.requestAnimationFrame === 'function') {
            window.requestAnimationFrame(function () {
                scrollActiveWizardStepIntoView(elements, activeKey);
            });
            return;
        }

        window.setTimeout(function () {
            scrollActiveWizardStepIntoView(elements, activeKey);
        }, 0);
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
        scheduleActiveWizardStepScroll(elements, activeKey);

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

    function livePreviewElements() {
        return {
            form: document.getElementById('cvForm'),
            toggle: document.querySelector('[data-live-preview-toggle]'),
            toggleText: document.querySelector('[data-live-preview-toggle-text]'),
            panel: document.querySelector('[data-live-preview-panel]'),
            output: document.querySelector('[data-live-preview-output]'),
            empty: document.querySelector('[data-live-preview-empty]'),
            status: document.querySelector('[data-live-preview-status]'),
        };
    }

    function initLivePreview() {
        var elements = livePreviewElements();

        if (!elements.toggle || !elements.panel || !elements.output) {
            return;
        }

        livePreviewEnabled = true;
        syncLivePreviewState(elements);
        updateLivePreview();
    }

    function syncLivePreviewState(elements) {
        if (!elements.toggle || !elements.panel) {
            return;
        }

        elements.panel.hidden = !livePreviewEnabled;
        elements.toggle.classList.toggle('btn-primary', livePreviewEnabled);
        elements.toggle.classList.toggle('btn-outline-primary', !livePreviewEnabled);
        elements.toggle.setAttribute('aria-expanded', livePreviewEnabled ? 'true' : 'false');
        elements.toggle.setAttribute('data-bs-title', livePreviewEnabled ? 'Sembunyikan preview CV dari isi form saat ini.' : 'Tampilkan preview CV dari isi form saat ini.');

        if (elements.toggleText) {
            elements.toggleText.textContent = livePreviewEnabled ? 'Sembunyikan Preview' : 'Tampilkan Preview';
        }

        if (elements.status) {
            elements.status.textContent = livePreviewEnabled ? 'Aktif' : 'Nonaktif';
        }
    }

    function toggleLivePreview() {
        var elements = livePreviewElements();

        if (!elements.toggle || !elements.panel) {
            return;
        }

        livePreviewEnabled = !livePreviewEnabled;
        syncLivePreviewState(elements);

        if (livePreviewEnabled) {
            updateLivePreview();
        }
    }

    function scheduleLivePreviewUpdate() {
        var elements = livePreviewElements();

        if (!livePreviewEnabled || !elements.output) {
            return;
        }

        if (elements.status) {
            elements.status.textContent = 'Memperbarui';
        }

        if (livePreviewUpdateTimer) {
            window.clearTimeout(livePreviewUpdateTimer);
        }

        livePreviewUpdateTimer = window.setTimeout(function () {
            livePreviewUpdateTimer = null;
            updateLivePreview();
        }, 120);
    }

    function updateLivePreview() {
        var elements = livePreviewElements();
        var data;

        if (!livePreviewEnabled || !elements.output) {
            return;
        }

        data = collectLivePreviewData();
        renderLivePreview(data);

        if (elements.status) {
            elements.status.textContent = 'Aktif';
        }
    }

    function collectLivePreviewData() {
        var elements = livePreviewElements();
        var form = elements.form;
        var location = cleanLivePreviewList([
            selectedLivePreviewOptionText('village_id'),
            selectedLivePreviewOptionText('district_id'),
            selectedLivePreviewOptionText('regency_id'),
            selectedLivePreviewOptionText('province_id'),
        ]);

        return {
            nik: form ? cleanLivePreviewText(form.dataset.livePreviewNik || '') : '',
            full_name: livePreviewFieldValue('full_name'),
            birth_place: livePreviewFieldValue('birth_place'),
            birth_date: formatLivePreviewDate(livePreviewFieldValue('birth_date')),
            gender: livePreviewGender(livePreviewFieldValue('gender')),
            marital_status: livePreviewFieldValue('marital_status'),
            address: cleanLivePreviewList([livePreviewFieldValue('address'), location.join(', ')]).join('\n'),
            phone: livePreviewFieldValue('phone'),
            email: livePreviewFieldValue('email'),
            photo: livePreviewPhotoSrc(),
            profile_summary: livePreviewFieldValue('profile_summary'),
            technical_skills: splitLivePreviewList(livePreviewFieldValue('technical_skills')),
            non_technical_skills: splitLivePreviewList(livePreviewFieldValue('non_technical_skills')),
            educations: livePreviewRows('educations', ['level', 'institution', 'major', 'graduation_year']).slice(0, 2),
            experiences: livePreviewRows('experiences', ['position', 'company', 'department', 'division', 'start_month', 'end_month', 'is_current', 'responsibilities']),
            certifications: livePreviewRows('certifications', ['name', 'issuer', 'year', 'valid_until_year', 'is_lifetime', 'type']),
            languages: livePreviewRows('languages', ['language', 'level']),
            projects: livePreviewRows('projects', ['name', 'year']),
            organizations: livePreviewRows('organizations', ['organization_name', 'role', 'start_year', 'end_year']),
        };
    }

    function renderLivePreview(data) {
        var elements = livePreviewElements();
        var sections = [
            renderLivePreviewSection('Ringkasan Profil', data.profile_summary ? '<p>' + escapeHtml(data.profile_summary) + '</p>' : ''),
            renderLivePreviewSection('Pendidikan', renderLivePreviewEducations(data.educations)),
            renderLivePreviewSection('Pengalaman Kerja', renderLivePreviewExperiences(data.experiences)),
            renderLivePreviewSection('Keahlian', renderLivePreviewSkills(data)),
            renderLivePreviewSection('Sertifikasi & Pelatihan', renderLivePreviewCertifications(data.certifications)),
            renderLivePreviewSection('Tambahan', renderLivePreviewExtras(data)),
        ].join('');

        if (!elements.output) {
            return;
        }

        elements.output.innerHTML = [
            '<article class="cv-paper cv-live-preview-paper">',
            renderLivePreviewHeader(data),
            sections,
            '</article>',
        ].join('');

        if (elements.empty) {
            elements.empty.hidden = true;
        }
    }

    function renderLivePreviewHeader(data) {
        var name = data.full_name || 'Nama belum diisi';
        var birth = (data.birth_place || 'Tempat lahir belum diisi') + ', ' + (data.birth_date || '-');
        var meta = [
            'NIK: ' + (data.nik || '-'),
            birth,
            data.gender || '-',
            data.marital_status || '-',
        ].map(function (item) {
            return escapeHtml(item);
        }).join('<span>|</span>');
        var photo = data.photo
            ? '<img src="' + escapeHtml(data.photo) + '" alt="Foto ' + escapeHtml(name) + '">'
            : '';

        return [
            '<header class="cv-output-header">',
            '<div class="cv-output-header-grid">',
            '<div class="cv-output-header-main">',
            '<h1>' + escapeHtml(name) + '</h1>',
            '<p class="cv-output-meta">' + meta + '</p>',
            '<p class="cv-output-contact">' + nl2br(escapeHtml(data.address || 'Alamat belum diisi')) + '</p>',
            '<p class="cv-output-contact">' + escapeHtml(data.phone || 'No. HP belum diisi') + '<span>|</span>' + escapeHtml(data.email || 'Email belum diisi') + '</p>',
            '</div>',
            '<div class="cv-output-photo-frame ' + (data.photo ? 'has-photo' : 'is-empty') + '">',
            photo,
            '</div>',
            '</div>',
            '</header>',
        ].join('');
    }

    function renderLivePreviewSection(title, bodyHtml) {
        if (!bodyHtml) {
            return '';
        }

        return '<section class="cv-output-section"><h2>' + escapeHtml(title) + '</h2>' + bodyHtml + '</section>';
    }

    function renderLivePreviewEducations(rows) {
        return rows.map(function (education) {
            var meta = cleanLivePreviewList([
                education.institution || 'Institusi belum diisi',
                education.major,
                education.graduation_year,
            ]).map(escapeHtml).join('<span>|</span>');

            return [
                '<div class="cv-output-entry">',
                '<h3>' + escapeHtml(education.level || 'Jenjang belum diisi') + '</h3>',
                '<p class="cv-output-meta">' + meta + '</p>',
                '</div>',
            ].join('');
        }).join('');
    }

    function renderLivePreviewExperiences(rows) {
        return rows.map(function (experience) {
            var meta = cleanLivePreviewList([
                experience.company || 'Perusahaan belum diisi',
                experience.department,
                experience.division,
                livePreviewPeriod(experience.start_month, experience.end_month, !!experience.is_current),
            ]).map(escapeHtml).join('<span>|</span>');
            var responsibilities = livePreviewResponsibilitiesHtml(experience.responsibilities);

            return [
                '<div class="cv-output-entry">',
                '<h3>' + escapeHtml(experience.position || 'Nama posisi belum diisi') + '</h3>',
                '<p class="cv-output-meta">' + meta + '</p>',
                responsibilities ? '<div class="cv-output-rich-text">' + responsibilities + '</div>' : '',
                '</div>',
            ].join('');
        }).join('');
    }

    function renderLivePreviewSkills(data) {
        var items = [];

        if (data.technical_skills.length) {
            items.push('<p><strong>Teknis:</strong> ' + escapeHtml(data.technical_skills.join(', ')) + '</p>');
        }

        if (data.non_technical_skills.length) {
            items.push('<p><strong>Non-teknis:</strong> ' + escapeHtml(data.non_technical_skills.join(', ')) + '</p>');
        }

        return items.join('');
    }

    function renderLivePreviewCertifications(rows) {
        if (!rows.length) {
            return '';
        }

        return [
            '<div class="table-responsive"><table class="cv-output-table">',
            '<thead><tr><th>Nama</th><th>Penerbit/Penyelenggara</th><th>Tahun</th><th>Berlaku s/d</th><th>Jenis</th></tr></thead>',
            '<tbody>',
            rows.map(function (certification) {
                return [
                    '<tr>',
                    '<td>' + escapeHtml(certification.name || '-') + '</td>',
                    '<td>' + escapeHtml(certification.issuer || '-') + '</td>',
                    '<td>' + escapeHtml(certification.year || '-') + '</td>',
                    '<td>' + escapeHtml(certification.is_lifetime ? 'Seumur hidup' : (certification.valid_until_year || '-')) + '</td>',
                    '<td>' + escapeHtml(certification.type || '-') + '</td>',
                    '</tr>',
                ].join('');
            }).join(''),
            '</tbody></table></div>',
        ].join('');
    }

    function renderLivePreviewExtras(data) {
        var blocks = [];

        if (data.languages.length) {
            blocks.push('<p><strong>Bahasa:</strong> ' + escapeHtml(data.languages.map(function (language) {
                return language.language + (language.level ? ' (' + language.level + ')' : '');
            }).join(', ')) + '</p>');
        }

        if (data.projects.length) {
            blocks.push('<p><strong>Proyek:</strong> ' + escapeHtml(data.projects.map(function (project) {
                return project.name + (project.year ? ' (' + project.year + ')' : '');
            }).join(', ')) + '</p>');
        }

        if (data.organizations.length) {
            blocks.push('<p><strong>Organisasi:</strong> ' + escapeHtml(data.organizations.map(function (organization) {
                var role = organization.role ? organization.role + ', ' : '';
                var period = livePreviewYearPeriod(organization.start_year, organization.end_year);

                return role + organization.organization_name + (period ? ' (' + period + ')' : '');
            }).join(', ')) + '</p>');
        }

        return blocks.join('');
    }

    function livePreviewRows(type, fields) {
        var rows = Array.prototype.slice.call(document.querySelectorAll('[data-repeat-list="' + type + '"] [data-repeat-item]'));

        return rows.map(function (row) {
            var item = {};

            fields.forEach(function (fieldName) {
                var field = repeatField(row, fieldName);

                item[fieldName] = field && field.type === 'checkbox'
                    ? field.checked
                    : (fieldName === 'responsibilities'
                        ? cleanLivePreviewMultilineText(fieldValue(field))
                        : cleanLivePreviewText(fieldValue(field)));
            });

            return item;
        }).filter(function (item) {
            return fields.some(function (fieldName) {
                return item[fieldName] === true || String(item[fieldName] || '').trim() !== '';
            });
        });
    }

    function livePreviewFieldValue(name) {
        var field = document.querySelector('[name="' + name + '"]');

        return cleanLivePreviewText(fieldValue(field));
    }

    function selectedLivePreviewOptionText(name) {
        var select = document.querySelector('[name="' + name + '"]');
        var option = select && select.selectedOptions && select.selectedOptions.length ? select.selectedOptions[0] : null;

        if (!select || !select.value || !option) {
            return '';
        }

        return cleanLivePreviewText(option.textContent || '');
    }

    function livePreviewPhotoSrc() {
        var preview = document.querySelector('[data-photo-preview]');

        if (!preview || preview.classList.contains('d-none')) {
            return '';
        }

        return preview.getAttribute('src') || '';
    }

    function splitLivePreviewList(value) {
        if (!value) {
            return [];
        }

        return cleanLivePreviewList(value.split(/[,;\n]+/));
    }

    function cleanLivePreviewList(items) {
        return items.map(cleanLivePreviewText).filter(function (item) {
            return item !== '';
        });
    }

    function cleanLivePreviewText(value) {
        value = String(value || '').replace(/[\u3400-\u9FFF\uF900-\uFAFF]+/g, '');
        value = value.replace(/\s+/g, ' ').trim();

        return value;
    }

    function cleanLivePreviewMultilineText(value) {
        value = String(value || '').replace(/[\u3400-\u9FFF\uF900-\uFAFF]+/g, '');

        return value.split(/\n+/).map(cleanLivePreviewText).filter(function (line) {
            return line !== '';
        }).join('\n');
    }

    function formatLivePreviewDate(value) {
        var parts = String(value || '').split('-');

        if (parts.length !== 3) {
            return '';
        }

        return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    function livePreviewGender(value) {
        if (value === 'L') {
            return 'Laki-laki';
        }

        if (value === 'P') {
            return 'Perempuan';
        }

        return value;
    }

    function livePreviewPeriod(startMonth, endMonth, isCurrent) {
        var start = formatLivePreviewMonth(startMonth);

        if (!start && !endMonth) {
            return '';
        }

        return (start || '-') + ' - ' + (isCurrent ? 'Sekarang' : (formatLivePreviewMonth(endMonth) || '-'));
    }

    function livePreviewYearPeriod(startYear, endYear) {
        if (!startYear && !endYear) {
            return '';
        }

        return (startYear || '-') + ' - ' + (endYear || 'Sekarang');
    }

    function formatLivePreviewMonth(value) {
        var parts = String(value || '').split('-');
        var months = [
            'Januari',
            'Februari',
            'Maret',
            'April',
            'Mei',
            'Juni',
            'Juli',
            'Agustus',
            'September',
            'Oktober',
            'November',
            'Desember',
        ];
        var monthIndex;

        if (parts.length !== 2) {
            return '';
        }

        monthIndex = parseInt(parts[1], 10) - 1;

        if (monthIndex < 0 || monthIndex > 11) {
            return '';
        }

        return months[monthIndex] + ' ' + parts[0];
    }

    function livePreviewResponsibilitiesHtml(value) {
        var lines = cleanLivePreviewList(String(value || '').split(/\n+/));

        if (!lines.length) {
            return '';
        }

        return '<ul>' + lines.map(function (line) {
            return '<li>' + escapeHtml(line) + '</li>';
        }).join('') + '</ul>';
    }

    function nl2br(value) {
        return String(value || '').replace(/\n/g, '<br>');
    }

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
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

            scheduleLivePreviewUpdate();
            return;
        }

        elements.preview.removeAttribute('src');
        elements.preview.classList.add('d-none');
        elements.frame.classList.add('is-empty');
        elements.frame.classList.remove('has-photo');

        if (elements.placeholder) {
            elements.placeholder.classList.remove('d-none');
        }

        scheduleLivePreviewUpdate();
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
        var livePreviewToggle = event.target.closest('[data-live-preview-toggle]');
        var guideButton = event.target.closest('[data-guide-start]');
        var wizardStepButton = event.target.closest('[data-wizard-step-target]');
        var wizardPrevButton = event.target.closest('[data-wizard-prev]');
        var wizardNextButton = event.target.closest('[data-wizard-next]');
        var addButton = event.target.closest('[data-repeat-add]');
        var removeButton = event.target.closest('[data-repeat-remove]');

        if (livePreviewToggle) {
            event.preventDefault();
            toggleLivePreview();
            return;
        }

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
            scheduleLivePreviewUpdate();
        }

        if (removeButton) {
            removeRepeatItem(removeButton);
            refreshWizardAccessState();
            scheduleLivePreviewUpdate();
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

        if (event.target.matches('[data-copy-current-job]')) {
            handleCopyCurrentJobToggle(event.target);
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

        if (event.target.matches('[data-family-status], [data-children-toggle]')) {
            syncFamilyDetails();
        }

        if (event.target.matches('[data-domicile-same-toggle]')) {
            syncDomicileAddressFields();
        }

        if (event.target.matches('[data-photo-remove]')) {
            setPhotoPreview(event.target.checked ? null : (photoElements().frame.dataset.photoOriginal || null));
        }

        if (event.target.matches('[data-photo-input]')) {
            openPhotoCrop(event.target);
        }

        refreshWizardAccessState();
        scheduleLivePreviewUpdate();
    });

    document.addEventListener('input', function (event) {
        if (event.target.matches('#cvForm input, #cvForm textarea, #cvForm select')) {
            clearFieldWizardError(event.target);
            refreshWizardAccessState();
        }

        if (event.target.matches('[data-organization-custom-input]')) {
            syncOrganizationCustomInput(event.target);
        }

        if (event.target.matches('[data-ktp-address]')) {
            syncDomicileAddressFields();
        }

        if (event.target.matches('.js-countable')) {
            updateCounters();
        }

        scheduleLivePreviewUpdate();
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

    document.addEventListener('submit', function (event) {
        validateWizardSubmit(event);
    });

    document.addEventListener('DOMContentLoaded', function () {
        applyCurrentToggles(document);
        syncCopyCurrentJobToggleAvailability();
        initOrganizationFields();
        syncFamilyDetails();
        syncDomicileAddressFields();
        initGuideTooltips();
        updateCounters();
        initWizard();
        initLivePreview();

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
