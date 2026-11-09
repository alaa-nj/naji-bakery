-- Show every price in US dollars.
-- Lebanese pound prices are converted at 90,000 L.L. per $1, kept to exact
-- cents (e.g. 250,000 L.L. -> $2.78). New items default to USD.
-- Run once on each database (already applied to the local database that gets
-- exported to the server).

UPDATE menu_items
SET price = ROUND(price / 90000, 2),
    currency = 'USD'
WHERE currency = 'LBP';

ALTER TABLE menu_items
    MODIFY currency ENUM('LBP','USD') NOT NULL DEFAULT 'USD';
