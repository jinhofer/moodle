<?PHP

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * library/utility for the local Library Reserves plugin
 *
 * @package   local
 * @subpackage library_reserves
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright University of Minnesota 2013
 */

class library_reserves_service {

    /**
     *
     */
    public function __construct() {

    }


    /**
     * convert from term-institution-classnumber triplet into
     * the format that the Library Reserve system consumes
     * (concatenating the components of the triplet)
     *
     *  @param $term string (1135, 1129, ...)
     *  @param $institution string (UMNCR, UMNTC, UMNDL, UMNMO)
     *  @param $class_nbr string (80365, 10947, ...)
     *
     *  @return string
     */
    public static function encode_class_triplet($term, $institution, $class_nbr) {
        return $term . $institution . $class_nbr ;
    }


    /**
     * parse a concatenated triplet into its component
     *
     * @param string $encoded_triplet
     * @return array(
     *     'term'         => <string>
     *     'institution'  => <string>
     *     'class_nbr'    => <string>
     */
    public static function decode_class_triplet($encoded_triplet) {
        if (strlen($encoded_triplet) < 13) {
            return false;
        }

        $term = substr($encoded_triplet, 0, 4);
        $institution = substr($encoded_triplet, 4, 5);
        $class_nbr = substr($encoded_triplet, 9);

        return array('term'        => $term,
                     'institution' => $institution,
                     'class_nbr'   => $class_nbr);
    }

}

/**
 * utility to sync from library system
 *
 * @package   local
 * @subpackage library_reserves
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright University of Minnesota 2011
 */

class library_reserves_syncer {

    private $ch;

    public function __construct($localcurl = null) {
        if (is_null($localcurl)) {
            $this->ch = new library_reserves_curl();
        }
        else {
            $this->ch = $localcurl;
        }
    }

