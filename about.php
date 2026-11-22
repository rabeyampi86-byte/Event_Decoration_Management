
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php';

/* =====================================
   FETCH ABOUT PAGE CONTENT
===================================== */

$aboutIntro = null;
$aboutMission = null;
$aboutVision = null;
$aboutImage = '';

$sql = "
    SELECT 
        ps.section_id,
        ps.section_key,
        ps.section_title,
        ps.heading,
        ps.subheading,
        ps.content,
        ps.image_id,
        m.file_path
    FROM pages p
    INNER JOIN page_sections ps 
        ON p.page_id = ps.page_id
    LEFT JOIN media m 
        ON ps.image_id = m.media_id
    WHERE p.page_slug = 'about'
      AND ps.is_visible = 1
    ORDER BY ps.sort_order ASC
";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {

        if ($row['section_key'] === 'about_intro') {
            $aboutIntro = $row;

            if (!empty($row['file_path'])) {
                $aboutImage = $row['file_path'];
            }
        }

        if ($row['section_key'] === 'mission') {
            $aboutMission = $row;
        }

        if ($row['section_key'] === 'vision') {
            $aboutVision = $row;
        }
    }
}

/* ===============================
   Our Team - Active Employees
================================ */

$teamMembers = [];

$teamQuery = $conn->query("
    SELECT
        u.full_name,
        ep.designation,
        ep.specialization,
        ep.profile_image,
        ep.bio
    FROM employee_profiles ep
    INNER JOIN users u ON ep.user_id = u.user_id
    WHERE u.role = 'employee'
      AND u.status = 'active'
    ORDER BY ep.employee_id ASC
");

if ($teamQuery) {
    while ($row = $teamQuery->fetch_assoc()) {
        $teamMembers[] = $row;
    }
}
/* =====================================
   SAFE OUTPUT FUNCTION
===================================== */

function aboutEscape($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/* Fallback image */
if (empty($aboutImage)) {
    $aboutImage = 'images/wed1.webp';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>About Us | DecorEase</title>

    <meta name="description" content="Learn more about DecorEase, our mission and vision.">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Google Fonts: Same as Homepage -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@500;600;700;800&display=swap"
          rel="stylesheet">

    <!-- Navbar CSS -->
    <?php include 'includes/nav_style.php'; ?>

    <style>

        /* =====================================
           GLOBAL CSS - SAME AS HOMEPAGE
        ===================================== */

        :root {
            --plum: #6d314b;
            --deep: #53263a;
            --pink: #c0547b;
            --rose: #e7a2b8;
            --cream: #fffaf7;
            --light: #fff0f4;
            --gold: #d6b47a;
            --text: #45343b;
            --muted: #83747a;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--cream);
            color: var(--text);
            line-height: 1.7;
        }

        a {
            text-decoration: none;
        }

        .container {
            width: 86%;
            max-width: 1280px;
            margin: auto;
        }

        .serif {
            font-family: 'Playfair Display', serif;
        }


        /* =====================================
           ABOUT HERO
        ===================================== */

        .about-hero {
            min-height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 70px 20px;

            background:
                linear-gradient(
                    rgba(20, 20, 20, 0.65),
                    rgba(20, 20, 20, 0.65)
                ),
                url('images/wed1.webp') center/cover no-repeat;
        }

        .about-hero-content {
            max-width: 750px;
            color: #fff;
        }

        .about-hero-content span {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .about-hero-content h1 {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            margin: 15px 0;
            font-weight: 700;
        }

        .about-hero-content p {
            font-size: 16px;
            line-height: 1.8;
            color: #f1f1f1;
        }


        /* =====================================
           INTRODUCTION SECTION
        ===================================== */

        .about-introduction {
            padding: 85px 0;
        }

        .about-intro-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            gap: 55px;
        }

        .about-intro-image {
            height: 440px;
            overflow: hidden;
            border-radius: 12px;
        }

        .about-intro-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .about-intro-content .eyebrow {
            display: inline-block;
            color: var(--pink);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
            margin-bottom: 15px;
        }

        .about-intro-content h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            line-height: 1.3;
            color: var(--deep);
            margin-bottom: 22px;
        }

        .about-intro-content p {
            font-size: 15px;
            line-height: 1.9;
            color: var(--muted);
            white-space: pre-line;
        }


        /* =====================================
           MISSION AND VISION
        ===================================== */

        .about-values-section {
            padding: 75px 0;
            background: var(--light);
        }

        .about-section-heading {
            text-align: center;
            max-width: 650px;
            margin: 0 auto 45px;
        }

        .about-section-heading span {
            color: var(--pink);
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 2px;
        }

        .about-section-heading h2 {
            font-family: 'Playfair Display', serif;
            font-size: 34px;
            color: var(--deep);
            margin-top: 12px;
        }

        .about-values-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 28px;
        }

        .about-value-card {
            background: #fff;
            padding: 38px 32px;
            border-radius: 12px;
            border: 1px solid #f1e5e9;
            transition: 0.3s ease;
        }

        .about-value-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(83, 38, 58, 0.08);
        }

        .about-value-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--light);
            color: var(--pink);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 25px;
            margin-bottom: 22px;
        }

        .about-value-card h3 {
            font-family: 'Playfair Display', serif;
            font-size: 23px;
            color: var(--deep);
            margin-bottom: 15px;
        }

        .about-value-card p {
            font-size: 15px;
            color: var(--muted);
            line-height: 1.9;
            margin: 0;
            white-space: pre-line;
        }


        /* =====================================
           RESPONSIVE DESIGN
        ===================================== */

        @media (max-width: 768px) {

            .about-hero {
                min-height: 280px;
            }

            .about-hero-content h1 {
                font-size: 34px;
            }

            .about-introduction {
                padding: 55px 0;
            }

            .about-intro-grid {
                grid-template-columns: 1fr;
                gap: 30px;
            }

            .about-intro-image {
                height: 300px;
            }

            .about-intro-content h2 {
                font-size: 28px;
            }

            .about-values-grid {
                grid-template-columns: 1fr;
            }

            .about-section-heading h2 {
                font-size: 27px;
            }

            .about-value-card {
                padding: 30px 25px;
            }
        }
