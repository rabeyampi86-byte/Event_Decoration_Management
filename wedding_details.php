
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$page_title = "Wedding Package Details | DecorEase";
?>

<!DOCTYPE html>
<html lang="en">
<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title><?php echo htmlspecialchars($page_title); ?></title>

<?php include 'includes/nav_style.php'; ?>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: Arial, sans-serif;
    background: #fffaf7;
    color: #38232d;
    line-height: 1.7;
}

:root {
    --primary: #57283c;
    --secondary: #a34d70;
    --accent: #c77a98;
    --light: #fff4f7;
    --border: #f0dce4;
    --text: #756870;
}
/* MAIN CONTAINER */
.wedding-container {
    max-width: 1200px;
    margin: 45px auto;
    padding: 0 25px;
}

/* PACKAGE OVERVIEW */
.package-overview {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 35px;
    align-items: center;
    background: white;
    padding: 22px;
    border-radius: 14px;
    border: 1px solid var(--border);
    box-shadow: 0 7px 22px rgba(65, 28, 46, .08);
}

.package-image {
    height: 380px;
    overflow: hidden;
    border-radius: 11px;
}

.package-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.package-content h2 {
    color: var(--primary);
    font-size: 28px;
    margin: 0 0 12px;
}

.package-content p {
    color: var(--text);
    font-size: 15px;
    margin-bottom: 15px;
}

.package-label {
    display: inline-block;
    background: var(--light);
    color: var(--secondary);
    padding: 5px 13px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 12px;
}

.package-price {
    margin-top: 20px;
    padding: 16px;
    background: var(--light);
    border-left: 4px solid var(--secondary);
    border-radius: 7px;
}

.package-price strong {
    display: block;
    color: var(--primary);
    font-size: 22px;
}

.package-price small {
    color: var(--text);
}

/* QUICK INFORMATION */
.quick-info {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 18px;
    margin-top: 30px;
}

.info-card {
    background: white;
    padding: 22px 15px;
    border: 1px solid var(--border);
    border-radius: 10px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(65, 28, 46, .05);
}

.info-card .info-icon {
    font-size: 27px;
    margin-bottom: 7px;
}

.info-card h3 {
    font-size: 23px;
    color: var(--primary);
    margin: 0;
}

.info-card p {
    font-size: 13px;
    color: var(--text);
    margin: 3px 0 0;
}

/* COMMON SECTION */
.detail-section {
    margin-top: 32px;
    padding: 30px;
    background: white;
    border: 1px solid var(--border);
    border-radius: 13px;
    box-shadow: 0 5px 18px rgba(65, 28, 46, .05);
}

.section-heading {
    margin-bottom: 23px;
}

.section-heading h2 {
    color: var(--primary);
    font-size: 25px;
    margin: 0 0 5px;
}

.section-heading p {
    color: var(--text);
    font-size: 14px;
    margin: 0;
}

/* TEAM GRID */
.team-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.team-card {
    background: #fff9fb;
    border: 1px solid var(--border);
    border-radius: 10px;
    padding: 20px;
}

.team-card .icon {
    font-size: 28px;
}

.team-card h3 {
    font-size: 17px;
    color: var(--primary);
    margin: 8px 0 5px;
}

.team-card .number {
    color: var(--secondary);
    font-size: 23px;
    font-weight: bold;
}

.team-card p {
    color: var(--text);
    font-size: 13px;
    margin: 5px 0 0;
}

/* DETAIL TABLE */
.detail-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 15px;
}

.detail-table th,
.detail-table td {
    padding: 13px 15px;
    text-align: left;
    border-bottom: 1px solid var(--border);
    font-size: 14px;
}

.detail-table th {
    color: var(--primary);
    background: #fff3f7;
}

.detail-table td {
    color: var(--text);
}

.detail-table tr:last-child td {
    border-bottom: none;
}

/* FLOWER GRID */
.flower-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.flower-card {
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #fff9fb;
}

.flower-card img {
    width: 100%;
    height: 175px;
    object-fit: cover;
    display: block;
}

.flower-card div {
    padding: 15px;
}

.flower-card h3 {
    color: var(--primary);
    font-size: 16px;
    margin: 0 0 5px;
}

.flower-card p {
    color: var(--text);
    font-size: 13px;
    margin: 0;
}

/* FOOD */
.food-layout {
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 25px;
}

.food-list {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.food-item {
    background: #fff8fa;
    border: 1px solid var(--border);
    border-radius: 7px;
    padding: 11px 13px;
    color: #65535c;
    font-size: 14px;
}

.food-item span {
    color: var(--secondary);
    font-weight: bold;
    margin-right: 7px;
}

.catering-box {
    padding: 22px;
    border-radius: 10px;
    background: #fff3f7;
}

.catering-box h3 {
    color: var(--primary);
    margin: 0 0 10px;
    font-size: 18px;
}

.catering-box p {
    font-size: 14px;
    color: var(--text);
    margin: 8px 0;
}

/* PARTNERS */
.partner-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 18px;
}

