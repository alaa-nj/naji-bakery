<?php
// Visiting the site root (e.g. https://najibakery.com/) opens the public menu.
header('Location: public/index.php', true, 301);
exit;
