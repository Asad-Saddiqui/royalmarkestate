<section class="rm-home-faq rm-native-section" aria-labelledby="home-faq-title">
<div class="rm-native-heading"><span>FAQ</span><h2 id="home-faq-title">Need help? We have you covered.</h2><p>Answers to common questions about finding your next property.</p></div>
<div class="rm-faq-list">
<?php foreach([
['How do I find a property?','Open Properties to browse available listings. Filter by location, property type and purpose, then open a property for photos, pricing and details.'],
['Where can I see the price and pricing documents?','Each property page shows its listed price in rupees. Where available, the Pricing Documents section lets you view images and PDFs. Contact our team to confirm the latest price and availability.'],
['Can I view property photos and videos?','Yes. Open the property gallery to view all available photos. Properties with videos also include a separate video section.'],
['How can I arrange a viewing?','Use Contact Us, email or WhatsApp to reach Royalmark Estate. Share the property link and your preferred time so our team can help arrange a viewing.'],
['Do I need an account to browse properties?','You can browse public listings without signing in. Your account gives you access to your user dashboard and account settings.']
] as [$question,$answer]): ?><details><summary><?= e($question) ?><span aria-hidden="true"></span></summary><div class="rm-faq-answer"><p><?= e($answer) ?></p></div></details><?php endforeach; ?>
</div></section>
