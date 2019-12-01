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
                    // Snippet from wordpress 3.1.1
                    $filter = '#\b(https?|ftp)://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#';

                    if (preg_match_all($filter, $key, $matches)):
                        if (count($matches[0]) > 0):
                            foreach ($matches[0] as $url):
                                $this->thelist[] = $url;
                            endforeach;
                        endif;
                    endif;

                    if (preg_match_all($filter, $value, $matches)):
                        if (count($matches[0]) > 0):
                            foreach ($matches[0] as $url):
                                $this->thelist[] = $url;
                            endforeach;
                        endif;
                    endif;

                endforeach;
            else:
                // Snippet from wordpress 3.1.1
                $filter = '#\b(https?|ftp)://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#';
                if (preg_match_all($filter, $what, $matches)):
                    if (count($matches[0]) > 0):
                        foreach ($matches[0] as $url):
                            $this->thelist[] = $url;
                        endforeach;
                    endif;
                endif;
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

            // Snippet from wordpress 3.1.1
            $filter = '#\b(https?|ftp)://[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/))#';
            if (preg_match_all($filter, $what, $matches)):
                if (count($matches[0]) > 0):
                    foreach ($matches[0] as $url):
                        $this->thelist[] = $url;
                    endforeach;
                endif;
            endif;
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


        $this->searchInWPOptions();
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

}
WP_CLI::add_command( 'urlscout', 'UrlScout' );