    public function __destruct() {
        $this->ch->close();
    }
    /**
     * update a single class, doesn't touch/clean the other classes
     * @param object $ppsft_class the ppsft_classes record
     */
    public function update_class($ppsft_class) {
        global $DB;

        $triplet = library_reserves_service::encode_class_triplet($ppsft_class->term,
                                                                  $ppsft_class->institution,
                                                                  $ppsft_class->class_nbr);
        // get resources from eReserve
        $params = array('courses' => $triplet, 'request' => 'resources');

        try {
            $data = $this->retrieve_data($params);
        }
        catch(Exception $e) {
            $data = '';
        }

        $resource_lines = preg_split('/\n/', $data, null, PREG_SPLIT_NO_EMPTY);

        // if no resources, mark the class as having no reserves
        if (count($resource_lines) == 0) {
            $absence_record = $DB->get_record('local_library_reserve', array('ppsft_class_id' => $ppsft_class->id));

            if ($absence_record) { // update timestamp
                $absence_record->timemodified = time();
                $DB->update_record('local_library_reserve', $absence_record);
            }
            else {    // insert absence record
                $absence_record = new stdClass();
                $absence_record->ppsft_class_id = $ppsft_class->id;
                $absence_record->resource_id    = 0;
                $absence_record->sort_number    = null;
                $absence_record->timemodified   = time();
                $DB->insert_record('local_library_reserve', $absence_record);
            }
        }
        else { // has resources
            // parse the resources
            $resources = array();
            $resource_ids = array();
            foreach ($resource_lines as $line) {
                $parsed_resource = str_getcsv($line);
                $resources[$parsed_resource[0]] = $parsed_resource; // index by resource_id
                $resource_ids[] = $parsed_resource[0];
            }

            // update existing resources
            $now = time();

            $resource_records = $DB->get_records_list('local_library_resource', 'resource_id', $resource_ids);
            //STRY0010333 20140627 mart0969 - Add note to field list
            $field_map = array('resource_id', 'title', 'resource_type', 'url', 'note');
            $existing_resource_ids = array();

            foreach ($resource_records as $record) {
                $existing_resource_ids[$record->resource_id] = true;

                // update local if the resource still exists on remote sides
                if (isset($resources[$record->resource_id])) {
                    // update the record even if there is no change (so that the timestamp get updated)
                    for ($i = 1; $i < count($field_map); $i++) {
                        $record->$field_map[$i] = $resources[$record->resource_id][$i];
                    }

                    $record->timemodified = $now;
                    $DB->update_record('local_library_resource', $record);
                }
            }

            // insert new resources
            foreach ($resources as $resource_id => $resource) {
                if (!isset($existing_resource_ids[$resource_id])) {
                    $r = new stdClass();
                    for ($i = 0; $i < count($field_map); $i++) {
                        $r->$field_map[$i] = $resource[$i];
                    }
                    $r->timemodified = $now;
                    $DB->insert_record('local_library_resource', $r);
                }
            }

            // ignore resource that doesn't exist on remote side, it might still be used by another class
            // the full sync will remove resources that have been deleted on remote side

            // get reserves from eReserve
            $params = array('courses' => $triplet, 'request' => 'reserves');
            $reserve_lines = preg_split('/\n/', $this->retrieve_data($params), null, PREG_SPLIT_NO_EMPTY);

            // parse the reserves
            $reserves = array();
            $recourse_ids = array();
            foreach ($reserve_lines as $line) {
                $parsed_reserve = str_getcsv($line);
                $reserves[$parsed_reserve[1]] = $parsed_reserve; // index by recourse_id
                $resource_ids[] = $parsed_reserve[1];
            }

            // update existing reserves (no need to match the triplet to class_id)
            $reserve_records = $DB->get_records('local_library_reserve', array('ppsft_class_id' => $ppsft_class->id));
            $existing_resource_ids = array();

            foreach ($reserve_records as $record) {
                $existing_resource_ids[$record->resource_id] = true;

                // update local if the reserve still exists on the remote side
                if (isset($reserves[$record->resource_id])) {
                    $record->sort_number = $reserves[$record->resource_id][2];
                    $record->timemodified = $now;
                    $DB->update_record('local_library_reserve', $record);
                }
            }

            // insert new reserves
            foreach ($reserves as $resource_id => $reserve) {
                if (!isset($existing_resource_ids[$resource_id])) {
                    $r = new stdClass();
                    $r->ppsft_class_id = $ppsft_class->id;
                    $r->resource_id    = $resource_id;
                    $r->sort_number    = $reserve[2];
                    $r->timemodified   = $now;
                    $DB->insert_record('local_library_reserve', $r);
                }
            }

            // delete reserve record that has been deleted on the remote side
            $where_sql = 'ppsft_class_id = :class_id AND timemodified < :now';
            $DB->delete_records_select('local_library_reserve', $where_sql, array('class_id' => $ppsft_class->id,
                                                                                  'now'      => $now));
        }

        return true;
    }



    /**
     * sync reserves for all ppsft classes
     *
     * This function perform syncing on all classes, not incremental.
     * It also delete staled records.
     */
    public function sync() {
        global $DB;

        echo "\n\n[", date('m-d-Y H:i:s'), "]";

        $service = new library_reserves_service();

        // retrieve the list of active courses from library system
        $params = array('courses' => '',
                        'request' => 'active');
        $remote_courses = preg_split('/[\s,;\n]+/', $this->retrieve_data($params), null, PREG_SPLIT_NO_EMPTY);

        // intersect with out active classes
        $ppsft_rs = $DB->get_recordset('ppsft_classes', null, '', 'term,institution,class_nbr');

        $ppsft_classes = array();
        foreach ($ppsft_rs as $rs) {
            $ppsft_classes[] = $service::encode_class_triplet($rs->term, $rs->institution, $rs->class_nbr);
        }
        $ppsft_rs->close();

        $candidates = array_intersect($remote_courses, $ppsft_classes);

        if (count($candidates) == 0) {
            echo 'No candidate classes.';
            return true;
        }

        // convert to CSV list
        $candidates = implode(',', $candidates);

        // retrieve and update the library resources
        $params = array('courses' => $candidates,
                        'request' => 'resources');
        $resource_filepath = $this->retrieve_data($params, 'resources.csv');
        $this->update_resources($resource_filepath);

        // retrieve and update the library reserves
        $params = array('courses' => $candidates,
                        'request' => 'reserves');
        $reserve_filepath = $this->retrieve_data($params, 'reserves.csv');
        $this->update_reserves($reserve_filepath);
    }



