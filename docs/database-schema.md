# Database Schema

The plugin uses custom database tables to store client data and related records. All tables use the WordPress table prefix.

## `lucd_clients`
Stores individual client records and associated company information.

| Column | Type | Description |
| --- | --- | --- |
| `client_id` | bigint(20) unsigned | Primary key. |
| `wp_user_id` | bigint(20) unsigned | Associated WordPress user ID. |
| `first_name` | varchar(100) | Client's first name. |
| `last_name` | varchar(100) | Client's last name. |
| `email` | varchar(100) | Unique email address for the client. |
| `mailing_address1` | varchar(255) | First line of the client's mailing address. |
| `mailing_address2` | varchar(255) | Second line of the client's mailing address. |
| `mailing_city` | varchar(100) | City for the client's mailing address. |
| `mailing_state` | varchar(100) | State or region for the client's mailing address. |
| `mailing_postcode` | varchar(20) | Postal or ZIP code for the client's mailing address. |
| `mailing_country` | varchar(100) | Country for the client's mailing address. |
| `company_name` | varchar(255) | Name of the client's company. |
| `company_website` | varchar(255) | Company's primary website URL. |
| `company_address1` | varchar(255) | First line of the company's physical address. |
| `company_address2` | varchar(255) | Second line of the company's physical address. |
| `company_city` | varchar(100) | City of the company's physical address. |
| `company_state` | varchar(100) | State or region of the company's physical address. |
| `company_postcode` | varchar(20) | Postal or ZIP code of the company's physical address. |
| `company_country` | varchar(100) | Country of the company's physical address. |
| `social_facebook` | varchar(255) | URL to the company's Facebook page. |
| `social_twitter` | varchar(255) | URL to the company's Twitter profile. |
| `social_instagram` | varchar(255) | URL to the company's Instagram profile. |
| `social_linkedin` | varchar(255) | URL to the company's LinkedIn page. |
| `social_yelp` | varchar(255) | URL to the company's Yelp listing. |
| `social_bbb` | varchar(255) | URL to the company's Better Business Bureau listing. |
| `client_since` | date | Date the client became a customer. |
| `created_at` | datetime | Record creation timestamp. |
| `updated_at` | datetime | Record last updated timestamp. |

Each client is associated with a WordPress user through `wp_user_id`. Future tables will reference `client_id` to relate records back to clients.
