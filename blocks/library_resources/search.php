<?php

require('../../config.php');

$search_domain  = required_param('library_search_domain', PARAM_TEXT);
$search_text    = required_param('library_search_text', PARAM_TEXT);

$search_text = urlencode($search_text);    // to be embedded in the URL

// dispatch the submitted action
$search_url = get_config('library_resources', 'se_'.$search_domain);

if ($search_url == false) {
    print_error('invalid_domain', 'block_library_resources', '', $search_domain);
}
else {
    redirect(str_replace('{$QUERY}', $search_text, $search_url));
}
