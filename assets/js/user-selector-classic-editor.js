/*!
 * User selector for classic editor
 *
 * @handle kvm-user-selector-classic-editor
 * @deps kvm-user-selector, wp-i18n, wp-components, wp-element
 */

const { createRoot, render, useState } = wp.element;
const { UserSelectorComponent } = kvm;
const { __ } = wp.i18n;

// virtual-author-id[]

const UserSelectorClassic = ( props ) => {
	const [ currentUsers, setUsers ] = useState( [] );
	return (
		<div className="kvm-user-selector-classic">
			<UserSelectorComponent post={ props.post } onUserChange={ ( users ) => setUsers( users ) } />
			{ currentUsers.map( ( user ) => {
				console.log( currentUsers)
				return (
					<input type="hidden" name="virtual-author-id[]" value={ user.id } key={ user.id } />
				);
			} ) }
		</div>
	);
};

document.addEventListener('DOMContentLoaded', () => {
	const container = document.getElementById('kvm-user-selector-classic' );
	const id = container.dataset.postId;
	if ( createRoot ) {
		createRoot( container ).render( <UserSelectorClassic post={ id } /> );
	} else {
		render( <UserSelectorClassic post={ id } />, container );
	}
});
