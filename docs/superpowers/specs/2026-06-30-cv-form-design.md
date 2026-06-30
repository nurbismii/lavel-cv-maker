# CV Form Design Refresh

## Goal

Improve the visual quality and usability of the CV completion form without changing its business logic. The form should feel like a focused HR work tool: professional, compact, easy to scan, accessible, and stable on mobile.

## Scope

- Refresh only the `Form CV` page rendered by `resources/views/cv/edit.blade.php`.
- Prefer CSS-only improvements in `public/css/app.css`.
- Touch Blade markup only when a small class or structure adjustment is required for layout clarity.
- Keep the existing Bootstrap, Bootstrap Icons, CropperJS, SweetAlert, and plain JavaScript stack.
- Keep all existing wizard behavior, validation, document upload, photo crop, preview save, and draft save flows.

## Non-Goals

- No backend changes.
- No route, controller, model, request validation, migration, or storage changes.
- No replacement of Bootstrap with a new UI framework.
- No redesign of dashboard, login, register, preview, PDF output, or email templates in this phase.
- No full component extraction unless a small markup adjustment is needed for responsive layout.

## Design Direction

Use a professional utilitarian style based on the installed UI/UX guidance:

- Keep the existing Vitae teal identity as the primary color.
- Use a light, neutral background with clear surface hierarchy.
- Reduce decorative shadows and large radius values.
- Favor readable density, predictable alignment, and clear state differences.
- Use border, spacing, and type hierarchy instead of heavy card nesting.
- Keep icons from Bootstrap Icons for consistency with the current project.

## Layout Decisions

### Page Structure

The form remains a two-column layout on desktop:

- Main column: wizard header and active form panel.
- Side column: completion summary, checklist, preview/PDF actions.

On tablet and mobile, the layout stacks vertically with the form first and the summary below.

### Wizard Header

The wizard remains the main navigation for the form. The refresh should make it more compact and easier to scan:

- Active step has strong border and subtle teal background.
- Completed step uses success state with a clear check-like visual through existing icon/number styling.
- Error step uses danger border/background and remains visually distinct from active and completed states.
- Locked step appears muted and non-interactive.
- Step cards keep stable dimensions so labels do not shift layout.

### Form Panels

Each wizard panel keeps the current card structure but becomes visually lighter:

- Card radius reduced to a tighter professional radius.
- Shadows reduced or removed in favor of border separation.
- Header and body spacing adjusted to keep dense forms readable.
- Field groups inside cards become lightweight sections, not heavy nested cards.

### Fields

Form fields should be consistent and accessible:

- Maintain visible labels for every input.
- Keep minimum touch-friendly height.
- Make focus states visible with teal ring and border.
- Keep readonly fields visually distinct from disabled fields.
- Preserve inline validation feedback near the related field.

### Repeat Items

Repeatable rows for education, experience, certifications, languages, projects, organizations, and emergency contacts should feel like structured rows:

- Use compact surfaces with clear row headers.
- Keep remove/add buttons visually secondary.
- Avoid layout shift when rows are added or removed.

### Document Upload

Document cards should prioritize status visibility:

- Required/optional badge stays prominent.
- Uploaded/not uploaded status is visible near the document label.
- Existing file metadata wraps safely for long filenames.
- Remove checkbox remains visually cautionary but not overly loud.

### Sticky Save Bar

The save bar remains sticky on desktop because it protects long-form workflows:

- Reduce visual weight and keep actions aligned.
- Preserve clear primary action for `Simpan Draft`.
- Keep `Simpan & Preview` secondary.
- On mobile, use a stacked or two-column layout that prevents button text overflow.

### Sidebar Progress

The sidebar remains sticky on large screens:

- Make progress and checklist easier to scan.
- Keep warning about PDF requirement.
- Avoid competing visually with the main form.

## Interaction And Feedback

- Preserve SweetAlert guide and validation warnings.
- Preserve loading text behavior on submit buttons.
- Preserve field focus mode, but ensure dimming does not reduce readability too much.
- Respect `prefers-reduced-motion` for pulse and panel transitions.
- Keep hover/focus transitions within 150-250ms.

## Accessibility

- Maintain visible labels and server-side validation feedback.
- Keep focus rings visible on controls and wizard buttons.
- Keep contrast at least WCAG AA for text and important UI states.
- Ensure icon-only or compact buttons still have visible text or tooltip support.
- Avoid horizontal scrolling at 375px viewport width.
- Keep touch targets around 44px minimum where practical.

## Implementation Notes

Expected primary file:

- `public/css/app.css`

Potential small markup file:

- `resources/views/cv/edit.blade.php`

The implementation should avoid changing JavaScript unless a visual state cannot be solved safely with CSS.

## Testing Plan

Manual browser checks:

- Open `/cv/edit`.
- Check each wizard step from 1 to 8.
- Trigger validation by trying to move forward with required fields empty.
- Add and remove at least one repeat item.
- Check photo upload modal still opens and controls remain usable.
- Check document cards with and without uploaded files.
- Check sticky savebar on desktop and mobile widths.
- Check mobile viewport around 375px for no horizontal scroll or text overflow.

Automated or command checks:

- Run `php artisan test` to ensure no backend behavior regressed.
- If CSS or asset build is changed through Mix later, run the relevant npm build command.

## Production Risk

Risk is low if the work stays mostly CSS-only. The main risks are:

- Button text wrapping poorly on mobile.
- Sticky savebar overlapping content.
- Wizard steps becoming too dense for translated labels.
- Visual states becoming ambiguous between active, complete, locked, and error.

Mitigation:

- Verify mobile and desktop layouts before delivery.
- Keep behavioral JavaScript unchanged.
- Keep changes scoped to the CV form styling.
