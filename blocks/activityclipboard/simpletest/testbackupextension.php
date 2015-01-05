<?php

require_once($CFG->dirroot . 
     '/blocks/activityclipboard/backup_extension/activityclipboard_backup_coursefiles_step.class.php');

if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');
}

class test_backupextension extends UnitTestCase {
    function test_extract_legacy_filepaths() {
        $paths = activityclipboard_backup_coursefiles_step::extract_legacy_filepaths($this->content_nofiles);

        $this->assertTrue(is_array($paths));
        $this->assertTrue(empty($paths));

        $paths = activityclipboard_backup_coursefiles_step::extract_legacy_filepaths($this->content_files);
        $this->assertEqual(count($paths), 3);
        $this->assertEqual($paths[0], '/eightball.png');
        $this->assertEqual($paths[1], '/myfolder/progressbar.gif');
        $this->assertEqual($paths[2], '/e, 1.2~!@#$%^*()_+=-[]{};.png');
    }

    private $content_nofiles = <<<'EOT'
This is content without any
references to legacy course files.
EOT;

     private $content_files = <<<'EOT'
<?xml version="1.0" encoding="UTF-8"?>
<activity id="40" moduleid="130" modulename="label" contextid="223">
  <label id="40">
    <name>Label with multiple files.
</name>
    <intro>Label with multiple files.
&lt;div&gt;&lt;img height="64" width="64" src="$@FILEPHP@$$@SLASH@$eightball.png" /&gt;&lt;img height="19" width="220" src="$@FILEPHP@$$@SLASH@$myfolder$@SLASH@$progressbar.gif" /&gt;&lt;/div&gt;</intro>
    <introformat>1</introformat>
    <timemodified>1324672542</timemodified>
  </label>
</activity>
<?xml version="1.0" encoding="UTF-8"?>
<activity id="37" moduleid="127" modulename="label" contextid="220">
  <label id="37">
    <name>Label with eightball
</name>
    <intro>Label with eightball
&lt;div&gt;&lt;img alt="eightball" height="64" width="64" src="$@FILEPHP@$$@SLASH@$e%2C%201.2~%21%40%23%24%25%5E%2A%28%29_%2B%3D-%5B%5D%7B%7D%3B.png" /&gt;&lt;/div&gt;</intro>
    <introformat>1</introformat>
    <timemodified>1324666318</timemodified>
  </label>
</activity>
EOT;

}
