<?php
/**
 * Helper utilities for Level Up Client Dashboard.
 *
 * @package Level_Up_Client_Dashboard
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class LUC_D_Helpers {
    /**
     * Get list of U.S. states and territories.
     *
     * @return array Map of state abbreviations to names.
     */
    public static function get_us_states() {
        return array(
            'AL' => 'Alabama',
            'AK' => 'Alaska',
            'AZ' => 'Arizona',
            'AR' => 'Arkansas',
            'CA' => 'California',
            'CO' => 'Colorado',
            'CT' => 'Connecticut',
            'DE' => 'Delaware',
            'DC' => 'District of Columbia',
            'FL' => 'Florida',
            'GA' => 'Georgia',
            'HI' => 'Hawaii',
            'ID' => 'Idaho',
            'IL' => 'Illinois',
            'IN' => 'Indiana',
            'IA' => 'Iowa',
            'KS' => 'Kansas',
            'KY' => 'Kentucky',
            'LA' => 'Louisiana',
            'ME' => 'Maine',
            'MD' => 'Maryland',
            'MA' => 'Massachusetts',
            'MI' => 'Michigan',
            'MN' => 'Minnesota',
            'MS' => 'Mississippi',
            'MO' => 'Missouri',
            'MT' => 'Montana',
            'NE' => 'Nebraska',
            'NV' => 'Nevada',
            'NH' => 'New Hampshire',
            'NJ' => 'New Jersey',
            'NM' => 'New Mexico',
            'NY' => 'New York',
            'NC' => 'North Carolina',
            'ND' => 'North Dakota',
            'OH' => 'Ohio',
            'OK' => 'Oklahoma',
            'OR' => 'Oregon',
            'PA' => 'Pennsylvania',
            'RI' => 'Rhode Island',
            'SC' => 'South Carolina',
            'SD' => 'South Dakota',
            'TN' => 'Tennessee',
            'TX' => 'Texas',
            'UT' => 'Utah',
            'VT' => 'Vermont',
            'VA' => 'Virginia',
            'WA' => 'Washington',
            'WV' => 'West Virginia',
            'WI' => 'Wisconsin',
            'WY' => 'Wyoming',
            'AS' => 'American Samoa',
            'GU' => 'Guam',
            'MP' => 'Northern Mariana Islands',
            'PR' => 'Puerto Rico',
            'VI' => 'U.S. Virgin Islands',
            'UM' => 'U.S. Minor Outlying Islands',
        );
    }

    /**
     * Get definitions for all client fields.
     *
     * @return array
     */
    public static function get_client_fields() {
        return array(
            'first_name'       => array( 'label' => __( 'First Name', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'last_name'        => array( 'label' => __( 'Last Name', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'email'            => array( 'label' => __( 'Email', 'level-up-client-dashboard' ), 'type' => 'email' ),
            'mailing_address1' => array( 'label' => __( 'Mailing Address 1', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_address2' => array( 'label' => __( 'Mailing Address 2', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_city'     => array( 'label' => __( 'Mailing City', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_state'    => array( 'label' => __( 'Mailing State', 'level-up-client-dashboard' ), 'type' => 'select', 'options' => self::get_us_states() ),
            'mailing_postcode' => array( 'label' => __( 'Mailing Zip', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_country'  => array( 'label' => __( 'Mailing Country', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_name'     => array( 'label' => __( 'Company Name', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_website'  => array( 'label' => __( 'Company Website', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'company_address1' => array( 'label' => __( 'Company Address 1', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_address2' => array( 'label' => __( 'Company Address 2', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_city'     => array( 'label' => __( 'Company City', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_state'    => array( 'label' => __( 'Company State', 'level-up-client-dashboard' ), 'type' => 'select', 'options' => self::get_us_states() ),
            'company_postcode' => array( 'label' => __( 'Company Zip', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_country'  => array( 'label' => __( 'Company Country', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'social_facebook'  => array( 'label' => __( 'Facebook', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_twitter'   => array( 'label' => __( 'Twitter', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_instagram' => array( 'label' => __( 'Instagram', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_linkedin'  => array( 'label' => __( 'LinkedIn', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_yelp'      => array( 'label' => __( 'Yelp', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_bbb'       => array( 'label' => __( 'BBB', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'client_since'     => array( 'label' => __( 'Client Since', 'level-up-client-dashboard' ), 'type' => 'date' ),
            'company_logo'     => array( 'label' => __( 'Company Logo', 'level-up-client-dashboard' ), 'type' => 'hidden' ),
            'attention_needed' => array(
                'label'      => __( 'Attention Needed', 'level-up-client-dashboard' ),
                'type'       => 'textarea',
                'admin_only' => true,
            ),
            'critical_issue'   => array(
                'label'      => __( 'Critical Issue', 'level-up-client-dashboard' ),
                'type'       => 'textarea',
                'admin_only' => true,
            ),
        );
    }

    /**
     * Get definitions for all project fields.
     *
     * @return array
     */
    public static function get_project_fields() {
        return array(
            'project_name'         => array( 'label' => __( 'Project Name', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'project_client'       => array( 'label' => __( 'Client', 'level-up-client-dashboard' ), 'type' => 'text', 'class' => 'lucd-project-client' ),
            'client_id'            => array( 'type' => 'hidden' ),
            'project_type'         => array(
                'label'    => __( 'Project Type', 'level-up-client-dashboard' ),
                'type'     => 'text',
                'datalist' => array(
                    __( 'Website Design & Development', 'level-up-client-dashboard' ),
                    __( 'Website Hosting', 'level-up-client-dashboard' ),
                    __( 'Domain Management', 'level-up-client-dashboard' ),
                    __( 'Logo', 'level-up-client-dashboard' ),
                    __( 'Misc. Graphic Design', 'level-up-client-dashboard' ),
                    __( 'PPC', 'level-up-client-dashboard' ),
                    __( 'SEO', 'level-up-client-dashboard' ),
                    __( 'Social Media Management', 'level-up-client-dashboard' ),
                    __( 'Social Media Advertising', 'level-up-client-dashboard' ),
                    __( 'Custom Development', 'level-up-client-dashboard' ),
                ),
            ),
            'start_date'           => array( 'label' => __( 'Start Date', 'level-up-client-dashboard' ), 'type' => 'date' ),
            'end_date'             => array( 'label' => __( 'End Date', 'level-up-client-dashboard' ), 'type' => 'date' ),
            'status'               => array(
                'label'    => __( 'Status', 'level-up-client-dashboard' ),
                'type'     => 'text',
                'datalist' => array(
                    __( 'Not Started', 'level-up-client-dashboard' ),
                    __( 'In Progress', 'level-up-client-dashboard' ),
                    __( 'Completed', 'level-up-client-dashboard' ),
                    __( 'Ongoing', 'level-up-client-dashboard' ),
                    __( 'Client Feedback Needed', 'level-up-client-dashboard' ),
                    __( 'Client Assets Needed', 'level-up-client-dashboard' ),
                    __( 'Cancelled', 'level-up-client-dashboard' ),
                ),
            ),
            'dev_link'             => array( 'label' => __( 'Dev Link', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'live_link'            => array( 'label' => __( 'Live Link', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'gdrive_link'          => array( 'label' => __( 'Google Drive Link', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'total_one_time_cost'  => array( 'label' => __( 'Total One-Time Cost', 'level-up-client-dashboard' ), 'type' => 'text', 'class' => 'lucd-currency' ),
            'mrr'                  => array( 'label' => __( 'MRR', 'level-up-client-dashboard' ), 'type' => 'text', 'class' => 'lucd-currency' ),
            'arr'                  => array( 'label' => __( 'ARR', 'level-up-client-dashboard' ), 'type' => 'text', 'class' => 'lucd-currency' ),
            'monthly_support_time' => array( 'label' => __( 'Monthly Support Time', 'level-up-client-dashboard' ), 'type' => 'number', 'step' => '1' ),
            'description'          => array( 'label' => __( 'Description', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
            'project_updates'      => array( 'label' => __( 'Project Updates', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
            'attention_needed'     => array( 'label' => __( 'Attention Needed', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
            'critical_issue'       => array( 'label' => __( 'Critical Issue', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
        );
    }

    /**
     * Get definitions for all support ticket fields.
     *
     * @return array
     */
    public static function get_ticket_fields() {
        return array(
            'ticket_id'          => array( 'label' => __( 'Ticket #', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'ticket_client'      => array( 'label' => __( 'Client', 'level-up-client-dashboard' ), 'type' => 'text', 'class' => 'lucd-project-client' ),
            'client_id'          => array( 'type' => 'hidden' ),
            'creation_datetime'  => array( 'label' => __( 'Created', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'start_time'         => array( 'label' => __( 'Start Time', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'end_time'           => array( 'label' => __( 'End Time', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'duration_minutes'   => array( 'label' => __( 'Duration (minutes)', 'level-up-client-dashboard' ), 'type' => 'number' ),
            'status'             => array( 'label' => __( 'Status', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'initial_description' => array( 'label' => __( 'Initial Description', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
            'ticket_updates'     => array( 'label' => __( 'Ticket Updates', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
            'attention_needed'   => array( 'label' => __( 'Attention Needed', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
            'critical_issue'     => array( 'label' => __( 'Critical Issue', 'level-up-client-dashboard' ), 'type' => 'textarea' ),
        );
    }

    /**
     * Validate U.S. ZIP code format.
     *
     * @param string $postcode Postal code to validate.
     * @return bool
     */
    public static function is_valid_zip( $postcode ) {
        return (bool) preg_match( '/^\d{5}(?:-\d{4})?$/', $postcode );
    }
}

