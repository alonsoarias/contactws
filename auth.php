<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/authlib.php');

/**
 * Contact Web Services authentication plugin.
 */
class auth_plugin_contactws extends \auth_contactws\auth {
    /**
     * Constructor
     */
    public function __construct() {
        debugging('Initializing auth_plugin_contactws', DEBUG_DEVELOPER);
        parent::__construct();
    }
}