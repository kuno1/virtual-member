/*!
 * User selector for block editor
 *
 * @handle kvm-user-selector-block-editor
 * @deps kvm-user-selector, wp-plugins, wp-edit-post, wp-i18n, wp-components
 */

alert( 'Block Editor' );

const { registerPlugin } = wp.plugins;
const { PluginDocumentSettingPanel } = wp.editPost;
const { UserSelectorComponent } = kvm;
const { PanelBody } = wp.components;
const { __ } = wp.i18n;

registerPlugin('kvm-sidebar', {
	render: () => {
		return (
			<PluginDocumentSettingPanel
				name="kvm-user-selector"
				title="ユーザー選択"
				className="user-selector-panel"
			>
				<PanelBody title="ユーザー選択" initialOpen={true}>
					<UserSelectorComponent users={ [ 1, 2 ] } onUserSelected={ ( newUsers ) => {
						console.log( newUsers );
					} } />
				</PanelBody>
			</PluginDocumentSettingPanel>
		);
	}
} );
