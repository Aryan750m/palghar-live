<?php
// Installer: install.php
// Production-ready self-locking database installer & seeder

$lockFile = 'install.lock';

// 1. Self-locking check
if (file_exists($lockFile)) {
    http_response_code(403);
    echo "<!DOCTYPE html>
    <html lang='en'>
    <head>
        <meta charset='UTF-8'>
        <title>Installation Locked - Palghar LIVE</title>
        <link rel='stylesheet' href='assets/css/style.css'>
    </head>
    <body style='display:flex; justify-content:center; align-items:center; min-height:100vh; background-color:var(--bg-main); font-family:sans-serif;'>
        <div class='form-card' style='max-width:500px; text-align:center; padding:40px; border:1px solid var(--border-color); border-radius:8px; background-color:var(--bg-card);'>
            <span style='font-size:3.5rem; color:var(--primary);'>🔒</span>
            <h2 style='margin-top:20px; font-family:var(--font-heading); color:var(--text-primary);'>Installation Locked</h2>
            <p style='color:var(--text-muted); margin:15px 0; line-height:1.6;'>This installer has already run successfully and is now disabled for security. If you need to re-run the setup, please delete the file <code>install.lock</code> from your server root directory.</p>
            <a href='index.php' class='btn-live' style='display:inline-flex; width:auto; text-decoration:none;'>Go to Website</a>
        </div>
    </body>
    </html>";
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    
    try {
        $db = getDatabaseConnection();
        
        // Read schema.sql
        $sqlPath = 'schema.sql';
        if (!file_exists($sqlPath)) {
            throw new Exception("schema.sql file not found in root directory.");
        }
        $sql = file_get_contents($sqlPath);
        
        // Execute table creation queries
        $db->exec($sql);
        
        // Clear any existing default data first to avoid duplicate primary keys
        $db->exec("SET FOREIGN_KEY_CHECKS = 0; TRUNCATE TABLE comments; TRUNCATE TABLE news; TRUNCATE TABLE section_images; TRUNCATE TABLE sections; TRUNCATE TABLE inquiries; TRUNCATE TABLE users; SET FOREIGN_KEY_CHECKS = 1;");
        
        // Insert dynamic category sections
        $sections = [
            ['local', 'Palghar Local', 'All local happenings, village councils, and civic updates from Palghar District.'],
            ['state', 'Maharashtra', 'State government notifications, policy declarations, and regional updates.'],
            ['national', 'National', 'Country-wide policy updates, national initiatives, and political analysis.'],
            ['sports', 'Sports', 'Local leagues, school sports events, and national tournaments news.'],
            ['business', 'Business', 'Local MIDC trade news, entrepreneurship guidance, and financial trends.'],
            ['culture', 'Art & Culture', 'Celebrating Warli art traditions, historic monuments, and tribal dance festivals.']
        ];
        
        $secStmt = $db->prepare("INSERT INTO sections (id, title, description) VALUES (?, ?, ?)");
        foreach ($sections as $sec) {
            $secStmt->execute($sec);
        }
        
        // Insert section images (Initial sliders / banner graphics)
        $sectionImages = [
            ['local', 'https://images.unsplash.com/photo-1504608524841-42fe6f032b4b?auto=format&fit=crop&w=800&q=80', 'Heavy rain warnings in Palghar talukas', 1],
            ['local', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80', 'Local bridge infrastructure progress', 2],
            ['state', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80', 'State bus transit network expansion', 1],
            ['national', 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=80', 'National Digital Education pilot scheme', 1],
            ['sports', 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?auto=format&fit=crop&w=800&q=80', 'District Cricket Championship finals', 1],
            ['business', 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80', 'Gholvad Chikoo international shipping desk', 1],
            ['business', 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=80', 'Boisar MIDC industrial development belt', 2],
            ['culture', 'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=800&q=80', 'Traditional Warli painting designs display', 1],
            ['culture', 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?auto=format&fit=crop&w=800&q=80', 'Traditional Tarpa tribal group dance', 2],
            ['culture', 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80', 'Jay Vilas Palace heritage site in Jawhar', 3]
        ];
        
        $imgStmt = $db->prepare("INSERT INTO section_images (section_id, image_path, caption, sort_order) VALUES (?, ?, ?, ?)");
        foreach ($sectionImages as $img) {
            $imgStmt->execute($img);
        }
        
        // Insert first administrative user securely hashed
        $adminUser = 'admin';
        $adminPass = 'admin123';
        $hashedPass = password_hash($adminPass, PASSWORD_DEFAULT);
        
        $usrStmt = $db->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 'enabled')");
        $usrStmt->execute([$adminUser, $hashedPass, 'admin']);
        
        // Insert secondary Editor accounts
        $editorA = 'usera';
        $editorB = 'userb';
        $editorPass = password_hash('user123', PASSWORD_DEFAULT);
        $usrStmt->execute([$editorA, $editorPass, 'editor']);
        $usrStmt->execute([$editorB, $editorPass, 'editor']);
        
        // Insert category permissions mappings
        $uIdA = $db->query("SELECT id FROM users WHERE username = 'usera'")->fetchColumn();
        $uIdB = $db->query("SELECT id FROM users WHERE username = 'userb'")->fetchColumn();
        
        $permStmt = $db->prepare("INSERT INTO user_permissions (user_id, section_id) VALUES (?, ?)");
        if ($uIdA) {
            $permStmt->execute([$uIdA, 'sports']);
            $permStmt->execute([$uIdA, 'business']);
        }
        if ($uIdB) {
            $permStmt->execute([$uIdB, 'local']);
            $permStmt->execute([$uIdB, 'culture']);
        }
        
        // Insert default articles matching defaultNews
        $newsArticles = [
            [
                'Red Alert Issued for Palghar District: Heavy Rainfall Predicted for Next 48 Hours',
                'IMD issues red alert warning for Palghar and surrounding regions. Local administration advises citizens to stay alert and prepares emergency response teams.',
                "Monsoon has advanced aggressively across Palghar district, prompting the Indian Meteorological Department (IMD) to issue a Red Alert warning for the region for the next 48 hours. Extremely heavy rainfall is expected at isolated places across the district.\n\nAccording to the District Collector's office, all emergency response systems, including flood rescue teams, have been put on high alert. Residents in low-lying areas of Vasai, Palghar, Dahanu, and Talasri talukas have been advised to take precautions.\n\nWith water levels expected to rise in local rivers, warnings have been issued to riverbank villages. The fisheries department has appealed to fishermen not to venture into the Arabian Sea for the next two days. The administration clarified that citizens can contact the district control room in case of any emergency.",
                'local',
                'https://images.unsplash.com/photo-1504608524841-42fe6f032b4b?auto=format&fit=crop&w=800&q=80',
                'Staff Reporter, Palghar',
                1420,
                1,
                1
            ],
            [
                'Mumbai-Vadodara Expressway Palghar Stretch in Final Phase; Set to Open by December',
                'Construction of the ambitious Mumbai-Vadodara Expressway stretch in Palghar district is 90% complete, paving the way for seamless transit.',
                "Work on the Mumbai-Vadodara Expressway, which will significantly boost connectivity in Palghar district, is moving at a rapid pace. The construction of the stretch passing through Dahanu, Wada, and Palghar talukas has entered its final phase.\n\nThis expressway is expected to drastically reduce travel time between Mumbai and Vadodara. It will also be a major boon for industrial pockets in Palghar district, especially the Boisar MIDC industrial area.\n\nAccording to national highway authority officials, construction of key flyovers and connecting roads is complete, with minor asphalt surfacing and signboarding works pending. The target is to open the highway for traffic by December 2026, which is expected to open up numerous employment opportunities for local youths.",
                'state',
                'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80',
                'Rajesh Patil, Boisar',
                985,
                1,
                0
            ],
            [
                'Three-Day Traditional Warli Art Festival Begins in Jawhar; Huge Tourist Footfall Recorded',
                'The historic town of Jawhar in Palghar district hosts an exhibition celebrating indigenous Warli paintings and tribal cultural heritage.',
                "A three-day 'Warli Art and Cultural Festival' celebrating the global heritage of tribal culture and Warli paintings has begun at the Palace Ground in Jawhar. The exhibition showcases thousands of paintings sketched by local tribal artists.\n\nA large number of domestic tourists from Mumbai and Pune, alongside international visitors, have gathered in Jawhar to experience the unique art forms, organic tribal lifestyles, and live demonstrations.\n\nThe festival provides local tribal craftsmen with a direct marketplace to sell their paintings and crafts, boosting the regional economy. The District Administration has supported the festival as part of its plan to develop Jawhar into a major tourism hub. Highlights of the event include traditional Tarpa dance performances and indigenous tribal food stalls.",
                'culture',
                'https://images.unsplash.com/photo-1513364776144-60967b0f800f?auto=format&fit=crop&w=800&q=80',
                'Sneha Gavit, Jawhar',
                742,
                0,
                0
            ],
            [
                'Palghar Cricket Association Championship: Boisar Warriors Wins the Title',
                'Boisar Warriors registered an emphatic 45-run victory against Palghar Stars in the finals of the District Championship at Palghar District Ground.',
                "Boisar Warriors clinched the prestigious Palghar District Cricket Championship trophy in an action-packed final match played at the Central District Sports Ground in Palghar on Sunday.\n\nOpting to bat first, Boisar Warriors posted a competitive total of 178 runs in their allotted 20 overs. Top-order batsman Amit Mishra scored a brilliant 68 runs off just 42 balls, hit with 5 boundaries and 4 sixes.\n\nIn response, Palghar Stars struggled against a disciplined bowling attack from Warriors. Palghar Stars were bowled out for 133 in 18.2 overs. Spinner Sagar Patil took 4 crucial wickets for Boisar, conceding just 18 runs in his quota of 4 overs. He was awarded the Player of the Match, while Amit Mishra bagged the Player of the Tournament award. Over 5,000 local sports enthusiasts gathered to witness the final match, raising community sports spirits.",
                'sports',
                'https://images.unsplash.com/photo-1531415074968-036ba1b575da?auto=format&fit=crop&w=800&q=80',
                'Sports Desk, Palghar',
                860,
                1,
                0
            ],
            [
                'Dahanu Gholvad Chikoo Receives Rising Global Demand; Exports Set to Double',
                'Dahanu\'s GI-tagged Gholvad Chikoo fruit is gaining high preference in Gulf and European markets this harvest season.',
                "The famous Gholvad Chikoo of Dahanu taluka, Palghar district, is rapidly establishing its presence in international markets. This season has seen large-scale shipments to Gulf countries, alongside a strong surge in inquiries from Europe.\n\nCultivated in coastal microclimates, Dahanu and Gholvad Chikoo has received the Geographical Indication (GI) tag due to its unique sweet taste and rich texture.\n\nAccording to the local Chikoo Growers and Exporters Association, international shipments are expected to double this year, ensuring highly profitable rates for local farmers. Advanced packaging and rapid shipping logistics have helped maintain quality. The District Agriculture Department has also introduced subsidy schemes to encourage local growers.",
                'business',
                'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&w=800&q=80',
                'Mahesh Sankhe, Dahanu',
                520,
                0,
                0
            ],
            [
                'National Digital Education Mission Launched to Benefit Rural Schools in Palghar',
                'Union Ministry of Education selects Palghar as one of the pilot districts to implement smart classroom initiatives in 100 rural schools.',
                "Under the National Digital Education Mission, the Central Government has chosen Palghar district to launch its major e-learning pilot program. Under this scheme, 100 government-run schools in remote and tribal areas of Jawhar, Mokhada, and Wada talukas will be equipped with modern digital smart boards and high-speed satellite internet connections.\n\nThis project aims to bridge the digital divide between urban and rural students, providing equal opportunities to access quality educational resources.\n\nTeachers in these schools are currently undergoing specialized training to utilize digital curriculum and interactive teaching aids. \"This initiative will completely transform the education system in tribal pockets, making studies interesting and lowering school drop-out rates,\" said the District Education Officer. Local parents have expressed joy over this developmental step.",
                'national',
                'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=800&q=80',
                'Delhi Bureau',
                690,
                0,
                0
            ]
        ];
        
        $newsStmt = $db->prepare("INSERT INTO news (title, summary, content, category, image_path, author, views, trending, featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        foreach ($newsArticles as $art) {
            $newsStmt->execute($art);
        }
        
        // Insert comments
        $comments = [
            ['%Heavy Rainfall%', 'Dinesh Raut', 'Very crucial weather information. Hope the local emergency teams are deployed correctly.'],
            ['%Heavy Rainfall%', 'Priya Sharma', 'Hope everyone in low lying areas stays safe!'],
            ['%Expressway%', 'Vikas Salvi', 'Great news! The highway will boost Boisar connectivity significantly.']
        ];
        
        $commentStmt = $db->prepare("INSERT INTO comments (news_id, name, text) VALUES (?, ?, ?)");
        foreach ($comments as $c) {
            $articleId = $db->prepare("SELECT id FROM news WHERE title LIKE ?");
            $articleId->execute([$c[0]]);
            $idVal = $articleId->fetchColumn();
            if ($idVal) {
                $commentStmt->execute([$idVal, $c[1], $c[2]]);
            }
        }
        
        // Insert inquiries
        $inquiries = [
            ['Suresh Patil', 'suresh@gmail.com', '9876543210', 'We want to advertise the grand opening of our new store in Boisar. What are your advertising rates?', 'ad'],
            ['Rahul Mehta', 'rahul@mehta.com', '9988776655', 'I want to submit a tip regarding a water leakage issue in Palghar West.', 'contact']
        ];
        
        $inqStmt = $db->prepare("INSERT INTO inquiries (name, email, phone, message, type) VALUES (?, ?, ?, ?, ?)");
        foreach ($inquiries as $inq) {
            $inqStmt->execute($inq);
        }
        
        // Create lock file
        file_put_contents($lockFile, date("Y-m-d H:i:s") . " - Database initialized and seeded successfully.");
        $message = "Database configured and seeded successfully! Default Admin and Editor accounts created.";
    } catch (Exception $e) {
        $error = "Installation failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Setup Installer - Palghar LIVE</title>
    <!-- Core Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css?v=2">
</head>
<body style="display:flex; justify-content:center; align-items:center; min-height:100vh; background-color:var(--bg-main); font-family:sans-serif; margin:0; padding:20px; box-sizing:border-box;">
    
    <div class="form-card" style="max-width:550px; padding:35px; border:1px solid var(--border-color); border-radius:8px; background-color:var(--bg-card); width:100%;">
        <div style="text-align:center; margin-bottom:25px;">
            <img src="assets/images/WhatsApp Image 2026-06-29 at 2.29.31 PM.jpeg" alt="Logo" style="height:65px; margin-bottom:10px;" onerror="this.src='https://via.placeholder.com/150x70?text=PALGHAR+LIVE'">
            <h2 style="font-family:var(--font-heading); color:var(--text-primary); margin:0;">Database Setup Installer</h2>
            <p style="color:var(--text-muted); margin-top:5px; font-size:0.9rem;">Configure database structure and seed default mock values.</p>
        </div>

        <?php if (!empty($message)): ?>
            <div class="stat-card" style="background-color:#dcfce7; border-left:4px solid #15803d; padding:15px; border-radius:4px; margin-bottom:20px; display:flex; flex-direction:column; gap:8px;">
                <span style="color:#166534; font-weight:bold; display:flex; align-items:center; gap:8px;">✓ Success</span>
                <p style="color:#166534; margin:0; font-size:0.9rem; line-height:1.5;"><?php echo $message; ?></p>
                <div style="margin-top:10px; padding-top:10px; border-top:1px solid rgba(22,101,52,0.1); font-size:0.85rem; color:#166534;">
                    <strong>Default Accounts Created:</strong><br>
                    - Admin Username: <code style="background:rgba(255,255,255,0.5); padding:2px 4px; border-radius:3px;">admin</code> | Password: <code style="background:rgba(255,255,255,0.5); padding:2px 4px; border-radius:3px;">admin123</code><br>
                    - Editor A Username: <code style="background:rgba(255,255,255,0.5); padding:2px 4px; border-radius:3px;">usera</code> | Password: <code style="background:rgba(255,255,255,0.5); padding:2px 4px; border-radius:3px;">user123</code> (Assigned: Sports, Business)<br>
                    - Editor B Username: <code style="background:rgba(255,255,255,0.5); padding:2px 4px; border-radius:3px;">userb</code> | Password: <code style="background:rgba(255,255,255,0.5); padding:2px 4px; border-radius:3px;">user123</code> (Assigned: Palghar Local, Art & Culture)
                </div>
                <div style="margin-top:15px; display:flex; gap:10px;">
                    <a href="index.php" class="btn-live" style="box-shadow:none; text-decoration:none; padding:8px 16px; font-size:0.85rem;">Go to Home</a>
                    <a href="admin/login.php" class="btn-live" style="box-shadow:none; text-decoration:none; padding:8px 16px; font-size:0.85rem; background-color:var(--secondary); color:#fff;">Go to Admin Portal</a>
                </div>
            </div>
        <?php elseif (!empty($error)): ?>
            <div class="stat-card" style="background-color:#fee2e2; border-left:4px solid #b91c1c; padding:15px; border-radius:4px; margin-bottom:20px;">
                <span style="color:#991b1b; font-weight:bold; display:flex; align-items:center; gap:8px;">✗ Error</span>
                <p style="color:#991b1b; margin:5px 0 0 0; font-size:0.9rem; line-height:1.5;"><?php echo $error; ?></p>
            </div>
        <?php endif; ?>

        <?php if (empty($message)): ?>
            <form method="POST">
                <div style="padding:15px; background-color:var(--bg-main); border-radius:4px; border:1px dashed var(--border-color); margin-bottom:20px; font-size:0.88rem; line-height:1.6; color:var(--text-muted);">
                    <strong>Pre-installation Checklist:</strong>
                    <ol style="margin:5px 0 0 0; padding-left:20px;">
                        <li>Verify database configuration settings inside <code>config/db.php</code>.</li>
                        <li>Ensure the <code>schema.sql</code> file exists in the root folder.</li>
                        <li>Ensure upload directories <code>uploads/news/</code> and <code>uploads/sections/</code> exist and have write permissions.</li>
                    </ol>
                </div>
                
                <button type="submit" class="btn-submit" style="width:100%; padding:12px; font-weight:bold; font-size:1rem;">Run Installation & Seed Data</button>
            </form>
        <?php endif; ?>
    </div>

</body>
</html>
