<template data-repeat-template="experiences">
    @include('cv.partials.experience-row', ['index' => '__INDEX__', 'item' => [], 'profile' => $profile])
</template>

<template data-repeat-template="educations">
    @include('cv.partials.education-row', ['index' => '__INDEX__', 'item' => [], 'educationLevels' => $educationLevels, 'yearOptions' => $yearOptions])
</template>

<template data-repeat-template="emergency_contacts">
    @include('cv.partials.emergency-contact-row', ['index' => '__INDEX__', 'item' => [], 'emergencyRelationshipOptions' => $emergencyRelationshipOptions])
</template>

<template data-repeat-template="certifications">
    @include('cv.partials.certification-row', ['index' => '__INDEX__', 'item' => [], 'yearOptions' => $yearOptions, 'validUntilYearOptions' => $validUntilYearOptions])
</template>

<template data-repeat-template="languages">
    @include('cv.partials.language-row', ['index' => '__INDEX__', 'item' => [], 'languageLevels' => $languageLevels])
</template>

<template data-repeat-template="projects">
    @include('cv.partials.project-row', ['index' => '__INDEX__', 'item' => [], 'yearOptions' => $yearOptions])
</template>

<template data-repeat-template="organizations">
    @include('cv.partials.organization-row', ['index' => '__INDEX__', 'item' => [], 'yearOptions' => $yearOptions])
</template>
