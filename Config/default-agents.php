<?php
declare(strict_types=1);

// Illustrative profiles for the initial directory. Replace these with your team.
$profiles = [
    ['ayesha-khan', 'Ayesha Khan', 'Residential Property Advisor', 'Lahore', ['Homes', 'Apartments', 'Buyer guidance'], ['Urdu', 'English'], 'f9c4c56232a0808b35', 'A sample residential advisor profile showing how your team can help buyers compare homes, arrange viewings, and understand the next steps. Replace this introduction with your agent’s verified background and services.'],
    ['hamza-ali', 'Hamza Ali', 'Commercial Property Advisor', 'Islamabad', ['Commercial property', 'Offices', 'Retail spaces'], ['Urdu', 'English'], 'ac493bf711ca9bfc67', 'A sample commercial advisor profile for office and retail enquiries. Use this space to describe your agent’s areas of service, approach to property searches, and availability for consultations.'],
    ['sara-ahmed', 'Sara Ahmed', 'Rental Property Consultant', 'Karachi', ['Rentals', 'Apartments', 'Tenant support'], ['Urdu', 'English'], '6e08d4cdfbf85e7b9d', 'A sample rental consultant profile for people looking for their next home. Add your agent’s real introduction, preferred neighbourhoods, and the support they offer before, during, and after a viewing.'],
    ['bilal-hussain', 'Bilal Hussain', 'Projects & Land Advisor', 'Lahore', ['New projects', 'Plots', 'Land enquiries'], ['Urdu', 'English', 'Punjabi'], '68ee829f5dd52b4ca0', 'A sample projects and land advisor profile. Replace this description with verified details about your agent’s work, the project information they can provide, and how they help clients request documents and compare options.'],
    ['omar-farooq', 'Omar Farooq', 'Property Listing Consultant', 'Rawalpindi', ['Property listings', 'Seller support', 'Homes'], ['Urdu', 'English'], '4ce21e8b066563dd01', 'A sample listing consultant profile for property owners and sellers. Introduce your real agent here and explain how they help prepare listing details, coordinate enquiries, and organise property viewings.'],
];

$agents = [];
foreach ($profiles as $index => [$slug, $name, $position, $city, $specialities, $languages, $image, $bio]) {
    $agents[] = [
        'slug' => $slug,
        'name' => $name,
        'position' => $position,
        'email' => '',
        'phone' => '',
        'whatsapp' => '',
        'city' => $city,
        'specialities' => $specialities,
        'languages' => $languages,
        'bio' => $bio,
        'photo' => './assets/site-media/' . $image . '.png',
        'photo_alt' => 'Illustrative portrait for the ' . $name . ' sample agent profile',
        'experience_years' => 0,
        'sort_order' => $index + 1,
        'status' => 'published',
        'facebook' => '',
        'instagram' => '',
        'linkedin' => '',
        'is_sample' => true,
    ];
}

return $agents;