/* ===============================
   Our Team Section
================================ */

.team-section {
    padding: 85px 0;
    background: #fffaf7;
}

.team-heading {
    text-align: center;
    margin-bottom: 45px;
}

.team-heading .eyebrow {
    color: var(--pink);
    font-size: 13px;
    font-weight: 700;
    letter-spacing: 3px;
    text-transform: uppercase;
}

.team-heading h2 {
    font-family: 'Playfair Display', serif;
    font-size: 38px;
    color: var(--deep);
    margin: 10px 0;
}

.team-heading p {
    color: var(--muted);
    max-width: 650px;
    margin: auto;
}

.team-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 25px;
}

.team-card {
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    text-align: center;
    box-shadow: 0 5px 22px rgba(83, 38, 58, 0.08);
    transition: transform .3s ease, box-shadow .3s ease;
}

.team-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 12px 28px rgba(83, 38, 58, 0.13);
}

.team-image {
    width: 100%;
    height: 260px;
    background: #f8eaf0;
    overflow: hidden;
}

.team-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.team-image-placeholder {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--plum);
    font-size: 75px;
}

.team-info {
    padding: 23px 20px 26px;
}

.team-info h3 {
    font-family: 'Playfair Display', serif;
    font-size: 22px;
    color: var(--deep);
    margin-bottom: 5px;
}

.team-designation {
    color: var(--pink);
    font-size: 14px;
    font-weight: 700;
    margin-bottom: 8px;
}

.team-specialization {
    color: var(--muted);
    font-size: 13px;
    margin-bottom: 13px;
}

.team-bio {
    color: var(--text);
    font-size: 13px;
    line-height: 1.8;
}

@media(max-width: 900px) {
    .team-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
}

@media(max-width: 600px) {
    .team-section {
        padding: 60px 0;
    }

    .team-heading h2 {
        font-size: 30px;
    }

    .team-grid {
        grid-template-columns: 1fr;
    }

    .team-image {
        height: 280px;
    }
}
    </style>

</head>

