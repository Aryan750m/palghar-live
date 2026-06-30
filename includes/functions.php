<?php
// Global Utilities: includes/functions.php
// Common functions, sanitization helpers, date formatting, and database setup installer for Phase 1

require_once __DIR__ . '/../config/db.php';

// Prevent XSS
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Format SQL Datetime to Human Readable format
function formatDate($dateString) {
    if (empty($dateString)) return '';
    $timestamp = strtotime($dateString);
    return date("M d, Y, h:i A", $timestamp);
}

// Category mappings
function getCategoryLabel($catId) {
    $mapping = [
        'local' => 'Palghar Local',
        'state' => 'Maharashtra',
        'national' => 'National & International',
        'sports' => 'Sports',
        'business' => 'Business',
        'culture' => 'Art & Culture'
    ];
    return $mapping[$catId] ?? ucfirst($catId);
}

// Color badges mappings
function getCategoryColor($catId) {
    $mapping = [
        'local' => 'var(--primary)',
        'state' => '#f59e0b',     // Amber
        'national' => '#0ea5e9',  // Sky Blue
        'sports' => 'var(--secondary)',
        'business' => '#10b981',  // Emerald Green
        'culture' => '#8b5cf6'    // Violet Purple
    ];
    return $mapping[$catId] ?? 'var(--text-muted)';
}

// Retrieve dynamic weather info helper
function getWeatherTemp() {
    return (28 + (date('H') % 5)) . "°C";
}

// Reset Database function (Serves as dynamic installer)
function resetDatabaseToDefaults() {
    $db = getDatabaseConnection();
    
    // 1. Run tables schema creation
    $sqlPath = __DIR__ . '/../schema.sql';
    if (!file_exists($sqlPath)) {
        throw new Exception("schema.sql file not found.");
    }
    $sql = file_get_contents($sqlPath);
    $db->exec($sql);
    
    // 2. Populate Sections
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
    
    // 3. Populate Section Images (Initial landing galleries)
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
    
    // 4. Populate default Articles
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
    
    // 5. Populate default Comments
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
    
    // 6. Populate default Inquiries
    $inquiries = [
        ['Suresh Patil', 'suresh@gmail.com', '9876543210', 'We want to advertise the grand opening of our new store in Boisar. What are your advertising rates?', 'ad'],
        ['Rahul Mehta', 'rahul@mehta.com', '9988776655', 'I want to submit a tip regarding a water leakage issue in Palghar West.', 'contact']
    ];
    
    $inqStmt = $db->prepare("INSERT INTO inquiries (name, email, phone, message, type) VALUES (?, ?, ?, ?, ?)");
    foreach ($inquiries as $inq) {
        $inqStmt->execute($inq);
    }
}
