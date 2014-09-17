<?php

class course_xfer_exception extends Exception {}

/**
 * Calling code is responsible for calling reset_cache before beginning high level
 * operations.  This is more than the caller should be responsible for, so this
 * could use some reconsideration.
 */
class course_xfer_client {

    /**
     * The directory on the file system containing all instance (client
     * and server) directories.
     */
    private $base_dir;

    /**
     * Map of URL host to server.  Can be different
     * if migration server is involved.
     */
    private $server_map;

    /**
     * The client name used primarily for generating file paths.
     * Should consist of the domain and any path component of the
     * url for the instance separated by an underscore.
     * For example, the instance with a course at
     * https://moodledev.oit.umn.edu/2.0-dev/index.php
     * would have a client_dirname of "moodledev.oit.umn.edu_2.0-dev".
     */
    private $client_dirname;

    /**
     * Request ids are the keys; server names are the values.
     * The server name is that of the server to which the request
     * was sent (by placing in the appropriate directory).
     */
    private $server_by_request;

    /**
     * Map of request id to response filepath.
     */
    private $responses;


    /**
     * $base_dir/[client] is a directory owned by client and contains
     * a requests and responses
     */
    public function __construct($base_dir, $client_dirname, $server_map) {
        $this->base_dir = $base_dir;
        $this->server_map = $server_map;
        $this->client_dirname = $client_dirname;
    }

    public function get_sent_request_list() {
        isset($this->server_by_request) or $this->load_sent_request_list();
        return $this->server_by_request;
    }

    /**
     * Returns an array of response file paths by request id.
     */
    public function get_current_response_list() {
        isset($this->responses) or $this->load_current_response_list();
        return $this->responses;
    }

    /**
     * Returns array of unique servers that are listed server map.
     * Does not include source servers whose names point to a different
     * server in that map; includes value from the map in those cases.
     */
    private function get_servers() {
        $servers = array();
        foreach ($this->server_map as $source=>$server) {
            $servers[] = empty($server) ? $source : $server;
        }
        return array_unique($servers);
    }

    private function get_request_dir($servername) {
        return "$this->base_dir/$this->client_dirname/requests/$servername";
    }

    private function get_response_dir($servername) {
        return "$this->base_dir/$servername/responses/$this->client_dirname";
    }

    /**
     * Looks for current requests in $base_dir/[client]/requests/[server]/
     * Looks for responses in $base_dir/[server]/responses/[client]/
     * Requests is a map of filenames to servers.
     * Responses is a map of filenames to servers.
     */
    private function load_sent_request_list() {
        $this->server_by_request = array();

        $servers = $this->get_servers();

        foreach ($servers as $server) {

            $serverdir = $this->get_request_dir($server);

            if (is_dir($serverdir)) {
                $request_filenames = scandir($serverdir);
                foreach ($request_filenames as $filename) {
                    if (is_dir("$serverdir/$filename")) { continue; }
                    if (! 'req' == pathinfo($filename, PATHINFO_EXTENSION)) {
                        error_log('Found file with unexpected extension in load_sent_request_list: '
                                  . "$serverdir/$filename");
                        continue;
                    }

                    $requestid = local_course_requestid_from_filename($filename);

                    if (array_key_exists($requestid, $this->server_by_request)) {
                        error_log("Request file appears multiple times: $requestid");
                    }
                    $this->server_by_request[$requestid] = $server;
                }
            }
        }
    }

