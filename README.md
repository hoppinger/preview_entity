## Preview entity
This module is built to provide a preview functionality to a Headless drupal website. It provides an endpoint which can be used to fetch the data per revision of the node.

The user navigates to [FRONTEND URL]/preview/[ENTITY TYPE]/[ENTITY ID]/[REVISION ID]?token=[VALIDATION TOKEN].

Drupal presents this URL in the interface to the user. Drupal can construct the path part of this URL very easily. The validation token URL parameter is constructed by Drupal using the following process. For the process it uses the following parameters:

- Timestamp: a unix timestamp number, in seconds since the epoch. The timestamp indicates the time until the link should be valid. So it is not the current time.
- Entity *: Parameters that indicate which entity should be previewed.
- Shared secret: A secret key that is shared between the Drupal and .Net applications. It should be kept a secret, because with it an attacker can generate preview URLs for unpublished content.

The frontend application receives the request to the specified URL, extracts the entity and revision parameters from the path and the token parameter and validates them

### Configuration
Update the settings.php file to include these parameters
```sh 
$settings["frontend_domain_url"] = "FRONTEND_DOMAIN_URL";
$settings["shared_secret"] = "SHARED_SECRET";
$settings["preview_valid_days"] = "5";
```
