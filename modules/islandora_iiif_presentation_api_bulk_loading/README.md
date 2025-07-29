# Islandora IIIF Presentation API, Bulk Loading

Query and loading optimization based on data-model specifics, in particular:
- `node.field_member_of` references the node under which to find other nodes
- `node.field_weight` is used to order nodes selected, under a given parent
- `media.field_media_of` relates media to the entities of `node.field_member_of`
- `media.field_media_use:taxonomy_term.entity:field_external_uri.uri` relates those media entities to the target media use URI; of specific interest here: `http://pcdm.org/use#ServiceFile`

You can suppress the usage of the bulk loading strategy by specifying the environment variable: `ISLANDORA_IIIF_PRESENTATION_API_BULK_LOADING=false`; which is to say: The variable `ISLANDORA_IIIF_PRESENTATION_API_BULK_LOADING` with the lower-case string value of `false`.

Additionally, when enabled, the number of entities loaded at a time can be controlled with `ISLANDORA_IIIF_PRESENTATION_API_BULK_LOADING_SIZE` the default is 64. To note: This quantity of both nodes and media entities will be loaded in close temporal proximity, so the number of entities actually loaded is about double.