    /**
     * Writes requests to $base_dir/[client]/requests/[server]/
     * The $request is a map that must include requestid and the
     * URL of the course being requested (.../course/view.php?id=nnn).
     *
     * $request  map of request attributes
     */
    public function write_request($request) {
        $required = array('requestid', 'sourcecourseurl');
        $missing = array_diff($required, array_keys($request));
        if ($missing) {
            throw new course_xfer_exception('Missing from request: ' .
                                            implode($missing));
        }

        $requestid = $request['requestid'];
        $courseurl = $request['sourcecourseurl'];

        // Delete any existing request files for this requestid.
        $this->find_and_delete_existing_request_files($requestid);

        $server = $this->server_from_course_url($courseurl);

        $filedir = $this->get_request_dir($server);
        $filepath = $this->get_request_filepath($requestid, $server);

        if (file_exists($filepath)) {
            throw new course_xfer_exception("Request file already exists: $filepath");
        }

        is_dir($filedir) or mkdir($filedir, 0755, true);

        $contents = json_encode($request);
        check_json_status();
        file_put_contents($filepath, $contents);

        return $filepath;
    }

    /**
     * Creates source name from course url and then checks for mapping to server.
     * "https://mymoodle.umn.edu/1.9-scratch/course/view.php?id=1" would become
     * a source name of "mymoodle.umn.edu_1.9-scratch".  The instances $server_map
     * might map this to a migration server for upgrade.
     */
    public function server_from_course_url($course_url) {

        $source = local_course_parse_courseurl_instancename($course_url);

        if (array_key_exists($source, $this->server_map)) {
            if (empty($this->server_map[$source])) {
                return $source;
            } else {
                return $this->server_map[$source];
            }
        } else {
            throw new course_xfer_exception("No known server for $course_url");
        }
    }

    /**
     * Loads array of response file paths by request id.
     * Server and client must agree on suffix convention for
     * error conditions, which is not covered in these classes.
     */
    private function load_current_response_list() {
        $this->responses = array();

        // Get the list of servers for which we have requests outstanding.
        $servers = array_unique(array_values($this->get_sent_request_list()));

        foreach ($servers as $server) {
            $filedir = $this->get_response_dir($server);
            if (!is_dir($filedir)) { continue; }

            $response_filenames = scandir($filedir);
            foreach ($response_filenames as $filename) {
                if (is_dir("$filedir/$filename")) { continue; }

                // Parse the request id out of the response file name.
                $requestid = local_course_requestid_from_filename($filename);

                // If we are expecting a response from this server
                // for this request, add the request to the response array.
                if (array_key_exists($requestid, $this->server_by_request)
                    and $server === $this->server_by_request[$requestid])
                {
                    $this->responses[$requestid] = "$filedir/$filename";
                }
            }
        }
    }

    /**
     * Will clean up multiple even though should not occur.
     * Resets the cache if any are found and deleted.
     */
    private function find_and_delete_existing_request_files($requestid) {
        $sent_requests = $this->get_sent_request_list();
        while (array_key_exists($requestid, $sent_requests)) {
            $this->delete_request($requestid);
            $this->reset_cache();
            $sent_requests = $this->get_sent_request_list();
            #break;  # TODO: Remove this line to iterate.
        }
    }

    private function get_request_filepath($requestid, $server) {
        $filedir = $this->get_request_dir($server);
        $filepath = "$filedir/$requestid.req";
        return $filepath;
    }

    /**
     * Returns the seconds since mtime for the request file.
     */
    public function get_request_age($requestid) {
        $requests = $this->get_sent_request_list();
        if (!array_key_exists($requestid, $requests)) {
            throw new Exception("In get_request_age, no file for request $requestid.");
        }

        $server = $requests[$requestid];
        $filepath = $this->get_request_filepath($requestid, $server);
        $age = time() - filemtime($filepath);
        return $age;
    }

    /**
     * This is to support deleting the request after processing
     * the response.  Also used for some cleanup.  Does not reset
     * cache.
     */
    public function delete_request($requestid) {
        $requests = $this->get_sent_request_list();
        if (!array_key_exists($requestid, $requests)) {
            error_log("In delete_request, no file for request $requestid.");
            return;
        }

        $server = $requests[$requestid];
        $filepath = $this->get_request_filepath($requestid, $server);

        if (!file_exists($filepath)) {
            error_log("In delete_request, $filepath does not exist.");
            return;
        }

        $success = unlink($filepath);
        if (!$success) {
            // Before throwing an exception let's double check that the file still
            // exists in case another process deleted the file in a race condition.
            if (file_exists($filepath)) {
                throw new Exception("In delete_request, unlink of $filepath failed.");
            }
        }
    }

