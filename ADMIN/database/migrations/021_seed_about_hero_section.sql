-- Migration 021: Seed the About Us page hero banner as an editable section
INSERT IGNORE INTO `about_content` (`section_key`, `title`, `content`, `image`) VALUES
('hero', 'Discover St.Monica Junior School Kasanje', 'Nurturing minds, building character, and fostering a vibrant community since 1995. We are dedicated to providing a holistic education that empowers every child to flourish.', 'assets/imgz/school building.webp');
