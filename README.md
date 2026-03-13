# TagsPlus

## Description

Plugin for Omeka Classic. Replaces the default Tags Browse page with an enhanced interface offering improved usability and additional tag management tools.

## Features

### Browse & Edit
- Full-width layout, optimised for all screen sizes
- Inline tag search with autocomplete
- Filter by record type
- Sort by name, count or date created
- Collapsible editing instructions with visual reference

### Inline Rename
Click any tag name to edit it directly in the list. Press **Enter** to save or **ESC** to cancel.

### Merge
When renaming a tag, if the new name matches an existing tag the system warns the user and offers the option to merge the two tags. The source tag's records are reassigned to the target tag, and the source tag is deleted. *(requires admin/super/contributor role)*

### Find Similar Tags
Detects pairs of tags with similar names using the Levenshtein distance algorithm. Results are displayed in a paginated table, with buttons to merge each pair in either direction. *(requires admin/super/contributor role)*

### Tools
Available to admin and super users from the dedicated tab:

- **Delete Unused Tags** — deletes all tags not associated with any record
- **Convert Case** — converts all tag names to UPPERCASE, lowercase, or First Letters Uppercase; tags that become identical after conversion are automatically merged
- **Subject to Tag** — synchronizes Tags with DC.Subject metadata entries, creating new tags from subjects not yet present as tags

## Installation

1. Uncompress files and rename plugin folder `TagsPlus`
2. Move the folder into Omeka's `plugins` directory
3. Activate the plugin from the admin panel under **Plugins**

## Permissions

| Feature | Super | Admin | Contributor | Researcher |
|---------|-------|-------|-------------|------------|
| Browse & Edit tags | ✓ | ✓ | ✓ (edit only) | ✓ (read only) |
| Find Similar Tags / Merge | ✓ | ✓ | ✓ | — |
| Tools (Delete, Convert, Sync) | ✓ | ✓ | — | — |

## Warning

Use it at your own risk.

It is always recommended to backup your files and your databases and to check your archives regularly so you can roll back if needed.

## Troubleshooting

See online issues on the [plugin issues](https://github.com/DBinaghi/plugin-TagsPlus/issues) page on GitHub.

## License

This plugin is published under the [CeCILL v2.1](https://www.cecill.info/licences/Licence_CeCILL_V2.1-en.html) licence, compatible with [GNU/GPL](https://www.gnu.org/licenses/gpl-3.0.html) and approved by [FSF](https://www.fsf.org/) and [OSI](http://opensource.org/).

## Copyright

Copyright [Daniele Binaghi](https://github.com/DBinaghi), 2026

For their coding inspiration and contributions, many thanks to the following people:

- SubjectToTags: copyright [Vincent Buard](https://github.com/EMAN-Omeka) / [EMAN-Omeka](https://github.com/EMAN-Omeka), 2021
