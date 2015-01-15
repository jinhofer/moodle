<?php
$string['pluginname'] = 'Library resources';
$string['blockname'] = 'Library resources';
$string['blocktitle'] = 'Library resources';
$string['help_title'] = 'Library resources help';
$string['library_resources:addinstance'] = 'Add a new library resources block';

/*
 * Prefix convention:
 *     ui_        strings that appear on the block when displayed in course page
 *     edit_      strings for the edit form (instance config)
 *     config_    strings for the site-wide admin configs
 *     se_        search-engines
 */


$string['twincities'] = 'TwinCities';
$string['crookston'] = 'Crookston';
$string['duluth'] = 'Duluth';
$string['morris'] = 'Morris';

$string['tc'] = 'TC';
$string['cr'] = 'Crookston';
$string['dl'] = 'Duluth';
$string['mr'] = 'Morris';


// ========= BLOCK UI ===========
$string['ui_se_articlediscovery'] = 'Article discovery';
$string['ui_se_ebscohost']        = 'EBSCOhost';
$string['ui_se_catalog']          = 'Catalog';

$string['ui_course_resource'] = 'Find articles and books for {$a}';
$string['ui_course_reserves'] = 'Access course readings';
$string['ui_search_in'] = 'Search in';


foreach (array('tc', 'dl', 'cr', 'mr') as $campus) {
    $string['ui_personal_account_'.$campus]  = 'Your library account';
    $string['ui_librarian_chat_'.$campus]    = 'Chat with a librarian ('.$string[$campus].')';
    $string['ui_lib_homepage_'.$campus]      = $string[$campus].' Libraries homepage';
    $string['ui_catalog_'.$campus]           = $string[$campus].' Catalog';
    $string['ui_research_guide_'.$campus]    = $string[$campus].' research guides';
    $string['ui_se_articlediscovery_'.$campus] = 'Books, articles, etc.';
    $string['ui_se_catalog_'.$campus]        = $string[$campus].' Catalog';

    $string['edit_se_catalog_'.$campus]           = $string[$campus].' Catalog';
    $string['edit_lib_homepage_'.$campus]    = $string[$campus].' Libraries homepage';
    $string['edit_catalog_'.$campus]         = $string[$campus].' Catalog';
    $string['edit_research_guide_'.$campus]  = $string[$campus].' research guide';
}

$string['ui_se_pubmed_gen']         = 'PubMed';
$string['ui_se_pubmed_tc']          = 'Twin Cities PubMed';
$string['ui_se_pubmed_dl']          = 'Duluth PubMed';

// ========= EDIT FORM ==========
$string['edit_visibility_header'] = 'What to display in this block:';
$string['edit_library_search'] = 'Library search';
$string['edit_course_resources'] = 'Course-specific resources';
$string['edit_course_reserves'] = 'Course reserves';
$string['edit_personal_account'] = 'Link to personal account';
$string['edit_librarian_chat'] = 'Chat with a librarian';
$string['edit_library_homepage'] = 'Show library homepage';
$string['edit_catalog'] = 'Show catalog';
$string['edit_research_guide'] = 'Show research guides';
$string['-----'] = '---------------------';
$string['edit_search_engine_header'] = 'Display these search engines in library search:';

$string['edit_se_articlediscovery'] = 'Books, articles, etc.';
$string['edit_se_ebscohost']        = 'EBSCOhost';
$string['edit_se_catalog']          = 'Catalog';
$string['edit_se_pubmed']           = 'PubMed';


// ========= global configs (links) ===========
$string['gconfig_header'] = 'Library resources links';
$string['gconfig_desc'] = 'Customize the base of the links to be generated in library resources block';

$string['config_personal_account'] = 'Personal account link';

foreach (array('tc', 'dl', 'cr', 'mr') as $campus) {
    $string['config_'.$campus.'_lib']             = $string[$campus].' Library link';
    $string['config_'.$campus.'_cat']             = $string[$campus].' Catalog link';
    $string['config_'.$campus.'_rs_guide']        = $string[$campus].' Research guides link';
    $string['config_'.$campus.'_personal']        = $string[$campus].' Personal account link';
    $string['config_'.$campus.'_librarian_chat']  = $string[$campus].' Librarian chat link';

    $string['config_se_articlediscovery_'.$campus]   = $string[$campus].' Books, articles, etc. search engine';
    $string['config_se_catalog_'.$campus]            = $string[$campus].' Catalog search engine';
}

$string['config_se_ebscohost'] = 'EBSCOhost search engine';
$string['config_course_resources'] = 'Course resources link';
$string['config_course_resources_desc'] = 'Base link to course resources. Use {SUBJECT} and {NUMBER} as placeholders';

$string['config_se_pubmed_tc']      = 'TC PubMed search engine';
$string['config_se_pubmed_dl']      = 'Duluth PubMed search engine';
$string['config_se_pubmed_gen'] = 'General PubMed search engine';

// errors
$string['invalid_domain'] = 'Unknown search engine: {$a}';
$string['no_engine_error'] = 'You must select at least one search engine';

$string['general']                  = 'General';