    /**
     * Resets the request and response member variables so that subsequent calls
     * to the accessor methods will reflect the most current state of the
     * file system. Other possible alternatives include eliminating the cache,
     * maintaining the cache internally, or putting high-level operations (with internal
     * calls to reset_cache) here with callbacks as to caller, as necessary.
     */
    public function reset_cache() {

        unset($this->server_by_request);
        unset($this->responses);
    }
}

/**
 * Calling code is responsible for calling reset_cache before beginning high level
 * operations.  This is more than the caller should be responsible for, so this
 * could use some reconsideration.
 */
class course_xfer_server {

    /**
     * The directory on the file system containing all instance (client
     * and server) directories.
     */
    private $base_dir;

    /**
     * The server name used primarily for generating file paths.
     * Should consist of the domain and any path component of the
     * url for the instance separated by an underscore.
     * For example, the instance with a course at
     * https://testing.moodle.umn.edu/1.9.8/course/view.php?id=333
     * would have a server_dirname of "testing.moodle.umn.edu_1.9.8".
     */
    private $server_dirname;

    /**
     * List of accepted client names.
     */
    private $clients;

    /**
     * Maps client to a map of requestid to request file path.
     * Access only through get_requests_by_client.
     */
    private $requests_by_client;

    /**
     * Maps client to a map of requestid to response file path.
     * Access only through get_responses_by_client.
     */
    private $responses_by_client;

    public function __construct($base_dir, $server_dirname, $clients) {
        $this->base_dir = $base_dir;
        $this->server_dirname = $server_dirname;
        $this->clients = $clients;
    }

    /**
     * Looks for current requests in $base_dir/[client]/requests/[server]/
     * Looks for responses in $base_dir/[server]/responses/[client]/
     */

    private function get_request_dir($clientname) {
        return "$this->base_dir/$clientname/requests/$this->server_dirname";
    }

    private function get_response_dir($clientname) {
        return "$this->base_dir/$this->server_dirname/responses/$clientname";
    }

    /**
     * Returns a map of client names to map of request id
     * to request file paths.
     */
    public function get_requests_by_client() {
        isset($this->requests_by_client) or $this->load_request_map();
        return $this->requests_by_client;
    }

    /**
     * Returns a map of client names to map of request id
     * to response file paths.
     */
    public function get_responses_by_client() {
        isset($this->responses_by_client) or $this->load_responses();
        return $this->responses_by_client;
    }

    public function get_client_request($client, $requestid) {
        $requests = $this->get_requests_by_client();
        $request_filepath = $requests[$client][$requestid];
        $request_string = file_get_contents($request_filepath);
        $request = json_decode($request_string, true);
        check_json_status();
        return $request;
    }

    /**
     * Loads the requests_by_client member data.
     */
    private function load_request_map() {

        $this->requests_by_client = array();

        foreach ($this->clients as $client) {
            $filedir = $this->get_request_dir($client);

            if (!is_dir($filedir)) { continue; }

            $request_filenames = scandir($filedir);
            $client_requests = array();
            foreach ($request_filenames as $filename) {
                if (is_dir("$filedir/$filename")) { continue; }

                $requestid = local_course_requestid_from_filename($filename);

                $client_requests[$requestid] = "$filedir/$filename";
            }

            if (!empty($client_requests)) {
                $this->requests_by_client[$client] = $client_requests;
            }
        }
    }

    /**
     * Loads the responses_by_client member data.
     */
    private function load_responses() {

        $this->responses_by_client = array();

        foreach ($this->clients as $client) {
            $filedir = $this->get_response_dir($client);

            if (!is_dir($filedir)) { continue; }

            $response_filenames = scandir($filedir);
            $responses = array();
            foreach ($response_filenames as $filename) {
                if (is_dir("$filedir/$filename")) { continue; }

                $requestid = local_course_requestid_from_filename($filename);

                $responses[$requestid] = "$filedir/$filename";
            }

            if (!empty($responses)) {
                $this->responses_by_client[$client] = $responses;
            }
        }
    }

