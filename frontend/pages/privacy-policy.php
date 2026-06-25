<?php
require_once dirname(__DIR__, 2) . '/backend/bootstrap-web-view.php';
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title>Privacy Policy — CreatorzHive</title>
  <link rel="icon" type="image/svg+xml" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>">
  <link rel="apple-touch-icon" href="<?= htmlspecialchars(asset_url('frontend/assets/icon.svg')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/auth.css')) ?>">
  <link rel="stylesheet" href="<?= htmlspecialchars(asset_url('frontend/css/dark-mode.css')) ?>">
  <style>
    body { background: var(--bg-primary); color: var(--text-primary); line-height: 1.6; }
    .legal-container { max-width: 900px; margin: 0 auto; padding: 2rem; }
    h1 { margin-bottom: 0.5rem; }
    .last-updated { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 2rem; }
    h2 { margin-top: 2rem; margin-bottom: 1rem; }
    ul, ol { margin: 1rem 0 1rem 1.5rem; }
    li { margin: 0.5rem 0; }
  </style>
</head>
<body>
<div class="legal-container">
  <h1>Privacy Policy</h1>
  <p class="last-updated">Last updated: June 12, 2026</p>

  <h2>1. Introduction</h2>
  <p>CreatorzHive ("we," "us," "our," or "Company") operates the CreatorzHive platform. This page informs you of our policies regarding the collection, use, and disclosure of personal data when you use our service and the choices you have associated with that data.</p>

  <h2>2. Information Collection and Use</h2>
  <p>We collect several different types of information for various purposes to provide and improve our service to you.</p>

  <h3>2.1 Personal Data</h3>
  <ul>
    <li>Email address</li>
    <li>Username and password</li>
    <li>Profile information (name, avatar, bio)</li>
    <li>Social media account credentials (encrypted)</li>
    <li>Usage analytics and preferences</li>
  </ul>

  <h3>2.2 Usage Data</h3>
  <p>We may also collect information on how the service is accessed and used ("Usage Data"). This includes information such as your computer's IP address, browser type and version, the pages you visit, timestamps, and other diagnostic data.</p>

  <h2>3. Use of Data</h2>
  <p>CreatorzHive uses the collected data for various purposes:</p>
  <ul>
    <li>To provide and maintain our service</li>
    <li>To notify you about changes to our service</li>
    <li>To allow you to participate in interactive features of our service</li>
    <li>To provide customer care and support</li>
    <li>To gather analysis or valuable information so we can improve our service</li>
    <li>To monitor the usage of our service</li>
    <li>To detect, prevent and address technical issues</li>
  </ul>

  <h2>4. Security of Data</h2>
  <p>The security of your data is important to us, but remember that no method of transmission over the Internet or method of electronic storage is 100% secure. While we strive to use commercially acceptable means to protect your personal data, we cannot guarantee its absolute security.</p>

  <h2>5. Third-Party Service Providers</h2>
  <p>We may employ third-party companies and individuals to facilitate our service:</p>
  <ul>
    <li>Google (OAuth authentication)</li>
    <li>Meta/Facebook (OAuth authentication and social platform integration)</li>
    <li>Email service providers (for notifications)</li>
  </ul>

  <h2>6. Links to Other Sites</h2>
  <p>Our service may contain links to other sites that are not operated by us. If you click on a third-party link, you will be directed to that third party's site. We strongly advise you to review the Privacy Policy of every site you visit.</p>

  <h2>7. Children's Privacy</h2>
  <p>Our service does not address anyone under the age of 18. We do not knowingly collect personally identifiable information from children under 18. If we become aware that we have collected personal data from a child under 18, we take steps to delete such information and terminate that person's account.</p>

  <h2>8. Changes to This Privacy Policy</h2>
  <p>We may update our Privacy Policy from time to time. We will notify you of any changes by posting the new Privacy Policy on this page and updating the "last updated" date.</p>

  <h2>9. Contact Us</h2>
  <p>If you have any questions about this Privacy Policy, please contact us at: privacy@creatorzhive.com</p>
</div>
</body>
</html>
