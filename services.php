<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$page_title = "Our Services | DecorEase";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo htmlspecialchars($page_title); ?></title>
<?php include 'includes/nav_style.php'; ?>
<style>
*{box-sizing:border-box} body{margin:0;background:#fffaf7;color:#38232d;font-family:Arial,sans-serif;line-height:1.6}
.services-hero{position:relative;overflow:hidden;padding:58px 7% 54px;text-align:center;color:#fff;background:linear-gradient(115deg,rgba(48,22,37,.94),rgba(119,43,75,.88)),radial-gradient(circle at 15% 20%,#e9a9bc,transparent 38%),linear-gradient(120deg,#57283c,#b84a72)}
.services-hero:after{content:"";position:absolute;width:280px;height:280px;border:1px solid rgba(255,255,255,.18);border-radius:50%;right:-65px;top:-130px;box-shadow:0 0 0 35px rgba(255,255,255,.04),0 0 0 70px rgba(255,255,255,.03)}
.hero-kicker{font-size:12px;letter-spacing:3px;text-transform:uppercase;color:#ffdce7;font-weight:bold}
.services-hero h1{font-size:clamp(32px,4vw,48px);margin:8px 0 7px;color:#fff;font-weight:700}
.services-hero p{max-width:680px;margin:0 auto;color:#f9eaf0;font-size:16px}
.services-wrap{padding:34px 7% 46px;max-width:1450px;margin:auto}
.section-heading{text-align:center;margin-bottom:24px}
.section-heading span{font-size:12px;text-transform:uppercase;letter-spacing:2px;color:#b84a72;font-weight:bold}
.section-heading h2{margin:3px 0 5px;font-size:30px;color:#57283c}
.section-heading p{margin:0;color:#796b72}
.service-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:18px}
.service-card{position:relative;overflow:hidden;background:#fff;border:1px solid #f0dce4;border-radius:12px;box-shadow:0 5px 18px rgba(65,28,46,.07);transition:.25s;min-width:0}
.service-card:hover{transform:translateY(-5px);box-shadow:0 12px 25px rgba(65,28,46,.14)}
.service-visual img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.service-visual:before,.service-visual:after{content:"";position:absolute;border:1px solid rgba(255,255,255,.25);border-radius:50%;width:170px;height:170px}
.service-visual:after{width:220px;height:220px}
.service-visual .symbol{position:relative;z-index:1;font-size:54px;filter:drop-shadow(0 4px 5px rgba(0,0,0,.18))}
.v-wedding{background:linear-gradient(135deg,#54263d,#c46a88)}
.v-engagement{background:linear-gradient(135deg,#72506d,#d99ab4)}
.v-holud{background:linear-gradient(135deg,#b47722,#e9bd58)}
.v-anniversary{background:linear-gradient(135deg,#6d2946,#d17b91)}
.v-birthday{background:linear-gradient(135deg,#634b83,#bd91cf)}
.v-akika{background:linear-gradient(135deg,#48766c,#9bc4a5)}
.v-corporate{background:linear-gradient(135deg,#344b63,#819ab3)}
.v-custom{background:linear-gradient(135deg,#74415f,#c28ba8)}
.service-info{padding:16px 17px 18px}
.service-info h3{font-size:18px;margin:0 0 6px;color:#57283c}
.service-info p{font-size:13px;color:#756870;margin:0 0 12px;min-height:59px}
.service-link{font-size:13px;font-weight:bold;color:#b84a72;text-decoration:none}
.service-link:hover{color:#57283c}
.service-link span{font-size:17px;vertical-align:-1px;margin-left:3px}
.service-cta{margin-top:30px;padding:25px 28px;border-radius:13px;background:linear-gradient(105deg,#57283c,#9d4668);color:#fff;display:flex;align-items:center;justify-content:space-between;gap:20px}
.service-cta h2{font-size:23px;margin:0 0 3px;color:#fff}.service-cta p{margin:0;color:#f6e7ed;font-size:14px}
.cta-btn{flex-shrink:0;background:#fff;color:#57283c;text-decoration:none;padding:11px 20px;border-radius:6px;font-weight:bold;font-size:13px}
.cta-btn:hover{background:#ffe5ee;color:#57283c}
.site-footer{background:#2d1b24;color:#eee;padding:17px 7%;text-align:center;font-size:13px}
@media(max-width:1000px){.service-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media(max-width:600px){.services-hero{padding:42px 6%}.services-wrap{padding:28px 5%}.service-grid{grid-template-columns:1fr}.service-visual{height:125px}.service-info p{min-height:0}.service-cta{display:block;text-align:center}.cta-btn{display:inline-block;margin-top:14px}}
</style>
</head>
<body>
<?php include 'includes/navber.php'; ?>
<main>
<section class="services-hero">
 <div class="hero-kicker">
    DecorEase • Celebrate beautifully
</div>
 <h1>Decoration for Every Celebration</h1>
 <p>
    From intimate family occasions to grand celebrations, we create beautiful spaces that make your special moments unforgettable.
</p>
</section>
<section class="services-wrap">
 <div class="section-heading">
    <span>What we do</span><h2>Our Event Decoration Services</h2>
    <p>
        Thoughtful themes, beautiful details and a setup made for your occasion.
    </p>
</div>
 <div class="service-grid">
  <article class="service-card">
    <div class="service-visual">
        <img src="images/wed1.png" alt="Wedding Decoration">
    </div>
    <div class="service-info">
        <h3>Wedding Decoration</h3>
        <p>Elegant wedding stages, entrance gates, floral arrangements and coordinated venue styling.
        </p>
        <a class="service-link" href="packages.php">Explore packages <span>→</span>
    </a>
</div>
</article>
  <article class="service-card">
    <div class="service-visual">
        <img src="images/en1.png" alt="Wedding Decoration">
    </div>
    <div class="service-info">
        <h3>Engagement Decoration</h3>
        <p>
            Beautiful backdrops, floral details and a charming setup for your engagement ceremony.
        </p>
        <a class="service-link" href="packages.php">Explore packages <span>→</span>
    </a>
</div>
</article>
  <article class="service-card">
    <div class="service-visual">
        <img src="images/h1.png" alt="Wedding Decoration">
    </div>
    <div class="service-info">
        <h3>Holud Decoration</h3>
        <p>
            Vibrant yellow themes, marigold-inspired details and colorful traditional event styling.
        </p>
        <a class="service-link" href="packages.php">Explore packages <span>→</span></a
        ></div>
    </article>
  <article class="service-card">
    <div class="service-visual">
        <img src="images/wed2.png" alt="Wedding Decoration">
    </div>
    <div class="service-info">
        <h3>Anniversary Decoration</h3>
        <p>
            Warm lighting, romantic backdrops and floral accents for meaningful milestones.
        </p>
        <a class="service-link" href="packages.php">Explore packages <span>→</span></a>
    </div>
</article>
  <article class="service-card">
    <div class="service-visual">
        <img src="images/b.png" alt="Wedding Decoration">
    </div>
    <div class="service-info">
        <h3>Birthday Decoration</h3>
        <p>
            Creative balloon styling, themed backdrops and joyful setups for all ages. 
        </p>
        <a class="service-link" href="packages.php">Explore packages <span>→</span></a>
    </div>
</article>
  <article class="service-card">
    <div class="service-visual">
        <img src="images/ak.png" alt="Wedding Decoration">
    </div>
  <div class="service-info">
    <h3>Akika Decoration</h3>
    <p>
        Soft, welcoming themes and tasteful decoration for your family’s special gathering.
    </p>
    <a class="service-link" href="packages.php">Explore packages <span>→</span></a>
</div>
</article>
  <article class="service-card">
    <div class="service-visual">
        <img src="images/c.jpg" alt="Wedding Decoration">
    </div>
    <div class="service-info">
        <h3>Corporate Events</h3>
        <p>
            Neat, professional stage and venue decoration for meetings, launches and celebrations.
        </p>
        <a class="service-link" href="contact.php">Discuss your event <span>→</span></a>
    </div>
</article>
  <article class="service-card">
    <div class="service-visual">
        <img src="images/com1.png" alt="Wedding Decoration">
    </div>
    <div class="service-info">
        <h3>Customized Events</h3>
        <p>
            Have a unique idea? We can shape a decoration concept around your theme and requirements.
        </p>
        <a class="service-link" href="contact.php">Discuss your idea <span>→</span>
    </a>
</div>
</article>
 </div>
 <div class="service-cta"><div>
    <h2>Have a celebration in mind?</h2>
    <p>
        Tell us your event type, preferred theme and date. Let’s plan something beautiful.
    </p>
</div>
<a class="cta-btn" href="contact.php">Contact DecorEase
</a>
</div>
</section>
</main>
<?php include 'includes/footer.php'; ?>
</body>
</html>