.partner-card {
    padding: 20px;
    border: 1px solid var(--border);
    border-radius: 10px;
    background: #fff9fb;
}

.partner-card h3 {
    color: var(--primary);
    font-size: 17px;
    margin: 0 0 8px;
}

.partner-card p {
    color: var(--text);
    font-size: 13px;
    margin: 0 0 10px;
}

.partner-status {
    display: inline-block;
    padding: 5px 10px;
    border-radius: 20px;
    background: #fff0d9;
    color: #95631d;
    font-size: 11px;
    font-weight: bold;
}

/* FACILITIES */
.facility-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 14px 25px;
}

.facility {
    display: flex;
    gap: 10px;
    font-size: 14px;
    color: #65535c;
}

.facility span {
    color: var(--secondary);
    font-weight: bold;
}

/* NOTICE */
.demo-note {
    margin-top: 25px;
    padding: 15px 18px;
    border-radius: 8px;
    background: #fff5e7;
    border: 1px solid #f0dfc4;
    color: #785d35;
    font-size: 13px;
}

/* BOOKING */
.booking-section {
    margin-top: 32px;
    padding: 40px 25px;
    text-align: center;
    border-radius: 13px;
    color: white;
    background: linear-gradient(110deg, #57283c, #a34d70);
}

.booking-section h2 {
    color: white;
    font-size: 27px;
    margin: 0 0 8px;
}

.booking-section p {
    color: #f8eaf0;
    font-size: 14px;
    margin: 0 0 22px;
}

.booking-btn {
    display: inline-block;
    padding: 12px 32px;
    background: white;
    color: #57283c;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    transition: .3s;
}

.booking-btn:hover {
    background: #ffe5ee;
    color: #57283c;
    transform: translateY(-2px);
}

.back-link {
    display: inline-block;
    margin-top: 22px;
    color: var(--secondary);
    text-decoration: none;
    font-weight: bold;
    font-size: 14px;
}

.back-link:hover {
    color: var(--primary);
}

/* RESPONSIVE */
@media(max-width: 900px) {
    .package-overview,
    .food-layout {
        grid-template-columns: 1fr;
    }

    .quick-info {
        grid-template-columns: repeat(2, 1fr);
    }

    .team-grid,
    .flower-grid,
    .partner-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media(max-width: 600px) {
    .wedding-container {
        padding: 0 15px;
        margin: 28px auto;
    }

    .wedding-hero {
        padding: 45px 5%;
    }

    .package-image {
        height: 230px;
    }

    .quick-info,
    .team-grid,
    .flower-grid,
    .partner-grid,
    .facility-grid,
    .food-list {
        grid-template-columns: 1fr;
    }

    .detail-section {
        padding: 20px 16px;
    }

    .section-heading h2 {
        font-size: 22px;
    }

    .detail-table th,
    .detail-table td {
        padding: 10px 8px;
        font-size: 12px;
    }
}
</style>

</head>

<body>

<?php include 'includes/navber.php'; ?>

<main>
<div class="wedding-container">

    <!-- PACKAGE OVERVIEW -->
    <section class="package-overview">

        <div class="package-image">
            <img src="images/wed1.webp"
                 alt="DecorEase Wedding Decoration">
        </div>

        <div class="package-content">

            <span class="package-label">WEDDING PACKAGE</span>

            <h2>Elegant Wedding Celebration</h2>

            <p>
                Make your special day unforgettable with a beautifully
                designed wedding venue. This package presents a complete
                event plan covering stage decoration, floral arrangements,
                lighting, photography, catering and guest management.
            </p>

            <p>
                Our event team coordinates every part of the celebration
                to create a comfortable and memorable experience for
                the bride, groom and their guests.
            </p>

            <div class="package-price">
                <strong>Price: Custom Quotation</strong>
                <small>
                    Final price depends on guest count, venue,
                    selected services and event requirements.
                </small>
            </div>

        </div>

    </section>


    <!-- QUICK INFORMATION -->
    <section class="quick-info">

        <div class="info-card">
            <div class="info-icon">📸</div>
            <h3>4</h3>
            <p>Photography Team Members</p>
        </div>

        <div class="info-card">
            <div class="info-icon">👥</div>
            <h3>6</h3>
            <p>Decoration Team Members</p>
        </div>

        <div class="info-card">
            <div class="info-icon">💡</div>
            <h3>30</h3>
            <p>LED & Decorative Lights</p>
        </div>

        <div class="info-card">
            <div class="info-icon">🍽️</div>
            <h3>300</h3>
            <p>Guest Meal Arrangement</p>
        </div>

    </section>


    <!-- PHOTOGRAPHY TEAM -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>01. Photography & Videography Team</h2>
            <p>
                Professional coverage of the important moments
                throughout the wedding celebration.
            </p>
        </div>

        <div class="team-grid">

            <div class="team-card">
                <div class="icon">📷</div>
                <h3>Photographers</h3>
                <div class="number">2 Persons</div>
                <p>
                    Wedding portraits, guest photography,
                    stage photography and special moments.
                </p>
            </div>

            <div class="team-card">
                <div class="icon">🎥</div>
                <h3>Videographers</h3>
                <div class="number">1 Person</div>
                <p>
                    Wedding ceremony video coverage
                    and important event highlights.
                </p>
            </div>

            <div class="team-card">
                <div class="icon">🖥️</div>
                <h3>Video Editor</h3>
                <div class="number">1 Person</div>
                <p>
                    Editing, colour correction and
                    preparation of the final wedding video.
                </p>
            </div>

        </div>

    </section>


    <!-- DECORATION TEAM -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>02. Decoration Team</h2>
            <p>
                A coordinated team responsible for preparing
                and decorating the entire wedding venue.
            </p>
        </div>

        <div class="team-grid">

            <div class="team-card">
                <div class="icon">👤</div>
                <h3>Lead Decorator</h3>
                <div class="number">1 Person</div>
                <p>
                    Plans the decoration theme and supervises
                    the complete decoration work.
                </p>
            </div>

            <div class="team-card">
                <div class="icon">🌸</div>
                <h3>Flower Decorators</h3>
                <div class="number">2 Persons</div>
                <p>
                    Arrange flowers, stage backdrops,
                    table decorations and floral designs.
                </p>
            </div>

            <div class="team-card">
                <div class="icon">🛠️</div>
                <h3>Setup Assistants</h3>
                <div class="number">3 Persons</div>
                <p>
                    Handle stage setup, seating arrangements,
                    installation and venue preparation.
                </p>
            </div>

        </div>

    </section>


    <!-- FLOWERS & DESIGN -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>03. Flowers & Decoration Design</h2>
            <p>
                A combination of fresh flowers and decorative
                elements to create an elegant wedding environment.
            </p>
        </div>

        <div class="flower-grid">

            <div class="flower-card">
                <img src="images/red2.jpg" alt="Rose decoration">
                <div>
                    <h3>Red & White Roses</h3>
                    <p>
                        Used around the wedding stage, couple seating
                        area and selected floral arrangements.
                    </p>
                </div>
            </div>

            <div class="flower-card">
                <img src="images/mg2.jpg" alt="Marigold decoration">
                <div>
                    <h3>Marigold Flowers</h3>
                    <p>
                        Used for entrance decoration, hanging garlands
                        and traditional decorative arrangements.
                    </p>
                </div>
            </div>

            <div class="flower-card">
                <img src="images/js.webp" alt="Jasmine decoration">
                <div>
                    <h3>Jasmine Flowers</h3>
                    <p>
                        Used in selected floral arrangements
                        and decorative details.
                    </p>
                </div>
            </div>

        </div>

        <table class="detail-table">

            <thead>
                <tr>
                    <th>Decoration Area</th>
                    <th>Design Plan</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>Wedding Stage</td>
                    <td>Floral backdrop with couple seating arrangement</td>
                </tr>

                <tr>
                    <td>Entrance Gate</td>
                    <td>Flower garlands and decorative lighting</td>
                </tr>

                <tr>
                    <td>Guest Tables</td>
                    <td>Small floral centrepieces</td>
                </tr>

                <tr>
                    <td>Photo Corner</td>
                    <td>Decorative backdrop with floral elements</td>
                </tr>

                <tr>
                    <td>Couple Seating</td>
                    <td>Decorated sofa with matching floral arrangements</td>
                </tr>
            </tbody>

        </table>

    </section>


    <!-- LIGHTING & SOUND -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>04. Lighting & Sound Arrangement</h2>
            <p>
                Carefully planned lighting and sound arrangements
                for the stage, entrance and event venue.
            </p>
        </div>

        <table class="detail-table">

            <thead>
                <tr>
                    <th>Equipment</th>
                    <th>Quantity</th>
                    <th>Purpose</th>
                </tr>
            </thead>

            <tbody>
                <tr>
                    <td>LED Par Lights</td>
                    <td>12</td>
                    <td>Stage and venue lighting</td>
                </tr>

                <tr>
                    <td>Moving Head Lights</td>
                    <td>4</td>
                    <td>Decorative stage effects</td>
                </tr>

                <tr>
                    <td>Spotlights</td>
                    <td>4</td>
                    <td>Couple and stage illumination</td>
                </tr>

                <tr>
                    <td>Fairy Lights</td>
                    <td>10 Sets</td>
                    <td>Entrance and decorative areas</td>
                </tr>

                <tr>
                    <td>Sound Speakers</td>
                    <td>2</td>
                    <td>Event sound coverage</td>
                </tr>

                <tr>
                    <td>Wireless Microphones</td>
                    <td>2</td>
                    <td>Announcements and event hosting</td>
                </tr>
            </tbody>

        </table>

    </section>


    <!-- FOOD & CATERING -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>05. Guest Food & Catering Menu</h2>
            <p>
                A sample wedding menu with selected traditional
                and popular dishes for invited guests.
            </p>
        </div>

        <div class="food-layout">

            <div class="food-list">

                <div class="food-item">
                    <span>✓</span> Welcome Drinks
                </div>

                <div class="food-item">
                    <span>✓</span> Chicken Roast
                </div>

                <div class="food-item">
                    <span>✓</span> Kacchi Biryani
                </div>

                <div class="food-item">
                    <span>✓</span> Beef Rezala
                </div>

                <div class="food-item">
                    <span>✓</span> Borhani
                </div>

                <div class="food-item">
                    <span>✓</span> Mixed Salad
                </div>

                <div class="food-item">
                    <span>✓</span> Jorda
                </div>

                <div class="food-item">
                    <span>✓</span> Firni
                </div>

                <div class="food-item">
                    <span>✓</span> Soft Drinks
                </div>

                <div class="food-item">
                    <span>✓</span> Mineral Water
                </div>

            </div>

            <div class="catering-box">

                <h3>Catering Information</h3>

                <p><strong>Guest Capacity:</strong> 300 Guests</p>

                <p><strong>Food Preparation:</strong> Professional Catering Team</p>

                <p><strong>Chef:</strong> 2 Head Chef + 6 Assistant Chefs</p>

                <p><strong>Serving Team:</strong> 15 Staff Members</p>

                <p>
                    <strong>Restaurant / Supplier:</strong>
                    To be confirmed according to customer selection.
                </p>

            </div>

        </div>

    </section>


    <!-- PARTNERS -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>06. Professional Partners</h2>
            <p>
                External photography, lighting and catering
                partners may be selected for the event.
            </p>
        </div>

        <div class="partner-grid">

            <div class="partner-card">
                <h3>Photography Partner</h3>
                <p>
                    Photography and videography service provider
                    for wedding event coverage.
                </p>
                <span class="partner-status">Partner to be confirmed</span>
            </div>

            <div class="partner-card">
                <h3>Lighting Partner</h3>
                <p>
                    Professional lighting equipment and
                    technical support provider.
                </p>
                <span class="partner-status">Partner to be confirmed</span>
            </div>

            <div class="partner-card">
                <h3>Catering Partner</h3>
                <p>
                    Restaurant or catering company responsible
                    for food preparation and delivery.
                </p>
                <span class="partner-status">Partner to be confirmed</span>
            </div>

        </div>

    </section>


    <!-- ADDITIONAL FACILITIES -->
    <section class="detail-section">

        <div class="section-heading">
            <h2>07. Additional Facilities</h2>
            <p>
                Additional arrangements to make the wedding
                celebration comfortable and organized.
            </p>
        </div>

        <div class="facility-grid">

            <div class="facility">
                <span>✓</span>
                <span>Bride and groom special seating arrangement</span>
            </div>

            <div class="facility">
                <span>✓</span>
                <span>Guest table and chair arrangement</span>
            </div>

            <div class="facility">
                <span>✓</span>
                <span>Welcome and guest management team</span>
            </div>

            <div class="facility">
                <span>✓</span>
                <span>Decorative entrance and reception area</span>
            </div>

            <div class="facility">
                <span>✓</span>
                <span>Event setup and decoration supervision</span>
            </div>

            <div class="facility">
                <span>✓</span>
                <span>Basic sound and microphone arrangement</span>
            </div>

        </div>

    </section>


    <!-- DEMO INFORMATION -->
    <div class="demo-note">
        <strong>Important:</strong>
        The quantities, team sizes and menu shown on this page
        are illustrative sample information for the package design.
        Actual arrangements, partner names, availability and final
        pricing must be confirmed before booking.
    </div>


    <!-- BOOKING -->
    <section class="booking-section">

        <h2>Ready to Plan Your Dream Wedding?</h2>

        <p>
            Explore our available wedding packages and choose
            the arrangement that matches your event and budget.
        </p>

        <a href="packages.php" class="booking-btn">
            Explore Wedding Packages →
        </a>

    </section>


    <a href="../services.php" class="back-link">
        ← Back to All Services
    </a>

</div>

</main>

<?php include 'includes/footer.php'; ?>

</body>
</html>
