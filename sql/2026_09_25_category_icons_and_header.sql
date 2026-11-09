-- Category icons (Font Awesome 6 Free class names, chosen in admin > Categories)
-- and header settings (tagline + opening/closing times for the "Open now" badge).
-- Run once on each database.

ALTER TABLE categories
    ADD COLUMN icon VARCHAR(40) NULL AFTER name_ar;

UPDATE categories SET icon = CASE id
    WHEN 1  THEN 'fa-seedling'        -- Manakish
    WHEN 2  THEN 'fa-burger'          -- Sandwiches
    WHEN 3  THEN 'fa-drumstick-bite'  -- Chicken
    WHEN 4  THEN 'fa-cow'             -- Veal
    WHEN 5  THEN 'fa-bone'            -- Lamb (Font Awesome Free has no sheep icon)
    WHEN 6  THEN 'fa-bacon'           -- Veal Filet (a cut of meat)
    WHEN 7  THEN 'fa-bread-slice'     -- Meat Pastries (baked dough)
    WHEN 8  THEN 'fa-bowl-food'       -- Plates
    WHEN 9  THEN 'fa-pizza-slice'     -- Pizza
    WHEN 10 THEN 'fa-fire-burner'     -- Oven Grills
    WHEN 11 THEN 'fa-wheat-awn'       -- Pastries (Party Trays)
    WHEN 12 THEN 'fa-crown'           -- Najwa Specialties
    WHEN 13 THEN 'fa-bottle-water'    -- Drinks
    ELSE icon
END;

ALTER TABLE business_settings
    ADD COLUMN tagline VARCHAR(150) NULL AFTER business_name_ar,
    ADD COLUMN open_time TIME NULL AFTER hours_ar,
    ADD COLUMN close_time TIME NULL AFTER open_time;

-- Placeholder tagline (edit it in admin > Settings) and the current hours
-- ("يومياً 8:00 ص - 4:00 م" = daily 8:00 AM to 4:00 PM).
UPDATE business_settings
SET tagline = 'Freshly baked every morning',
    open_time = '08:00:00',
    close_time = '16:00:00';
