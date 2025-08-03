CREATE TABLE `users` (
                         `id` INT AUTO_INCREMENT PRIMARY KEY,
                         `username` VARCHAR(50) NOT NULL UNIQUE,
                         `email` VARCHAR(100) NOT NULL UNIQUE,
                         `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `categories` (
                              `id` INT AUTO_INCREMENT PRIMARY KEY,
                              `name` VARCHAR(50) NOT NULL UNIQUE,
                              `slug` VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `articles` (
                            `id` INT AUTO_INCREMENT PRIMARY KEY,
                            `title` VARCHAR(255) NOT NULL,
                            `slug` VARCHAR(255) NOT NULL UNIQUE,
                            `content` TEXT NOT NULL,
                            `author_id` INT NOT NULL,
                            `category_id` INT NOT NULL,
                            `published_at` DATETIME,
                            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                            FOREIGN KEY (`author_id`) REFERENCES `users`(`id`),
                            FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed data
INSERT INTO `users` (`id`, `username`, `email`) VALUES
    (1, 'Marianne', 'marilegrelle@gmail.com');

INSERT INTO `categories` (`id`, `name`, `slug`) VALUES
                                                    (1, 'European Classics', 'european-classics'),
                                                    (2, 'American Muscle', 'american-muscle');

INSERT INTO `articles` (`title`, `slug`, `content`, `author_id`, `category_id`, `published_at`) VALUES
                                                                                                    ('The Enduring Legacy of the Ferrari 250 GTO', 'ferrari-250-gto-legacy', 'The Ferrari 250 GTO is not just a car; it''s a legend. Produced from 1962 to 1964, only 36 were ever made. Its breathtaking design by Giotto Bizzarrini and Sergio Scaglietti, combined with a potent 3.0L Colombo V12 engine, made it a dominant force in motorsport. Today, it represents the pinnacle of car collecting, with auction prices reaching astronomical figures. The GTO is more than metal; it''s a symbol of an era where beauty and performance were one.', 1, 1, '2025-07-20 10:00:00'),
                                                                                                    ('Jaguar E-Type: The Most Beautiful Car Ever Made?', 'jaguar-e-type-beauty', 'When it was unveiled in 1961, Enzo Ferrari himself reportedly called the Jaguar E-Type "the most beautiful car ever made." With its long, elegant bonnet, sleek lines, and impressive performance for its time, the E-Type captured the world''s imagination. It offered the performance of a supercar for a fraction of the price, making it an instant icon of the Swinging Sixties. Its legacy endures as a masterpiece of automotive design.', 1, 1, '2025-07-21 11:30:00'),
                                                                                                    ('The Unmistakable Roar of the 1969 Ford Mustang Boss 429', 'ford-mustang-boss-429', 'The Boss 429 is the stuff of American muscle car legend. Created to homologate Ford''s new semi-hemispherical 429 V8 engine for NASCAR, this Mustang was a beast in street clothing. With a massive hood scoop and a thunderous exhaust note, it was pure intimidation. While underrated in its day, the Boss 429 is now one of the most sought-after and valuable Mustangs ever produced, a true testament to the "win on Sunday, sell on Monday" philosophy.', 1, 2, '2025-07-22 14:00:00');
