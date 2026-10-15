<?php
declare(strict_types=1);

// Illustrative profiles for the initial directory. Replace these with your team.
$profiles = [
    ['ayesha-khan', 'Ayesha Khan', 'Residential Property Advisor', 'Lahore', ['Homes', 'Apartments', 'Buyer guidance'], ['Urdu', 'English'], 'f9c4c56232a0808b35', 'Sample profile focused on homes, apartments and buyer guidance in Lahore. Contact Royalmark Estate to discuss your residential property search and arrange the next step.'],
    ['hamza-ali', 'Hamza Ali', 'Commercial Property Advisor', 'Islamabad', ['Commercial property', 'Offices', 'Retail spaces'], ['Urdu', 'English'], 'ac493bf711ca9bfc67', 'Sample profile for commercial property, office and retail enquiries in Islamabad. Contact Royalmark Estate to discuss your preferred location, space requirements and viewing options.'],
    ['sara-ahmed', 'Sara Ahmed', 'Rental Property Consultant', 'Karachi', ['Rentals', 'Apartments', 'Tenant support'], ['Urdu', 'English'], '6e08d4cdfbf85e7b9d', 'Sample profile focused on rental homes and apartments in Karachi. Contact Royalmark Estate for help comparing rental options and planning a property viewing.'],
    ['bilal-hussain', 'Bilal Hussain', 'Projects & Land Advisor', 'Lahore', ['New projects', 'Plots', 'Land enquiries'], ['Urdu', 'English', 'Punjabi'], '68ee829f5dd52b4ca0', 'Sample profile for new projects, plots and land enquiries in Lahore. Contact Royalmark Estate to request project information, discuss locations and arrange a consultation.'],
    ['omar-farooq', 'Omar Farooq', 'Property Listing Consultant', 'Rawalpindi', ['Property listings', 'Seller support', 'Homes'], ['Urdu', 'English'], '4ce21e8b066563dd01', 'Sample profile for property owners and sellers in Rawalpindi. Contact Royalmark Estate to discuss listing requirements, property information and enquiry coordination.'],
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
