# Virtual Member

Virtual Member is a WordPress plugin that creates a custom post type called "member" to represent authors who are not WordPress users. This allows you to assign virtual members as authors to your posts, which is particularly useful when the actual content creator is different from the person who publishes it in WordPress.

## Description

Virtual Member provides a solution for websites that need to display content from authors who don't have WordPress user accounts. Instead of creating WordPress user accounts for every author, you can create "member" posts that contain author information such as name, profile picture, biography, and contact methods.

### Key Features

- Create virtual members with profile pictures, descriptions, and contact information
- Assign virtual members as authors to any post type
- Support for single or multiple authors per post
- Group members using taxonomies
- Customize the member post type label and URL structure
- Set a default member for posts without specified authors
- Designate members as organizations or site representatives
- Automatic Open Graph Protocol metadata for virtual members
- Compatible with both classic editor and block editor

### Use Cases

- Publishing content from guest authors without creating WordPress user accounts
- Managing content where the actual author is different from the WordPress user who publishes it
- Creating bylines for organizations rather than individuals
- Maintaining a staff directory with author archives

## Installation

1. Upload the `virtual-member` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to "Member Setting" under the "Member" menu to configure the plugin

## Customization

### Basic Settings

1. Navigate to "Member" > "Member Setting" in your WordPress admin
2. Under "Basic Setting", select which post types should have virtual members as authors
3. Optionally set a default member for posts without specified authors
4. Choose whether the member post type should be public (have its own permalink)
5. Configure the URL prefix for member archives (default is "member")
6. Save your changes

### Creating Virtual Members

1. Go to "Member" > "Add New" in your WordPress admin
2. Enter the member's name in the title field
3. Add a description in the content editor
4. Set a profile picture using the featured image
5. Add contact information in the "Contact Methods" meta box
6. Optionally mark the member as an organization or site representative
7. Assign the member to groups using the "Group" taxonomy
8. Publish the member

### Assigning Members to Posts

1. Edit a post of a type that you've enabled for virtual members
2. Look for the "Member" meta box in the sidebar
3. Select a member from the dropdown (single author mode) or use the search field to add multiple members (multiple author mode)
4. Update the post

### Advanced Settings

#### Multiple Authors

1. Go to "Member" > "Member Setting"
2. Under "Basic Setting", select "Multiple Authors" in the "Allow Multiple Assign" option
3. Save your changes
4. When editing posts, you can now add multiple members as authors

#### Custom Contact Methods

1. Go to "Member" > "Member Setting"
2. Find the "Contact Methods" field
3. Enter custom contact methods in CSV format (e.g., `facebook,Facebook`)
4. Save your changes
5. These contact methods will appear in the member editor

#### Member Groups

1. Go to "Member" > "Groups"
2. Create groups to categorize your members (e.g., "Staff", "Contributors", "Editors")
3. When creating or editing members, assign them to these groups

### Frontend Display

When a post has a virtual member assigned as its author:

- The author name will display the member's name instead of the WordPress user
- The author link will point to the member's page or archive
- The author avatar will use the member's profile picture
- Author description and contact information will come from the member post

## FAQ

### Can I use this plugin alongside co-authors?

Yes, Virtual Member can be used alongside other author management plugins, but you may need to ensure compatibility through custom code.

### How do I display custom contact methods on the frontend?

The plugin automatically hooks into WordPress's author template functions. You can use standard WordPress functions like `get_the_author_meta('facebook')` to display custom contact methods.

### Can I change the URL structure for member archives?

Yes, go to "Member" > "Member Setting" and change the "URL Prefix" setting. After changing this setting, remember to flush your permalinks by going to Settings > Permalinks and clicking "Save Changes".

### How do I set a member as the default author for all posts?

1. Create a member that will serve as the default
2. Go to "Member" > "Member Setting"
3. Select this member in the "Default Author" dropdown
4. Save your changes

### Can I use this plugin with custom post types?

Yes, you can enable virtual members for any public post type in the plugin settings.

### How do I display multiple authors on the frontend?

If you've enabled multiple authors, you'll need to use custom code to display all authors. You can use the `get_members()` function from the `CommonMethods` trait to retrieve all members assigned to a post.

```php
// Example code to display all authors of a post
$public_screen = \Kunoichi\VirtualMember\Ui\PublicScreen::get_instance();
$members = $public_screen->get_members(get_the_ID());
foreach ($members as $member) {
    echo get_the_title($member);
    // Display other member information
}
```

### Is this plugin compatible with the block editor?

Yes, Virtual Member works with both the classic editor and the block editor (Gutenberg).

### Can I designate a member as an organization?

Yes, when editing a member, check the "This is organization" option in the Organization meta box. This will also affect the schema.org markup generated for the member.
