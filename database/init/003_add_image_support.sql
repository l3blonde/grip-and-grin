-- Add image columns to articles table
ALTER TABLE articles ADD COLUMN image_original_path VARCHAR(255) NULL;
ALTER TABLE articles ADD COLUMN image_thumbnail_path VARCHAR(255) NULL;
ALTER TABLE articles ADD COLUMN image_medium_path VARCHAR(255) NULL;
ALTER TABLE articles ADD COLUMN image_full_path VARCHAR(255) NULL;
ALTER TABLE articles ADD COLUMN image_alt_text VARCHAR(255) NULL;
ALTER TABLE articles ADD COLUMN image_width INT NULL;
ALTER TABLE articles ADD COLUMN image_height INT NULL;

-- Add some sample image paths to existing articles (placeholder paths)
UPDATE articles SET
                    image_thumbnail_path = '/uploads/thumbnails/sample-car-1.webp',
                    image_medium_path = '/uploads/medium/sample-car-1.webp',
                    image_full_path = '/uploads/full/sample-car-1.webp',
                    image_alt_text = 'Classic car featured image',
                    image_width = 1200,
                    image_height = 800
WHERE id <= 5;

UPDATE articles SET
                    image_thumbnail_path = '/uploads/thumbnails/sample-car-2.webp',
                    image_medium_path = '/uploads/medium/sample-car-2.webp',
                    image_full_path = '/uploads/full/sample-car-2.webp',
                    image_alt_text = 'Collector car featured image',
                    image_width = 1200,
                    image_height = 800
WHERE id > 5 AND id <= 10;
