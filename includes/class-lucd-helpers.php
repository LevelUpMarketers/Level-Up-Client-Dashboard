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
            'mailing_postcode' => array( 'label' => __( 'Mailing Postcode', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'mailing_country'  => array( 'label' => __( 'Mailing Country', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_name'     => array( 'label' => __( 'Company Name', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_website'  => array( 'label' => __( 'Company Website', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'company_address1' => array( 'label' => __( 'Company Address 1', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_address2' => array( 'label' => __( 'Company Address 2', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_city'     => array( 'label' => __( 'Company City', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_state'    => array( 'label' => __( 'Company State', 'level-up-client-dashboard' ), 'type' => 'select', 'options' => self::get_us_states() ),
            'company_postcode' => array( 'label' => __( 'Company Postcode', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'company_country'  => array( 'label' => __( 'Company Country', 'level-up-client-dashboard' ), 'type' => 'text' ),
            'social_facebook'  => array( 'label' => __( 'Facebook', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_twitter'   => array( 'label' => __( 'Twitter', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_instagram' => array( 'label' => __( 'Instagram', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_linkedin'  => array( 'label' => __( 'LinkedIn', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_yelp'      => array( 'label' => __( 'Yelp', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'social_bbb'       => array( 'label' => __( 'BBB', 'level-up-client-dashboard' ), 'type' => 'url' ),
            'client_since'     => array( 'label' => __( 'Client Since', 'level-up-client-dashboard' ), 'type' => 'date' ),
            'company_logo'     => array( 'label' => __( 'Company Logo', 'level-up-client-dashboard' ), 'type' => 'hidden' ),
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

