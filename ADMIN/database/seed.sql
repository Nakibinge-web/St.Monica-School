-- ==========================================================
-- St. Monica Junior School - Initial Database Seed Data
-- ==========================================================

USE `st_monica`;

-- 1. Default Administrator
-- Default credentials:
-- Email: admin@stmonicakasanje.ac.ug
-- Password: Admin@2026!
INSERT INTO `admins` (`id`, `name`, `email`, `password`, `role`, `created_at`)
VALUES
(1, 'Administrator', 'admin@stmonicakasanje.ac.ug', '$2y$12$GA.4so1kgGUU7ken/D/TC.1/vj8z3G7PQArtiAgrPcUqjTz6P/WbS', 'administrator', NOW())
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`);

-- 2. Hero Slides
INSERT INTO `hero_slides` (`id`, `title`, `subtitle`, `description`, `image`, `button_text`, `button_url`, `display_order`, `status`)
VALUES
(1, 'Welcome to St.Monica Junior School', 'Welcome Community', 'Welcome to St.Monica Junior School website and community. Explore all the school information you need to know from here', 'assets/imgz/hero1-light.webp', 'Know More', 'about.html', 1, 'active'),
(2, 'Providing Quality Education', 'Academic Excellence', 'We provide quality education to our pupils for a bright future and build skills for success in a global world', 'assets/imgz/hero2.webp', 'Our Academics', 'academics.html', 2, 'active'),
(3, 'Beautiful Compound And Structures', 'Modern Facilities', 'St.Monica boosts a serene and beautiful compound, complete with modern structures to provide a safe and condusive learning environment to our pupils', 'assets/imgz/hero3.webp', 'Explore Our Gallery', 'gallery.html', 3, 'active'),
(4, 'Co-curricular Activities', 'Holistic Growth', 'At St.Monica, our pupils engage in co-curricular activities and we have a fully stocked Library that allows our pupils to seek more knowledge', 'assets/imgz/farming.webp', 'Co-curricular Activities', 'cocurricular.html', 4, 'active')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`);

-- 3. Homepage Sections
INSERT INTO `homepage_sections` (`id`, `section_key`, `title`, `subtitle`, `content`, `image`, `author_name`, `author_title`)
VALUES
(1, 'director_message', 'Message from Our Director', 'Leadership Message', 'Welcome to St. Monica Junior School! We are dedicated to providing a nurturing environment where every pupil can thrive. Our focus on holistic and Christian development ensures that each child reaches their full potential, both academically and personally.\n\nOur commitment to excellence is reflected in our UNEB Center number, which guarantees that our pupils are well-prepared for national exams. We use innovative teaching methods to engage students, fostering a lifelong love of learning.\n\nIn addition to academics, we offer a range of co-curricular activities to enhance overall development. Our balanced approach supports pupils\' growth in both their academic and personal lives, preparing them for future success.', 'assets/imgz/Director-copy.jpg', 'Rev. Fr. Dr Denis Mpanga', 'Director'),
(2, 'why_choose_intro', 'Why Choose St Monica?', 'Nurturing Environment', 'We are committed to providing a nurturing environment where each child can thrive academically, socially, and emotionally.', 'assets/imgz/Communion.webp', NULL, NULL)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `content` = VALUES(`content`);

