(function () {
    var repeatIndex = null;
    var photoCropper = null;
    var photoCropModal = null;
    var pendingPhotoInput = null;
    var pendingPhotoObjectUrl = null;
    var pendingPhotoApplied = false;
    var previewPhotoObjectUrl = null;
    var activeWizardIndex = 0;

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
        initRichTextEditors(item);
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

        container.querySelectorAll('[data-rich-text-input]').forEach(function (input) {
            var editor = input.closest('[data-rich-text-editor]');

            input.innerHTML = '';
            syncRichTextEditor(editor);
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

    function richTextHasText(input) {
        return input.textContent.replace(/\u00a0/g, ' ').trim() !== '';
    }

    function setRichTextEmptyState(editor) {
        var input = editor ? editor.querySelector('[data-rich-text-input]') : null;

        if (!input) {
            return;
        }

        input.classList.toggle('is-empty', !richTextHasText(input));
    }

    function syncRichTextEditor(editor) {
        var input = editor ? editor.querySelector('[data-rich-text-input]') : null;
        var value = editor ? editor.querySelector('[data-rich-text-value]') : null;

        if (!input || !value) {
            return;
        }

        if (!richTextHasText(input)) {
            input.innerHTML = '';
        }

        value.value = input.innerHTML.trim();
        setRichTextEmptyState(editor);
    }

    function syncAllRichTextEditors(root) {
        (root || document).querySelectorAll('[data-rich-text-editor]').forEach(function (editor) {
            syncRichTextEditor(editor);
        });
    }

    function initRichTextEditors(root) {
        (root || document).querySelectorAll('[data-rich-text-editor]').forEach(function (editor) {
            var input = editor.querySelector('[data-rich-text-input]');
            var value = editor.querySelector('[data-rich-text-value]');

            if (!input || !value || editor.dataset.richTextReady === '1') {
                return;
            }

            editor.dataset.richTextReady = '1';

            if (!input.innerHTML.trim() && value.value) {
                input.innerHTML = value.value;
            }

            setRichTextEmptyState(editor);
        });
    }

    function runRichTextCommand(button) {
        var editor = button.closest('[data-rich-text-editor]');
        var input = editor ? editor.querySelector('[data-rich-text-input]') : null;
        var command = button.dataset.richTextCommand;

        if (!input || !command) {
            return;
        }

        input.focus();

        try {
            document.execCommand('styleWithCSS', false, false);
            document.execCommand('defaultParagraphSeparator', false, 'p');
            document.execCommand(command, false, null);
        } catch (error) {
            return;
        }

        syncRichTextEditor(editor);
    }

    function pastePlainText(input, text) {
        var editor = input.closest('[data-rich-text-editor]');

        input.focus();

        try {
            document.execCommand('insertText', false, text);
        } catch (error) {
            input.textContent += text;
        }

        syncRichTextEditor(editor);
    }

    function updateCounters() {
        document.querySelectorAll('.js-countable').forEach(function (field) {
            var counter = document.querySelector(field.dataset.counter);

            if (counter) {
                counter.textContent = field.value.length;
            }
        });
    }

    function isFocusableCvField(element) {
        return element.matches('#cvForm input:not([type="hidden"]), #cvForm textarea, #cvForm select, #cvForm [contenteditable="true"]');
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

            step.classList.toggle('is-active', isActive);
            step.classList.toggle('is-complete', targetIndex > -1 && targetIndex < boundedIndex);
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

        setWizardStep(errorIndex > -1 ? errorIndex : Math.max(initialIndex, 0), {
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
        var wizardStepButton = event.target.closest('[data-wizard-step-target]');
        var wizardPrevButton = event.target.closest('[data-wizard-prev]');
        var wizardNextButton = event.target.closest('[data-wizard-next]');
        var addButton = event.target.closest('[data-repeat-add]');
        var removeButton = event.target.closest('[data-repeat-remove]');
        var richTextButton = event.target.closest('[data-rich-text-command]');

        if (richTextButton) {
            event.preventDefault();
            runRichTextCommand(richTextButton);
            return;
        }

        if (wizardStepButton) {
            var stepElements = wizardElements();
            var targetIndex = stepElements ? wizardPanelIndexByKey(stepElements.panels, wizardStepButton.dataset.wizardStepTarget) : -1;

            event.preventDefault();

            if (targetIndex > -1) {
                setWizardStep(targetIndex, { scroll: true });
            }

            return;
        }

        if (wizardPrevButton) {
            event.preventDefault();
            setWizardStep(activeWizardIndex - 1, { scroll: true });
            return;
        }

        if (wizardNextButton) {
            event.preventDefault();
            setWizardStep(activeWizardIndex + 1, { scroll: true });
            return;
        }

        if (addButton) {
            addRepeatItem(addButton.dataset.repeatAdd);
        }

        if (removeButton) {
            removeRepeatItem(removeButton);
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
        if (event.target.matches('[data-current-checkbox]')) {
            applyCurrentToggles(event.target.closest('[data-repeat-item]'));
        }

        if (event.target.matches('[data-location-child]')) {
            loadLocationChild(event.target);
        }

        if (event.target.matches('[data-photo-remove]')) {
            setPhotoPreview(event.target.checked ? null : (photoElements().frame.dataset.photoOriginal || null));
        }

        if (event.target.matches('[data-photo-input]')) {
            openPhotoCrop(event.target);
        }
    });

    document.addEventListener('input', function (event) {
        if (event.target.matches('.js-countable')) {
            updateCounters();
        }

        if (event.target.matches('[data-rich-text-input]')) {
            syncRichTextEditor(event.target.closest('[data-rich-text-editor]'));
        }
    });

    document.addEventListener('paste', function (event) {
        var input = event.target.closest('[data-rich-text-input]');

        if (!input) {
            return;
        }

        event.preventDefault();
        pastePlainText(input, (event.clipboardData || window.clipboardData).getData('text/plain'));
    });

    document.addEventListener('submit', function (event) {
        if (event.target.matches('#cvForm')) {
            syncAllRichTextEditors(event.target);
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
        initRichTextEditors(document);
        updateCounters();
        initWizard();

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
