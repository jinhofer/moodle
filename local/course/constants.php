<?php

/**
 * Course request status indicating that the course
 * request has not yet been processed.
 */
define('CRS_REQ_STATUS_NEW', 0);

/**
 * Course request status indicating that the course
 * request is pending a course migration from another
 * Moodle instance.
 */
define('CRS_REQ_STATUS_MIGRATING', 1);

/**
 * Course request status indicating that the backup
 * from the source is ready for restoring into a
 * new course.
 */
define('CRS_REQ_STATUS_READY', 5);

/**
 * Course request status indicating an unrecoverable error
 * state. Rejecting the request is likely the only option.
 */
define('CRS_REQ_STATUS_ERROR', 7);

/**
 * Course request status indicating that the course
 * request is cancelled.
 */
define('CRS_REQ_STATUS_CANCELED', 8);

/**
 * Course request status indicating that the course
 * request has been successfully processed and the
 * courseid property holds the id of the new course.
 */
define('CRS_REQ_STATUS_COMPLETE', 9);
