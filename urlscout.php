<?php
/*
Description: Searches urls inside Wordpress Tables
Version: 1.0.0
Author: Panagiotis Chalatsakos <chibioni@gmail.com>
Author URL: https://ditikos.github.io
*/

use WP_CLI\Utils;

class UrlScout extends WP_CLI_Command {
    protected $thelist;

    /**
     * Searches for urls in wordpress wp_posts and wp_postmeta
     * 
     * @since 1.0.0
     * @author Panagiotis Chalatsakos <chibioni@gmail.com>
     */
    public function __invoke( $args )
    {
        $this->searchInWordpress();
        $this->displayResults();
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

            // Snippet from wordpress 3.1.1
            $filter = '#\b(https?|ftp)://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#';
            if (preg_match_all($filter, $what, $matches)) {
                if (count($matches[0]) > 0):
                    foreach ($matches[0] as $url):
                        $this->thelist[] = $url;
                    endforeach;
                endif;
            }
        endforeach;

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
    }
}
WP_CLI::add_command( 'urlscout', 'UrlScout' );