<body>

    <!-- =====================================
         NAVBAR
    ===================================== -->

    <?php include 'includes/navber.php'; ?>


    <!-- =====================================
         ABOUT HERO
    ===================================== -->

    <section class="about-hero">

        <div class="about-hero-content">

            <span>ABOUT DECOR EASE</span>

            <h1>We Create Moments That Last Forever</h1>

            <p>
                Discover our story, our passion, and our commitment
                to making every celebration extraordinary.
            </p>

        </div>

    </section>


    <!-- =====================================
         COMPANY INTRODUCTION
    ===================================== -->

    <?php if ($aboutIntro): ?>

    <section class="about-introduction">

        <div class="container">

            <div class="about-intro-grid">

                <div class="about-intro-image">

                    <img
                        src="<?php echo aboutEscape($aboutImage); ?>"
                        alt="<?php echo aboutEscape($aboutIntro['heading']); ?>"
                    >

                </div>

                <div class="about-intro-content">

                    <span class="eyebrow">
                        <?php echo aboutEscape($aboutIntro['subheading']); ?>
                    </span>

                    <h2>
                        <?php echo aboutEscape($aboutIntro['heading']); ?>
                    </h2>

                    <p>
                        <?php echo aboutEscape($aboutIntro['content']); ?>
                    </p>

                </div>

            </div>

        </div>

    </section>

    <?php endif; ?>


    <!-- =====================================
         MISSION AND VISION
    ===================================== -->

    <?php if ($aboutMission || $aboutVision): ?>

    <section class="about-values-section">

        <div class="container">

            <div class="about-section-heading">

                <span>WHAT DRIVES US</span>

                <h2>Our Mission & Vision</h2>

            </div>


            <div class="about-values-grid">


                <?php if ($aboutMission): ?>

                <!-- Mission Card -->

                <div class="about-value-card">

                    <div class="about-value-icon">
                        <i class="fas fa-bullseye"></i>
                    </div>

                    <h3>
                        <?php echo aboutEscape($aboutMission['heading']); ?>
                    </h3>

                    <p>
                        <?php echo aboutEscape($aboutMission['content']); ?>
                    </p>

                </div>

                <?php endif; ?>


                <?php if ($aboutVision): ?>

                <!-- Vision Card -->

                <div class="about-value-card">

                    <div class="about-value-icon">
                        <i class="fas fa-eye"></i>
                    </div>

                    <h3>
                        <?php echo aboutEscape($aboutVision['heading']); ?>
                    </h3>

                    <p>
                        <?php echo aboutEscape($aboutVision['content']); ?>
                    </p>

                </div>

                <?php endif; ?>


            </div>

        </div>

    </section>

    <?php endif; ?>

<!-- OUR TEAM SECTION -->
<?php if (!empty($teamMembers)): ?>

<section class="team-section">

    <div class="container">

        <div class="team-heading">
            <span class="eyebrow">Meet Our Team</span>

            <h2>Our Creative Team</h2>

            <p>
                Meet the dedicated professionals behind DecorEase.
                Our team works together to make every celebration
                beautiful, organized, and memorable.
            </p>
        </div>

        <div class="team-grid">

            <?php foreach ($teamMembers as $member): ?>

                <div class="team-card">

                    <div class="team-image">

                        <?php if (!empty($member['profile_image'])): ?>

                            <img
                                src="<?= aboutEscape($member['profile_image']) ?>"
                                alt="<?= aboutEscape($member['full_name']) ?>"
                                loading="lazy"
                            >

                        <?php else: ?>

                            <div class="team-image-placeholder">
                                <i class="fas fa-user"></i>
                            </div>

                        <?php endif; ?>

                    </div>

                    <div class="team-info">

                        <h3>
                            <?= aboutEscape($member['full_name']) ?>
                        </h3>

                        <div class="team-designation">
                            <?= aboutEscape($member['designation'] ?: 'Team Member') ?>
                        </div>

                        <?php if (!empty($member['specialization'])): ?>

                            <div class="team-specialization">
                                <?= aboutEscape($member['specialization']) ?>
                            </div>

                        <?php endif; ?>

                        <?php if (!empty($member['bio'])): ?>

                            <p class="team-bio">
                                <?= nl2br(aboutEscape($member['bio'])) ?>
                            </p>

                        <?php endif; ?>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>

<?php endif; ?>
    <!-- =====================================
         FOOTER
    ===================================== -->

    <?php include 'includes/footer.php'; ?>


</body>
</html>