    /** 
     * update the table local_library_resource with data from file
     * @param string $filepath
     * @return bool
     */
    protected function update_resources($filepath) {
        global $DB;
        $dbman = $DB->get_manager();

        echo "\nStart updating resources";

        // create temporary table
        $tmp_table_name = 'tmp_library_resource';

        $table = new xmldb_table($tmp_table_name);
        $table->add_field('resource_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('title', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('resource_type', XMLDB_TYPE_CHAR, '10', null, null, null, 'item');
        $table->add_field('url', XMLDB_TYPE_TEXT, null, null, null, null, null);
        //STRY0010333 20140627 mart0969 - Add note to field list
        $table->add_field('note', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding indexes to table local_library_resource
        $table->add_index('resource_id', XMLDB_INDEX_UNIQUE, array('resource_id'));

        // create the temp table
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $dbman->create_temp_table($table);

        // import the file content into temp table
        //STRY0010333 20140627 mart0969 - Add note to field list
        $this->importCSV($filepath, $tmp_table_name, array('resource_id','title','resource_type','url','note'));

        // get some stats
        echo "\nNumber of resource records: ", $DB->count_records($tmp_table_name);

        $now = time();

        // update existing records
        $query = 'UPDATE {local_library_resource} local,
                         {'.$tmp_table_name.'} tmp
                  SET  local.title = tmp.title,
                       local.resource_type = tmp.resource_type,
                       local.url = tmp.url,
                       local.note = tmp.note,
                       local.timemodified = :now
                  WHERE local.resource_id = tmp.resource_id';

        $DB->execute($query, array('now' => $now));

        // insert new records
        $query = 'INSERT INTO {local_library_resource}
                      (id, resource_id, title, resource_type, url, note, timemodified)
                  SELECT 0,
                         resource_id,
                         title,
                         resource_type,
                         url,
                         note,
                         :now
                  FROM {'.$tmp_table_name.'} tmp
                  WHERE tmp.resource_id NOT IN
                      (SELECT DISTINCT resource_id
                       FROM {local_library_resource})';

        $DB->execute($query, array('now' => $now));


        // delete staled records
        $query = 'DELETE FROM {local_library_resource}
                  WHERE timemodified < :now';
        $DB->execute($query, array('now' => $now));

        // done, drop the temp table
        $dbman->drop_table($table);

        echo "\nDone updating resources.\n";
    }



    /**
     * update the table local_library_reserve with data from file
     * @param string $filepath
     * @return bool
     */
    protected function update_reserves($filepath) {
        global $DB;
        $dbman = $DB->get_manager();

        echo "\nStart updating reserves";

        // create temporary table
        $tmp_table_name = 'tmp_library_reserve';

        $table = new xmldb_table($tmp_table_name);
        $table->add_field('ppsft_class_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('ppsft_triplet', XMLDB_TYPE_CHAR, '15', null, XMLDB_NOTNULL, null, null);
        $table->add_field('resource_id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('sort_number', XMLDB_TYPE_INTEGER, '3', null, null, null, null);

        // Adding keys to temp table (to de-dupe the source)
        $table->add_key('ppsft_triplet-resource_id', XMLDB_KEY_UNIQUE, array('ppsft_triplet', 'resource_id'));

        // Adding indexes to temp table
        $table->add_index('ppsft_class_id', XMLDB_INDEX_NOTUNIQUE, array('ppsft_class_id'));

        // create the temp table
        if ($dbman->table_exists($table)) {
            $dbman->drop_table($table);
        }

        $dbman->create_temp_table($table);

        // import the file content into temp table
        $this->importCSV($filepath, $tmp_table_name, array('ppsft_triplet','resource_id','sort_number'));

        // get some stats
        echo "\nNumber of reserve records imported: ", $DB->count_records($tmp_table_name);

        // create a mapping from ppsft_triplet to ppsft_class_id
        $map_table_name = 'tmp_ppsft_id_map';

        $map_table = new xmldb_table($map_table_name);
        $map_table->add_field('class_id', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $map_table->add_field('triplet', XMLDB_TYPE_CHAR, '15', null, XMLDB_NOTNULL, null, null);

        // Adding indexes to map table
        $map_table->add_index('triplet', XMLDB_INDEX_NOTUNIQUE, array('class_id'));

        // create the temp table
        if ($dbman->table_exists($map_table)) {
            $dbman->drop_table($map_table);
        }

        $dbman->create_temp_table($map_table);

        $map_rs = $DB->get_recordset('ppsft_classes', null, null, 'id, term, institution, class_nbr');

        // insert records into mapping table all at once (assuming the number of ppsft_classes is not large)
        $sql = '';
        $values = array();

        foreach ($map_rs as $rs) {
            $values[] = library_reserves_service::encode_class_triplet($rs->term, $rs->institution, $rs->class_nbr);
            $values[] = $rs->id;

            $sql .= '(?, ?),';
        }
        $map_rs->close();

        $query = 'INSERT IGNORE INTO {'.$map_table_name.'} (triplet, class_id)
                  VALUES ' . trim($sql, ',');
        $DB->execute($query, $values);


        // fill the temp table with class ID
        $query = 'UPDATE {'.$tmp_table_name.'} tmp,
                         {'.$map_table_name.'} map
                  SET tmp.ppsft_class_id = map.class_id
                  WHERE tmp.ppsft_triplet = map.triplet';
        $DB->execute($query);

        // done with mapping table
        $dbman->drop_table($map_table);

        $now = time();

        // update existing records
        $query = 'UPDATE {local_library_reserve} local,
                         {'.$tmp_table_name.'} tmp
                  SET  local.sort_number = tmp.sort_number,
                       local.timemodified = :now
                  WHERE local.ppsft_class_id = tmp.ppsft_class_id AND
                        local.resource_id = tmp.resource_id';
        $DB->execute($query, array('now' => $now));

        // insert new records
        $query = 'INSERT INTO {local_library_reserve}
                      (id, ppsft_class_id, resource_id, sort_number, timemodified)
                  SELECT 0,
                         ppsft_class_id,
                         resource_id,
                         sort_number,
                         :now
                  FROM {'.$tmp_table_name.'} tmp
                  WHERE tmp.ppsft_class_id IS NOT NULL AND
                        (tmp.ppsft_class_id, tmp.resource_id) NOT IN
                            (SELECT DISTINCT ppsft_class_id, resource_id
                             FROM {local_library_reserve})';
        $DB->execute($query, array('now' => $now));

        // update existing absence/null records
        $query = 'UPDATE {local_library_reserve} reserve,
                         {ppsft_classes}
                  SET reserve.timemodified = :now
                  WHERE reserve.resource_id = 0 AND
                        reserve.ppsft_class_id NOT IN
                            (SELECT DISTINCT ppsft_class_id
                             FROM {'.$tmp_table_name.'})';
        $DB->execute($query, array('now' => $now));

        // insert new absence/null records
        $query = 'INSERT INTO {local_library_reserve}
                      (id, ppsft_class_id, resource_id, sort_number, timemodified)
                  SELECT 0,
                         id,
                         0,
                         NULL,
                         :now
                  FROM {ppsft_classes} ppsft_classes
                  WHERE id NOT IN
                      (SELECT DISTINCT ppsft_class_id
                       FROM {local_library_reserve})';
        $DB->execute($query, array('now' => $now));

        // delete staled records
        $query = 'DELETE FROM {local_library_reserve}
                  WHERE timemodified < :now';
        $DB->execute($query, array('now' => $now));

        // done, drop the temp table
        $dbman->drop_table($table);

        echo "\nDone updating reserves.\n";
    }




    /**
     * Load data from a CSV file into a (temp) table
     * @param string $filepath
     * @param string $table
     * @param array $columns map the position of CSV fields to table columns
     * @param int $chunk_size how many lines should be inserted at once (as a chunk)
     * @throws Exception
     */
    protected function importCSV($filepath, $table, $columns, $chunk_size = 1000) {
        global $DB;

        // import CSV into temp table
        $handle = fopen($filepath, "r");

        if ($handle == false) {
            throw new Exception('importCSV: cannot read data file '.$tmp_filepath);
        }

        $row = 0;
        $values = array();
        $sql = '';

        while (($line = fgetcsv($handle)) !== false) {
            $row++;
            $placeholder_group = array();

            foreach ($line as $field) {
                $placeholder_group[] = '?';
                $values[] = $field;
            }

            $sql .= '('.implode(',', $placeholder_group).'),';

            // flush the chunk to DB
            if ($row == $chunk_size) {
                $row = 0;

                $query = 'INSERT IGNORE INTO {'.$table.'} ('.implode(',', $columns).') VALUES '.trim($sql, ',');
                $DB->execute($query, $values);

                $sql = '';
                $values = array();
            }
        }

        fclose($handle);

        // flush the remaining chunk
        if ($row > 0) {
                $query = 'INSERT IGNORE INTO {'.$table.'} ('.implode(',', $columns).') VALUES '.trim($sql, ',');
                $DB->execute($query, $values);
        }
    }


    /**
     * helper utility to post a request to the remote library system
     *
     * @param array $params key-value pairs to submit with the request
     * @param string $filename write the retrieved data to the file, or return the data if null
     */
    public function retrieve_data($params, $filename = null) {
        global $CFG;

        $api_url = get_config('local_library_reserves', 'api_url');
        $params['token'] = get_config('local_library_reserves', 'api_token');

        $this->ch->setOption(CURLOPT_URL, $api_url);
        $this->ch->setOption(CURLOPT_SSL_VERIFYPEER, false);
        $this->ch->setOption(CURLOPT_POST, 1);
        $this->ch->setOption(CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
        $this->ch->setOption(CURLOPT_VERBOSE, true);
        // curl_setopt($ch, CURLOPT_VERBOSE, true);

        if (is_null($filename)) {
            $this->ch->setOption(CURLOPT_RETURNTRANSFER, true);
        }
        else {
            $filepath = $CFG->tempdir.'/library_reserves/'.$filename;
            make_temp_directory('library_reserves');

            $fp = fopen($filepath, 'w+');

            $this->ch->setOption(CURLOPT_RETURNTRANSFER, false);
            $this->ch->setOption(CURLOPT_FILE, $fp);
        }

        $data = $this->ch->execute();
        $error = $this->ch->getError();
        $http_status = $this->ch->getInfo(CURLINFO_HTTP_CODE);

        if (isset($fp)) {
            fclose($fp);
        }

        if (!empty($error)) {
            throw new Exception('Library Reserves: CURL error: '.$error);
        }

        if ($http_status != 200) {
            throw new Exception('Library Reserves: unexpected HTTP_STATUS '.$http_status);
        }

        if (is_null($filename)) {
            return $data;
        }
        else {
            return $filepath;
        }
    }
}

interface HttpRequest
{
    public function setOption($name, $value);
    public function execute();
    public function getInfo($name);
    public function getError();
    public function close();
}

class library_reserves_curl implements HttpRequest{
    private $handle = null;

    public function __construct() {
        $this->handle = curl_init();
    }

    public function setOption($name, $value) {
        curl_setopt($this->handle, $name, $value);
    }

    public function execute() {
        return curl_exec($this->handle);
    }

    public function getInfo($name) {
        return curl_getinfo($this->handle, $name);
    }

    public function getError() {
        return curl_error($this->handle);
    }

    public function close() {
        curl_close($this->handle);
    }
}
