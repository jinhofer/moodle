<?php

$settings->add(new admin_setting_heading(
    'headerconfig',
    get_string('gconfig_header', 'block_library_resources'),
    get_string('gconfig_desc', 'block_library_resources')
));

$locations = array(
    'tc' => array('lib'       => 'http://www.lib.umn.edu',
                  'cat'       => 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?vid=TWINCITIES',
                  'guide'     => '',
                  'chat'      => 'http://www.questionpoint.org/crs/servlet/org.oclc.home.TFSRedirect?virtcategory=12947',   //TODO: 404s
                  'personal'  => 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?vid=TWINCITIES&redirectTo=myAccount'),

    'cr' => array('lib'       => 'http://www1.crk.umn.edu/library',
                  'cat'       => 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?vid=DULUTH',   //TODO: should be CROOKSTON
                  'guide'     => 'http://www1.crk.umn.edu/library/researchresources/index.html',
                  'chat'      => 'http://www1.crk.umn.edu/library/contact/index.html',
                  'personal'  => 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?vid=CROOKSTON&redirectTo=myAccount'),

    'dl' => array('lib'       => 'http://www.duluth.umn.edu/lib',
                  'cat'       => 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?vid=CROOKSTON',    //TODO: should be DULUTH
                  'guide'     => 'http://libguides.d.umn.edu/umdguides',
                  'chat'      => 'http://www.d.umn.edu/lib/askus/index.htm',
                  'personal'  => 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?vid=DULUTH&redirectTo=myAccount'),

    'mr' => array('lib'       => 'http://www.morris.umn.edu/library/index.php',
                  'cat'       => 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?vid=MORRIS',
                  'guide'     => 'http://morris.umn.libguides.com/',
                  'chat'      => 'http://www.morris.umn.edu/library/askalibrarian/',
                  'personal'  => 'http://primo.lib.umn.edu/primo_library/libweb/action/search.do?vid=MORRIS&redirectTo=myAccount')
);

foreach ($locations as $location => $default) {
    $settings->add(new admin_setting_configtext(
            "library_resources/link_librarian_chat_{$location}",
            get_string("config_{$location}_librarian_chat", 'block_library_resources'),
            '',
            $default['chat']
    ));

    $settings->add(new admin_setting_configtext(
        "library_resources/link_{$location}_lib",
        get_string("config_{$location}_lib", 'block_library_resources'),
        '',
        $default['lib']
    ));

    $settings->add(new admin_setting_configtext(
        "library_resources/link_{$location}_cat",
        get_string("config_{$location}_cat", 'block_library_resources'),
        '',
        $default['cat']
    ));

    $settings->add(new admin_setting_configtext(
            "library_resources/link_{$location}_rs_guide",
            get_string("config_{$location}_rs_guide", 'block_library_resources'),
            '',
            $default['guide']
    ));

    $settings->add(new admin_setting_configtext(
            "library_resources/link_{$location}_personal",
            get_string("config_{$location}_personal", 'block_library_resources'),
            '',
            $default['personal']
    ));

}

// links for course resources and reserves
$settings->add(new admin_setting_configtext(
    "library_resources/link_course_resources",
    get_string("config_course_resources", 'block_library_resources'),
    get_string("config_course_resources_desc", 'block_library_resources'),
    'http://www.lib.umn.edu/course/{SUBJECT}/{NUMBER}#genres'
));

// search engine URLS
$search_engines = array(
    'ebscohost'           => 'https://www.lib.umn.edu/apps/lcp/searchtext.phtml?bquery={$QUERY}',
    'articlediscovery_tc' => 'http://primo.lib.umn.edu/primo_library/libweb/action/dlSearch.do?institution=TWINCITIES'.
                             '&vid=TWINCITIES&indx=1&dym=true&highlight=true&lang=eng&search_scope=mncat_discovery'.
                             '&query=any,contains,{$QUERY}',
    'articlediscovery_cr' => 'http://primo.lib.umn.edu/primo_library/libweb/action/dlSearch.do?institution=CROOKSTON'.
                             '&vid=CROOKSTON&indx=1&dym=true&highlight=true&lang=eng&tab=primocentral&'.
                             'search_scope=Primo_Central&query=any,contains,{$QUERY}',
    'articlediscovery_dl' => 'http://primo.lib.umn.edu/primo_library/libweb/action/dlSearch.do?institution=DULUTH'.
                             '&vid=DULUTH&indx=1&dym=true&highlight=true&lang=eng&tab=blended&search_scope=blended'.
                             '&query=any,contains,{$QUERY}',
    'articlediscovery_mr' => 'http://primo.lib.umn.edu/primo_library/libweb/action/dlSearch.do?institution=MORRIS'.
                             '&vid=MORRIS&indx=1&dym=true&highlight=true&lang=eng&tab=primocentral&search_scope=Primocentral'.
                             '&query=any,contains,{$QUERY}',
    'catalog_dl'          => 'http://primo.lib.umn.edu/primo_library/libweb/action/dlSearch.do?institution=DULUTH'.
                             '&onCampus=false&indx=1&displayField=title&query=any,contains,{$QUERY}'.
                             '&loc=local,scope:(dulsearch)&dym=true&group=GUEST&highlight=true&vid=DULUTH',
    'catalog_mr'          => 'http://primo.lib.umn.edu/primo_library/libweb/action/dlSearch.do?institution=MORRIS'.
                             '&vid=MORRIS&onCampus=false&indx=1&dym=true&highlight=true&lang=eng&group=GUEST&fromSitemap=1'.
                             '&loc=local,scope:(allsearch)&vl(freeText0)={$QUERY}&query=any,contains,{$QUERY}',
    'catalog_cr'          => 'http://primo.lib.umn.edu/primo_library/libweb/action/dlSearch.do?institution=CROOKSTON'.
                             '&vid=CROOKSTON&onCampus=false&indx=1&dym=true&highlight=true&lang=eng&group=GUEST&fromSitemap=1'.
                             '&loc=local,scope:(allsearch)&vl(freeText0)={$QUERY}&query=any,contains,{$QUERY}',
    'catalog_tc'          => 'https://www.lib.umn.edu/libinc/articlesearch.php?src=mncat&query={$QUERY}',
    //STRY0010378 20140606 mart0969 - Add PubMed search
    'pubmed_tc'           => 'http://www.ncbi.nlm.nih.gov/pubmed/?otool=umnbmlib&term={$QUERY}',
    'pubmed_dl'           => 'http://www.ncbi.nlm.nih.gov/pubmed/?otool=umndlib&term={$QUERY}',
    'pubmed_gen'          => 'http://www.ncbi.nlm.nih.gov/pubmed/?term={$QUERY}'
);

foreach ($search_engines as $engine => $url) {
    $settings->add(new admin_setting_configtext(
        'library_resources/se_'.$engine,
        get_string('config_se_'.$engine, 'block_library_resources'),
        '',
        $url
    ));
}
