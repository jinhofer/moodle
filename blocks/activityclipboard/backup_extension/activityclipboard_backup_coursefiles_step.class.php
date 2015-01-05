<?php

// This causes course files referenced by the activity being backed up
// to be included in the backup. Otherwise, the restored activity would
// not be able to access the file.

// Some ideas from implementation of function annotate_files (in
// backup/util/dbops/backup_structure_dbops.class.php).

class activityclipboard_backup_coursefiles_step extends backup_execution_step {

    protected function define_execution() {

        // Grep XML for $@FILEPHP@$.  With each such file determine the
        // values to get the fileid from storage.  See function process_cdata
        // in backup/util/helper/restore_structure_parser_processor.class.php
        // for logic to unescape SLASH and FORCEDOWNLOAD.
        // After finding file id, use insert_backup_ids_record as in
        // backup/util/dbops/backup_structure_dbops.class.php.

        $basepath = $this->get_basepath();

        $this->process_files_recursively($basepath, array($this, 'process_file'));
    }

    private function process_files_recursively($path, $callback) {

        $fh = opendir($path);
        while($filename = readdir($fh)){

            // Skip over symbolic links and dot directories.
            if ( is_link($filename) || $filename == '.' || $filename == '..' ) continue;

            $filepath = "$path/$filename";
            if(is_dir($filepath)) {
                $this->process_files_recursively($filepath, $callback);
            } else if ('xml' === pathinfo($filename, PATHINFO_EXTENSION)) {
                call_user_func($callback, $filepath);
            }
        }
    }

    public static function extract_legacy_filepaths($content) {

        preg_match_all('/\$@FILEPHP@\$(((\$@SLASH@\$)|[\w%\~\.\-])*)/',
                       $content,
                       $matches,
                       PREG_SET_ORDER);

        return array_map(function ($match) {
            $path = str_replace('$@SLASH@$', '/', $match[1]);
            return urldecode($path);
        }, $matches);
    }

    /**
     * Searches through a file for file references that look like references
     * to a legacy course file. If it finds one, it adds its id to the backup
     * table whose purpose is to keep track of items that need to be added
     * to the backup. This causes the legacy course file to be included in the
     * backup.
     */
    private function process_file($filepath) {
        $backupid = $this->get_backupid();
        $contextid = context_course::instance($this->get_courseid())->id;

        $content = file_get_contents($filepath);

        $relativepaths = self::extract_legacy_filepaths($content);
        foreach ($relativepaths as $relativepath) {

            $fs = get_file_storage();
            // This seems a bit fragile. See similar code in mod/resource/db/upgradelib.php,
            // mod/page/db/upgradelib.php, and file.php. See also get_pathname_hash in
            // lib/filestorage/file_storage.php. The zero in the following string
            // corresponds to the item id. Appears to be always zero for legacy
            // course files.
            $fullpath = "/$contextid/course/legacy/0$relativepath";
            $fullpathhash = sha1($fullpath);
            $file = $fs->get_file_by_hash($fullpathhash);

            if ($file) {

                $fileid = $file->get_id();

                debugging("Calling insert_backup_ids_record for backupid $backupid"
                           . " and file $fileid ($fullpath) found in $filepath",
                          DEBUG_DEVELOPER);

                backup_structure_dbops::insert_backup_ids_record($backupid, 'file', $fileid);

            } else {
                debugging("Legacy course file not found for backupid $backupid"
                           . " and filepath hash $fullpathhash ($fullpath).");
            }
        }
    }

}

