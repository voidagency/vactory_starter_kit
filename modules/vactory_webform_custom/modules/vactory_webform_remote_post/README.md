# Vactory Webform Remote Post

This module provides enhanced webform remote post functionality with support
for custom fields configured in settings.php.

## Features

- **Remote Post Handler**: Post webform submissions to external URLs
- **Field Mapping**: Map webform fields to custom remote field names
- **Custom Settings Integration**: Include custom fields from settings.php
- **Token Support**: Use custom settings as tokens in URLs and custom data
- **Datalayer Integration**: Map response data to datalayer for analytics

## Custom Settings Configuration

### Adding Custom Fields in settings.php

Add custom fields to your `settings.php` or `settings.docker.php` file:

```php
$settings['vactory_remote_post'] = [
  'orgid' => 'XXXXXXXXX',
  'remote_url' => 'https://api.example.com',
  'api_key' => 'your-api-key-here',
];
```

### Using Custom Fields

#### 1. As Direct Fields
Custom fields are automatically included in all remote post requests with
their configured values.

#### 2. As Tokens
You can use custom fields as tokens in:
- Remote post URLs
- Custom data fields
- Response messages

**Token Format**: `[vactory:vactory_remote_post:FIELD_NAME]`

**Examples**:
- `[vactory:vactory_remote_post:orgid]` → `XXXXXXXXX`
- `[vactory:vactory_remote_post:remote_url]` → `https://api.example.com`
- `[vactory:vactory_remote_post:api_key]` → `your-api-key-here`

#### 3. In Custom Data (YAML)
```yaml
orgid: '[vactory:vactory_remote_post:orgid]'
api_endpoint: '[vactory:vactory_remote_post:remote_url]'
```

## Configuration

1. Go to your webform's Handlers tab
2. Add "Vactory Remote post" handler
3. Configure the remote URL and other settings
4. View the "Custom Settings Fields" section to see available custom fields
5. Use tokens in URLs and custom data as needed
