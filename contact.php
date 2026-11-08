<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$page_title = "Contact Us | DecorEase";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title); ?></title>
<?php include 'includes/nav_style.php'; ?>
<style>
*{box-sizing:border-box}body{margin:0;background:#fffaf7;color:#38232d;font-family:Arial,sans-serif;line-height:1.6}
.contact-hero{padding:52px 7% 48px;text-align:center;color:#fff;background:linear-gradient(115deg,rgba(48,22,37,.95),rgba(119,43,75,.88)),radial-gradient(circle at 80% 20%,#e9a9bc,transparent 35%),linear-gradient(120deg,#57283c,#b84a72)}
.contact-hero .eyebrow{font-size:12px;letter-spacing:3px;text-transform:uppercase;color:#ffdce7;font-weight:bold}
.contact-hero h1{font-size:clamp(32px,4vw,46px);margin:7px 0;color:#fff}.contact-hero p{max-width:650px;margin:auto;color:#f9eaf0}
.contact-area{max-width:1200px;margin:auto;padding:34px 7% 45px}
.contact-heading{text-align:center;margin-bottom:23px}.contact-heading h2{font-size:29px;color:#57283c;margin:0 0 4px}.contact-heading p{color:#796b72;margin:0}
.contact-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:17px}
.contact-card{background:#fff;border:1px solid #f0dce4;border-radius:12px;padding:23px 20px;text-align:center;box-shadow:0 5px 18px rgba(65,28,46,.07)}
.contact-card .icon{width:52px;height:52px;margin:0 auto 11px;border-radius:50%;display:grid;place-items:center;background:#fff0f4;color:#b84a72;font-size:25px}
.contact-card h3{font-size:17px;color:#57283c;margin:0 0 5px}.contact-card p,.contact-card a{margin:0;color:#766a71;font-size:14px;text-decoration:none;overflow-wrap:anywhere}.contact-card a:hover{color:#b84a72}
.contact-lower{display:grid;grid-template-columns:1.15fr .85fr;gap:18px;margin-top:20px}
.contact-panel{border-radius:12px;padding:24px;background:#fff0f4;border:1px solid #f2dce5}
.contact-panel h3{margin:0 0 8px;color:#57283c;font-size:21px}.contact-panel p{margin:0;color:#705f68;font-size:14px}
.contact-panel.dark{background:linear-gradient(120deg,#57283c,#9d4668);color:#fff;border:0;display:flex;flex-direction:column;justify-content:center}
.contact-panel.dark h3{color:#fff}.contact-panel.dark p{color:#f6e7ed}
.contact-btn{display:inline-block;align-self:flex-start;margin-top:13px;background:#fff;color:#57283c;text-decoration:none;padding:10px 19px;border-radius:6px;font-weight:bold;font-size:13px}
.contact-btn:hover{background:#ffe5ee;color:#57283c}
.site-footer{background:#2d1b24;color:#eee;padding:17px 7%;text-align:center;font-size:13px}
@media(max-width:700px){.contact-grid{grid-template-columns:1fr}.contact-lower{grid-template-columns:1fr}.contact-area{padding:28px 5%}.contact-hero{padding:40px 5%}}
</style>
</head>
<body>
<?php include 'includes/navber.php'; ?>
<main>
<section class="contact-hero"><div class="eyebrow">We’re here to help</div><h1>Let’s Plan Your Special Event</h1><p>Have a question or a decoration idea? Get in touch with DecorEase and tell us what you’re celebrating.</p></section>
<section class="contact-area">
 <div class="contact-heading"><h2>Get in Touch</h2><p>Reach out to us through the contact details below.</p></div>
 <div class="contact-grid">
  <div class="contact-card"><div class="icon">✉</div><h3>Email Us</h3><p><a href="mailto:info@decorease.com">info@decorease.com</a></p></div>
  <div class="contact-card"><div class="icon">☎</div><h3>Call Us</h3><p>+880 1XXXXXXXXX</p></div>
  <div class="contact-card"><div class="icon">⌖</div><h3>Our Location</h3><p>Dhaka, Bangladesh</p></div>
 </div>
 <div class="contact-lower">
  <div class="contact-panel"><h3>Tell Us About Your Event</h3><p>Whether it’s a wedding, engagement, holud, birthday, akika, anniversary or corporate event, share your preferred date, theme and requirements with us.</p></div>
  <div class="contact-panel dark"><h3>Ready to Get Started?</h3><p>Explore our decoration packages and choose a setup that fits your celebration.</p><a class="contact-btn" href="packages.php">Explore Packages</a></div>
 </div>
</section>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
