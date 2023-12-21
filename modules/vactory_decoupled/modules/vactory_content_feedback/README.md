# Vactory content feedback
Provides admin feedback configuration and adds an endpoint to update a feedback.

### Installation
`drush en vactory_content_feedback -y`

### Dependencies
- 'drupal:admin_feedback'
- vactory_decoupled

### Endpoints
* `/_feedback_update`:
  > Method: POST
  > 
  > Params: feedback_id (id of feedback to update),
  > feedback_message(feedback message)
  > 
  > _permission: 'give feedback'


For more examples about this endpoint call see:
`apps/starter/components/widgets/feedback/utils.js` on vactory_next project.
