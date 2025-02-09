<?php
// Security check
if (!defined('_PS_VERSION_')) {
    exit;
}

// Prevent directory listing
http_response_code(403);
exit('Access Denied');
