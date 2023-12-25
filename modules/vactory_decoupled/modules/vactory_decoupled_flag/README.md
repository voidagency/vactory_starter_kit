# Vactory decoupled flag
Provides APIs for managing content flags, possibility of enabling flag
feature from backoffice per content type.

### Installation
`drush en vactory_decoupled_flag -y`

### Endpoints
* `/api/flagging`: For flagging content [Method: POST]
* `/api/unflagging`: For unflagging content [Method: POST]
* `/api/flagging/all/{bundle}`: Get all flagged nodes of given 
content type ({bundle}) [Method: GET]

### Computed fields
The module adds two computed field to node entity type:
* `is_flagged`: Returns TRUE when the node is flagged
* `has_flag`: Returns TRUE when flag is enabled for the content type of the node.

### Enable flag for specific content type
1. Go to the content type edit form
(`/admin/structure/types/manage/{content_type}`)
2. On flag configuration tab check enable flag checkbox
