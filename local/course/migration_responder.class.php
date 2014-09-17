<?php

class migration_responder {

    private $restore_controller_factory;
    private $course_xfer_client;

    public function __construct($course_xfer_server, $course_backup_wrapper) {
        $this->course_xfer_server = $course_xfer_server;
        $this->course_backup_wrapper = $course_backup_wrapper;
    }

    public function process_migration_requests() {
        $this->course_xfer_server->reset_cache();

        $pending = $this->course_xfer_server->get_pending_requests_by_client();
        foreach ($pending as $client => $client_requests) {
            foreach ($client_requests as $requestid => $filepath) {
                $this->process_migration_request($client, $requestid, $filepath);
            }
        }
    }

    public function process_migration_request($client, $requestid, $filepath) {
        $request = $this->course_xfer_server->get_client_request($client, $requestid);

        // Parse the courseid out of the URL. Also confirm that instance
        // indicated by the URL matches $client.
        $courseid = local_course_parse_courseurl_courseid(
                        $request['sourcecourseurl'],
                        local_course_this_instancename());

        $include_user_data = $request['userdata'];

        $backup_file_object = $this->course_backup_wrapper
                                   ->backup_course_to_file($courseid,
                                                           $include_user_data);
        $filename = $backup_file_object->get_filename();

        $filenamebase = pathinfo($filename, PATHINFO_FILENAME);
        $suffix       = pathinfo($filename, PATHINFO_EXTENSION);

        // Get the file system directory in which to place the file.  Also, put the
        // requestid into the filename just before the suffix.
        $response_path = $this->course_xfer_server->generate_response_path($client,
                                                                           $filenamebase,
                                                                           $requestid,
                                                                           $suffix);
        echo "response_path: $response_path\n";

        // Moving the file to the response path is a two step operation.  First, we move
        // it to a temporary file name on what is likely a different mount than the original.
        // Then we rename the file to the expected name.  This is necessary to avoid clients'
        // picking up a partial file due to slowness in moving files across mounts.
        $tmp_path = $response_path.'.tmp';
        $success = $backup_file_object->copy_content_to($tmp_path);
        if (!$success) {
            throw new Exception("Unable to copy backup to the file system at $tmp_path");
        }
        $success = rename($tmp_path, $response_path);
        if (!$success) {
            throw new Exception("Failed to rename $tmp_path to $response_path");
        }
        $backup_file_object->delete();
    }

    #public function backup_course($courseid, $backupfilepath) {
    #}

    public function delete_unmatched_responses() {
        $this->course_xfer_server->reset_cache();
        $this->course_xfer_server->delete_unmatched_responses();
    }
}


