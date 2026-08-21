<?php
// Configuration Settings

// Direct CSV export link for the Google Sheet.
// Note: For this to work, the sheet must be set to "Anyone with the link can view".
// Sheet ID: 1dgw0kLOxyUwDoD7MK6HDvAciU6-N5IXjCsm7si1p8cw
define('GSHEET_CSV_URL', 'https://docs.google.com/spreadsheets/d/1dgw0kLOxyUwDoD7MK6HDvAciU6-N5IXjCsm7si1p8cw/export?format=csv&id=1dgw0kLOxyUwDoD7MK6HDvAciU6-N5IXjCsm7si1p8cw&gid=0');

// Login Credentials
// Everyone has equal tier, no roles.
define('APP_USERS', [
    'james@SolutionsIMPACT.com' => 'TransformationConsult',
    'tangmo@SolutionsIMPACT.com' => 'TransformationConsult'
]);

// Security key for the "remember me" cookie
define('SECRET_KEY', 'solutions_impact_recruit_secret_key_2026');
?>