    /**
     * Includes only requests that do not have matching responses.
     */
    public function get_pending_requests_by_client() {

        $requests = $this->get_requests_by_client();
        $responses = $this->get_responses_by_client();

        return $this->diff_multiarray_by_client($requests, $responses);
    }

    /**
     * Includes only responses that do not have matching requests.
     * These would typically indicate that the requester processed
     * the response and deleted the request.
     */
    public function get_unmatched_responses_by_client() {

        $requests = $this->get_requests_by_client();
        $responses = $this->get_responses_by_client();

        return $this->diff_multiarray_by_client($responses, $requests);
    }

    /**
     * Helper for finding pending requests and unmatched responses.
     */
    private function diff_multiarray_by_client($a1, $a2) {

        $result = array();

        foreach ($a1 as $client => $val1) {
            if (!is_array($val1)) { continue; }

            if (array_key_exists($client, $a2) and is_array($a2[$client])) {
                $result[$client] = array_diff_key($val1, $a2[$client]);

                // Delete client with empty list.
                # TODO: Test this.
                if (empty($result[$client])) {
                    unset($result[$client]);
                }
            } else {
                $result[$client] = $val1;
            }
        }
        return $result;
    }

    /**
     * Deletes responses for which the client (presumably) has deleted
     * the request after processing the response.
     */
    public function delete_unmatched_responses() {
        $unmatched = $this->get_unmatched_responses_by_client();

        foreach ($unmatched as $client => $responses_by_requestid) {
            foreach ($responses_by_requestid as $requestid => $response_path) {
                $this->delete_response($client, $requestid);
            }
        }
    }

    /**
     * Might not need this if we know the filename and path to write to from
     * the request and the application logic just writes there.
     */
    //public function write_response() { }
    //public function write_error_response($client, $requestid, $content) { }

    /**
     * Returns the path to the response file, which does not need to
     * exist yet.
     *
     * Creates the directory if it does not already exist.  Does not write
     * the file, however; that is left to the caller.
     *
     * Gets the correct directory in which to place the file and adjusts the filename
     * to include the requestid just before the suffix.
     */
    public function generate_response_path($client, $filenamebase, $requestid, $suffix='mbz') {
        if (!in_array($client, $this->clients)) {
            throw new course_xfer_exception("Invalid client: $client");
        }

        $pending_requests = $this->get_pending_requests_by_client();
        if (!array_key_exists($client, $pending_requests)
            or !is_array($pending_requests[$client])
            or !array_key_exists($requestid, $pending_requests[$client]))
        {
            throw new course_xfer_exception("Client $client is not expecting response for $requestid");
        }

        $filedir = $this->get_response_dir($client);

        is_dir($filedir) or mkdir($filedir, 0755, true);

        return "$filedir/$filenamebase.$requestid.$suffix";
    }

    /**
     * This is to support deleting the response after finding
     * request is deleted. This does not reset the cache.
     */
    public function delete_response($client, $requestid) {

        $responses_by_client = $this->get_responses_by_client();

        if (!array_key_exists($client, $responses_by_client)
           or !array_key_exists($requestid, $responses_by_client[$client]))
        {
            throw new course_xfer_exception("No response for $requestid from $client.");
        }

        $filepath = $responses_by_client[$client][$requestid];

        if (!file_exists($filepath)) {
            throw new course_xfer_exception("Response file not found: $filepath");
        }

        #debugging("Deleting $filepath");
        unlink($filepath);
    }

    /**
     * Resets the request and response member variables so that subsequent calls
     * to the accessor methods will reflect the most current state of the
     * file system.
     */
    public function reset_cache() {

        unset($this->requests_by_client);
        unset($this->responses_by_client);
    }

}
