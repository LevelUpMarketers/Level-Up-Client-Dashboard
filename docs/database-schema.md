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

## `lucd_projects`
Tracks projects associated with a client.

| Column | Type | Description |
| --- | --- | --- |
| `project_id` | bigint(20) unsigned | Primary key. |
| `client_id` | bigint(20) unsigned | References `lucd_clients.client_id`. |
| `project_name` | varchar(255) | Name of the project. |
| `start_date` | date | Date the project work began. |
| `end_date` | date | Date the project was completed. |
| `status` | varchar(100) | Current project status. |
| `dev_link` | varchar(255) | URL to the development environment. |
| `live_link` | varchar(255) | URL to the live site. |
| `gdrive_link` | varchar(255) | Google Drive folder URL. |
| `project_type` | varchar(100) | Type or category of project. |
| `total_cost` | decimal(10,2) | Total project cost. |
| `description` | text | Detailed project description. |
| `project_updates` | longtext | Historical log of project updates. |
| `created_at` | datetime | Record creation timestamp. |
| `updated_at` | datetime | Record last updated timestamp. |

## `lucd_tickets`
Stores support ticket records associated with a client.

| Column | Type | Description |
| --- | --- | --- |
| `ticket_id` | bigint(20) unsigned | Primary key. |
| `client_id` | bigint(20) unsigned | References `lucd_clients.client_id`. |
| `creation_datetime` | datetime | When the ticket was created. |
| `start_time` | datetime | Work start date and time. |
| `end_time` | datetime | Work end date and time. |
| `duration_minutes` | int unsigned | Total duration in minutes. |
| `status` | varchar(50) | Current ticket status. |
| `initial_description` | text | Initial ticket description. |
| `ticket_updates` | longtext | Ongoing ticket updates. |
| `created_at` | datetime | Record creation timestamp. |
| `updated_at` | datetime | Record last updated timestamp. |

## `lucd_billing`
Captures basic billing records.

| Column | Type | Description |
| --- | --- | --- |
| `billing_id` | bigint(20) unsigned | Primary key. |
| `client_id` | bigint(20) unsigned | References `lucd_clients.client_id`. |
| `invoice_number` | varchar(100) | Placeholder invoice identifier. |
| `created_at` | datetime | Record creation timestamp. |

## `lucd_plugins`
Lists plugins associated with a client.

| Column | Type | Description |
| --- | --- | --- |
| `plugin_id` | bigint(20) unsigned | Primary key. |
| `client_id` | bigint(20) unsigned | References `lucd_clients.client_id`. |
| `plugin_name` | varchar(255) | Placeholder plugin name. |
| `created_at` | datetime | Record creation timestamp. |

## Archive Tables

Every primary table listed above has a corresponding archive table that mirrors its structure:

- `lucd_clients_archive`
- `lucd_projects_archive`
- `lucd_tickets_archive`
- `lucd_billing_archive`
- `lucd_plugins_archive`

Archived records are moved from the primary tables into these archive tables when a client is archived. Future custom tables should also include matching archive tables so client data can be preserved when archived.