-- 4. Why Choose Us Items
INSERT INTO `why_choose_us_items` (`id`, `title`, `description`, `icon`, `color_theme`, `display_order`, `status`)
VALUES
(1, 'Children\'s Safety', 'At St Monica Junior School, Our children\'s safety is key', 'fa-solid fa-children', 'navy', 1, 'active'),
(2, 'Healthy Meals', 'Healthy food is key to a healthy mind', 'fa-solid fa-bowl-food', 'red', 2, 'active'),
(3, 'Learning & Fun', 'Creative activities to help with development and early learning.', 'fa-solid fa-book-open', 'navy', 3, 'active'),
(4, 'Cute Environment', 'Encourages children to freely explore, discover and learn through nature', 'fa-solid fa-building', 'red', 4, 'active')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`);

-- 5. Statistics
INSERT INTO `statistics` (`id`, `number_value`, `suffix`, `label`, `icon`, `display_order`, `status`)
VALUES
(1, 10, '+', 'Years of Excellence', 'verified', 1, 'active'),
(2, 500, '+', 'Happy Pupils', 'school', 2, 'active'),
(3, 10, '', 'Modern Classrooms', 'meeting_room', 3, 'active')
ON DUPLICATE KEY UPDATE `number_value` = VALUES(`number_value`), `label` = VALUES(`label`);

-- 6. Staff
INSERT INTO `staff` (`id`, `name`, `position`, `department`, `biography`, `email`, `photo`, `display_order`, `is_featured`, `status`)
VALUES
(1, 'Rev. Fr. Dr Denis Mpanga', 'Director', 'Administration', 'With over 15 years of experience in educational leadership, Rev. Fr. Dr. Denis brings a wealth of knowledge and passion to our school community.', 'headteacher@stmonicakasanje.ac.ug', 'assets/imgz/Director-copy.jpg', 1, 1, 'published'),
(2, 'Ms Jane Frances Luyiga', 'Headteacher', 'Administration', 'Ms. Jane oversees academic programs and ensures our curriculum meets the highest standards of educational excellence.', 'deputy@stmonicakasanje.ac.ug', 'assets/imgz/headteacher.webp', 2, 1, 'published'),
(3, 'Mr. Isaac Omuge', 'Deputy Headteacher', 'Administration', 'Mr. Isaac coordinates academic activities, inspiring young minds and ensuring continuous improvement in learning outcomes.', 'dos@stmonicakasanje.ac.ug', 'assets/imgz/Deputy HM.webp', 3, 1, 'published')
ON DUPLICATE KEY UPDATE `name` = VALUES(`name`), `position` = VALUES(`position`);

-- 7. News & Events
INSERT INTO `news_events` (`id`, `title`, `slug`, `type`, `excerpt`, `content`, `featured_image`, `event_date`, `event_location`, `status`, `published_at`)
VALUES
(1, 'Top Class Graduation Day', 'top-class-graduation-day', 'event', 'Celebrating our top class students for reaching another educational level', 'We are thrilled to invite all parents, guardians, and well-wishers to our annual Top Class Graduation Day. Our pupils have shown tremendous growth, acquiring essential foundational literacy, numeracy, and social skills.', 'assets/imgz/3 graduants.webp', '2026-10-15', 'St. Monica Main Hall', 'published', NOW()),
(2, 'Inter-House Athletics', 'inter-house-athletics', 'sports', 'Join us for an exciting day of sports and competition.', 'The annual Inter-House Sports and Athletics Competitions bring together our entire school community for a day of friendly competition, camaraderie, and athletic excellence.', 'assets/imgz/netball.webp', '2026-10-22', 'School Sports Complex', 'published', NOW()),
(3, 'Study Trip', 'study-trip', 'news', 'Our trip is happening soon so that pupils get to learn visually and see new places', 'Experiential learning is a vital cornerstone of education at St. Monica Junior School. Our upcoming educational study trip will allow pupils to discover science, history, and nature outside the traditional classroom environment.', 'assets/imgz/swimming.webp', '2026-10-05', 'Entebbe Wildlife Education Centre', 'published', NOW())
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `excerpt` = VALUES(`excerpt`);

-- 8. Gallery
INSERT INTO `gallery` (`id`, `title`, `description`, `file_path`, `file_type`, `category`, `display_order`, `status`)
VALUES
(1, 'Science Fair Prep', 'Pupils actively engaging in practical science projects and experiments.', 'assets/imgz/studying.webp', 'image', 'Academics', 1, 'published'),
(2, 'Inter-House Football', 'Thrilling athletic moments from our inter-house sports tournaments.', 'assets/imgz/netball.webp', 'image', 'Co-curricular Activities', 2, 'published'),
(3, 'Main Quadrangle', 'The pristine, serene, and spacious compound of St. Monica Junior School.', 'assets/imgz/school building.webp', 'image', 'Campus Life', 3, 'published'),
(4, 'Creative Arts Class', 'Inspiring creative expression through crafts, modeling, and painting.', 'assets/imgz/compund and kids.webp', 'image', 'Academics', 4, 'published'),
(5, 'Annual Cultural Day', 'Vibrant dances, traditional attire, and drama celebrations.', 'assets/imgz/dancing.webp', 'image', 'Special Events', 5, 'published'),
(6, 'The Library Hub', 'A quiet, well-stocked sanctuary fostering a love for reading.', 'assets/imgz/farming.webp', 'image', 'Campus Life', 6, 'published'),
(7, 'Physical Training & Swimming', 'Promoting water safety, confidence, and motor agility.', 'assets/imgz/swimming2.webp', 'image', 'Co-curricular Activities', 7, 'published'),
(8, 'Top Class Graduates', 'Honoring milestones and building confident young leaders.', 'assets/imgz/3 graduants.webp', 'image', 'Special Events', 8, 'published')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `category` = VALUES(`category`);

-- 9. About Content
INSERT INTO `about_content` (`id`, `section_key`, `title`, `content`, `image`)
VALUES
(1, 'history', 'Our History', 'Founded in 1995 by a group of passionate educators in Kampala, St. Monica Junior School began with a simple vision: to create a learning environment where academic rigor meets holistic personal development.\n\nStarting with just three classrooms and 45 students, our commitment to excellence quickly resonated with the community. Over the decades, we have grown into a premier institution, expanding our campus and enriching our curriculum to meet global standards while staying deeply rooted in Ugandan values.\n\nToday, St. Monica Junior School stands as a beacon of growth, community, and academic excellence, proudly shaping the leaders of tomorrow.', 'assets/imgz/school building.webp'),
(2, 'vision', 'Our Vision', 'To provide a happy, safe, and loving environment thus providing the highest quality care possible to the children. Rather than simply operating a daycare, we envision a facility where education including Christian education is an essential component of the child upbringing where the children we teach will reach their full human potential and later serve as responsible citizens.', NULL),
(3, 'mission', 'Our Mission', 'St. Monica Junior School exists to serve the unique academic, physical, social, and emotional needs of children during that tender age. The staff is committed to creating and maintaining an orderly, trusting, and environment where teaching and learning are exciting and children are assisted as they grow.', NULL),
(4, 'motto', 'Our Motto', '\"Always Aim Higher\"\n\nWe believe that every child has the potential to achieve greatness. Through dedicated teaching, a supportive environment, and a commitment to high standards, we strive for excellence in all aspects of school life.', NULL),
(5, 'support_cta', 'Support St.Monica', 'Your generous support helps us nurture bright futures, provide quality education, and empower the leaders of tomorrow.', 'assets/imgz/Donate Image.webp')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `content` = VALUES(`content`);

-- 10. Core Values
INSERT INTO `core_values` (`id`, `title`, `display_order`, `status`)
VALUES
(1, 'Fearing God', 1, 'active'),
(2, 'Discipline', 2, 'active'),
(3, 'Compassion', 3, 'active'),
(4, 'Integrity', 4, 'active'),
(5, 'Respect and Preservation of Nature', 5, 'active'),
(6, 'A Commitment to Excellence & Quality', 6, 'active'),
(7, 'Parent, Teacher & Student Collaboration', 7, 'active')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 11. Facilities
INSERT INTO `facilities` (`id`, `title`, `description`, `image`, `display_order`, `status`)
VALUES
(1, 'Modern Library', 'A vast collection of resources fostering a lifelong love for reading and research.', 'assets/imgz/school building.webp', 1, 'active'),
(2, 'General Knowledge', 'Our General Knowledge program introduces children to a variety of topics, sparking curiosity and a love for learning through interactive activities.', 'assets/imgz/compund and kids.webp', 2, 'active'),
(3, 'Sports Complex', 'Expansive fields and courts supporting a wide range of athletic activities.', 'assets/imgz/hero5.webp', 3, 'active'),
(4, 'Communication and language', 'Fostering strong expression, active listening, and vocabulary through interactive learning and storytelling.', 'assets/imgz/studying.webp', 4, 'active'),
(5, 'Physical Development', 'Promoting health, coordination, agility, and motor skills through structured athletics and outdoor play.', 'assets/imgz/swimming2.webp', 5, 'active'),
(6, 'Expressive Arts and design', 'Nurturing creativity, imagination, music, drama, and artistic expression across all age levels.', 'assets/imgz/dancing.webp', 6, 'active')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `description` = VALUES(`description`);

-- 12. Contact Information
INSERT INTO `contact_information` (`id`, `school_name`, `address`, `village`, `district`, `country`, `phone`, `alternative_phone`, `email`, `admissions_email`, `opening_hours`, `facebook`, `instagram`, `youtube`, `whatsapp`, `map_url`)
VALUES
(1, 'St. Monica Junior School Kasanje', 'Kasanje Village, Wakiso District, Uganda', 'Kkoba village, Kasanje', 'Wakiso District', 'Uganda', '+256 752 406176', '+256 762636213', 'stmonicajuniorschool2012@gmail.com', 'info@stmonicakasanje.ac.ug', 'Mon - Fri: 7:00 AM - 5:00 PM | Saturday: 8:00 AM - 1:00 PM', 'https://facebook.com', 'https://instagram.com', 'https://youtube.com', '+256752406176', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.8027083140364!2d32.400966173966786!3d0.1602842642463911!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x177d9d78438ea433%3A0x1a41e79700174128!2sSt%20Monica%20Junior%20School%2C%20Kasanjye!5e0!3m2!1sen!2sug!4v1724341837360!5m2!1sen!2sug')
ON DUPLICATE KEY UPDATE `school_name` = VALUES(`school_name`), `phone` = VALUES(`phone`), `email` = VALUES(`email`);

-- 13. Activity Logs Initial Seed
INSERT INTO `activity_logs` (`id`, `admin_id`, `admin_name`, `action`, `details`, `ip_address`, `created_at`)
VALUES
(1, 1, 'System', 'System Initialized', 'CMS database initialized with default school content.', '127.0.0.1', NOW());
