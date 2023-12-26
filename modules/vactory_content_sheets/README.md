# Vactory Content Sheets

The Vactory Content Sheets module allows for seamless
updating of website content and translations directly
from Google Sheets. This integration facilitates content
management, allowing clients to modify website content
in a familiar spreadsheet format, which is then automatically
reflected on the website.

## Features

- **Direct Integration**: Connects your Drupal site with Google Sheets
  for real-time content updates.
- **Translation Management**: Easily update and manage translations for
  your site's content.
- **User-friendly Interface**: Allows non-technical users to update website
  content without needing to access the backend.

## Installation

To install the Vactory Content Sheets module, use the following Drush command:
`drush en vactory_content_sheets -y`

## Configuration

### API Endpoint

`/api/vactory-content-sheets/update`

### Google Sheets Script

Configure the Google Sheets script to connect with your Drupal site's API endpoint
and update content as needed.

### Permissions

Ensure proper permissions are set for the API and users who will be updating the
Google Sheets.

## Usage

- **Update Content in Google Sheets**: Clients modify the content in predefined
  Google Sheets columns.
- **Automated Sync**: Changes are sent to the Drupal site through the configured
  API endpoint, updating content and translations.

## Demo Video

(!available)

## Maintainers

- **(Fahd BOUAICHA)** <f.bouaicha@void.fr>
- **(Brahim KHOUY)** <b.khouy@void.fr>
