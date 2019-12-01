<?php
/*
Description: Searches urls inside Wordpress Tables
Version: 1.0.0
Author: Panagiotis Chalatsakos
Author URL: https://ditikos.github.io
*/

use WP_CLI\Utils;

use function WP_CLI\Utils\make_progress_bar;

class UrlScout extends WP_CLI_Command {
    protected $thelist;

    /**
     * Searches for urls in wordpress wp_posts and wp_postmeta
     * 
     * @since 1.0.0
     * @author Panagiotis Chalatsakos
     */
    public function __invoke( $args )
    {
        global $wpdb;
        $this->thelist = array();
        //$this->searchInWordpress();
        //$this->displayResults();
        $searchTableSQL = 'SELECT table_schema db, table_name tb from information_schema.tables where table_schema="'.DB_NAME.'"';
        // " and lower(table_name) like "wp_woo%"'; 
        $results = $wpdb->get_results($searchTableSQL);
        $progress = make_progress_bar("Hello progress:", count($results));
        foreach ($results as $table):
            $progress->tick();
            $tableToSearch = $table->tb;
            WP_CLI::line("Searching in :".$tableToSearch);
            $sqlToSearch = "select * from ".$tableToSearch;
            $resultSearch = $wpdb->get_results($sqlToSearch);
            foreach ($resultSearch as $entry):
                foreach ($entry as $key=>$value):
                    $this->searchInArray($key);
                    $value = maybe_unserialize( $value );
                    if (is_array($value) || is_object($value)):
                        $arr = $this->squash($value);
                        foreach ($arr as $key => $value):
                            // wp_options might have a url as key
                            $this->searchInArray($key);
                            $this->searchInArray($value);
                        endforeach;
                    else:
                        $this->searchInArray($key);
                    endif;
                endforeach;
            endforeach;
        endforeach;

        $progress->finish();
        $this->searchInAttachments();
        $this->displayResults();
    }

    private function searchInWPOptions() {

        global $wpdb;

        // Extract urls from wp_options
        //
        $url_search = "SELECT option_value FROM wp_options";
        $entries = $wpdb->get_results($url_search);
        foreach ($entries as $entry):

            // Use wp's maybe_unserialize
            $what = maybe_unserialize($entry->option_value);
            if (is_array($what) || is_object($what)):

                // Flatten array
                $arr = $this->squash($what);

                foreach ($arr as $key => $value):
                    // wp_options might have a url as key
                    $this->searchInArray($key);
                    $this->searchInArray($value);
                endforeach;
            else:
                $this->searchInArray($what);
            endif;
        endforeach;
    }

    private function searchInWordpress() {
        global $wpdb;

        // Define one list (tbd: class variable)
        $this->thelist = array();

        //
        // Extract urls from wp_posts
        //
        $url_search = "SELECT post_content FROM wp_posts";
        $entries = $wpdb->get_results($url_search);
        foreach ($entries as $entry):
            $what = $entry->post_content;
            $this->searchInArray($what);
        endforeach;

        $this->searchInAttachments();
        $this->searchInWPOptions();
    }

    private function searchInAttachments() {
        global $wpdb;

        //
        // Extract attachments using wp_postmeta
        //
        $upload_info = wp_get_upload_dir();
        // Search only the original photo
        $img_search = "SELECT * FROM wp_postmeta WHERE meta_key = '_wp_attached_file'";
        // For all attachments -- TBA.
        //$img_search = "SELECT * FROM wp_postmeta WHERE meta_key IN ('_wp_attached_file', '_wp_attachment_backup_sizes',  '_wp_attachment_metadata',  '_thumbnail_id')";
        
        $entries = $wpdb->get_results($img_search);
        if (count($entries) > 0):
            foreach ($entries as $entry):
                $this->thelist[] = $upload_info['baseurl']."/".$entry->meta_value;
            endforeach;
        endif;
    }

    public function displayResults()
    {
        if (count($this->thelist) > 0):
            foreach($this->thelist as $entry):
                WP_CLI::line($entry);
            endforeach;
        else:
            WP_CLI::warning("No urls found.");
        endif;

        WP_CLI::success("Found: ".count($this->thelist));
    }

    /**
     * Flatten object / array
     * @author Wogan May
     * @gist http://gist.github.com/woganmay/9a98dda0524bca664c
     */
    private function squash($array, $prefix = '') {

        $flat = array();
        $sep = ".";

        if (!is_array($array))  $array = (array)$array;

        foreach ($array as $key => $value):
            $_key = ltrim($prefix.$sep.$key, ".");
            if (is_array($value) || is_object($value)):
                $flat = array_merge($flat, $this->squash($value, $_key));
            else:
                $flat[$_key] = $value;
            endif;
        endforeach;

        return $flat;
    }

    /**
     * Search for specific pattern
     */
    private function searchInArray($what) {
        $filter = "/(?i)\b((?:https?:\/\/|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}\/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:'\".,<>?«»“”‘’]))/";
        if (preg_match_all($filter, $what, $matches)):
            if (count($matches[0]) > 0):
                foreach ($matches[0] as $url):
                    $this->thelist[] = $url;
                endforeach;
            endif;
        endif;
    }

}
WP_CLI::add_command( 'urlscout', 'UrlScout' );

