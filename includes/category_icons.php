<?php
// --- Category icons (Font Awesome 6 Free, solid style) ---
// The ONLY icons a category can use. The admin picks one from this list on
// the Categories page; the server rejects anything that isn't in it.

const CATEGORY_ICON_DEFAULT = 'fa-utensils';

const CATEGORY_ICONS = [
    // Bakery
    'fa-seedling'        => 'Manakish / Zaatar',
    'fa-cheese'          => 'Cheese',
    'fa-bread-slice'     => 'Bread / Kaak / Meat pastries',
    'fa-wheat-awn'       => 'Croissant / Pastries',
    'fa-leaf'            => 'Fatayer / Spinach',
    'fa-pizza-slice'     => 'Pizza',
    // Meat & mains
    'fa-cow'             => 'Meat / Veal',
    'fa-bone'            => 'Lamb',
    'fa-bacon'           => 'Filet / Meat cuts',
    'fa-drumstick-bite'  => 'Chicken',
    'fa-fire-burner'     => 'Grills / Oven',
    'fa-fish'            => 'Fish',
    'fa-shrimp'          => 'Seafood',
    'fa-bowl-food'       => 'Plates / Dishes',
    'fa-bowl-rice'       => 'Rice dishes',
    'fa-burger'          => 'Sandwiches / Burgers',
    'fa-hotdog'          => 'Hot dog',
    'fa-egg'             => 'Breakfast / Eggs',
    // Sweets
    'fa-cake-candles'    => 'Cakes / Desserts',
    'fa-cookie-bite'     => 'Cookies / Sweets',
    'fa-ice-cream'       => 'Ice cream',
    'fa-candy-cane'      => 'Candy',
    // Drinks
    'fa-mug-hot'         => 'Coffee / Hot drinks',
    'fa-lemon'           => 'Juice',
    'fa-bottle-water'    => 'Cold drinks',
    'fa-glass-water'     => 'Water',
    // Labels
    'fa-crown'           => 'Specialties',
    'fa-tags'            => 'Offers',
    'fa-fire'            => 'Best seller',
    'fa-pepper-hot'      => 'Spicy',
    'fa-carrot'          => 'Vegetarian',
    'fa-star'            => 'Featured',
    'fa-utensils'        => 'General',
];

// Returns a safe icon class for a category (falls back to the default).
function category_icon_class($icon) {
    return isset(CATEGORY_ICONS[$icon]) ? $icon : CATEGORY_ICON_DEFAULT;
}
