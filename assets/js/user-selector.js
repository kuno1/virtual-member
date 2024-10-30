/*!
 * User Selector
 *
 * @handle kvm-user-selector
 *
 * @deps wp-element, wp-api-fetch, wp-components, wp-i18n
 */

/* global KvmUserSelector:false */

const { useState, useEffect } = wp.element;
const { Button, Icon, ComboboxControl } = wp.components;
const { apiFetch } = wp;

const UserToken = ( { user, onUserDelete } ) => {
	return (
		<Button className="kvm-user-token" onClick={ () => onUserDelete( user ) }
			iconPosition="right" variant="secondary">
			{ user.name }
			<small>({ user.group.length > 0 ? user.group.map( ( g ) => g.name ).join( ', ' ) : '---' })</small>
			<Icon icon="no-alt" />
		</Button>
	);
};

const UserComboBox = ( { onUserSelected } ) => {
	const [ query, setQuery ] = useState( '' );
	const [ users, setUsers ] = useState( [] );
	const [ selectedOption, setSelectedOption ] = useState( null );
	const [ timer, setTimer ] = useState( null );

	// Do incremental search
	useEffect( () => {
		if ( timer ) {
			clearTimeout( timer );
		}
		setTimer( setTimeout( () => {
			if ( query ) {
				// Search authros via API
				const fetchOptions = async () => {
					const response = await apiFetch( { path: `/kvm/v1/authors/search?s=${ query }` } );
					setUsers( response );
				};
				try {
					fetchOptions();
				} catch ( error ) {
					setUsers( [] );
				}
			} else {
				setUsers( [] );
			}
		}, 300 ) );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ query ] );

	return (
		<ComboboxControl
			label={ KvmUserSelector.slabel }
			placeholder={ KvmUserSelector.search }
			value={ selectedOption }
			options={ users.map( ( user ) => {
				return { label: user.name, value: user.id };
			} ) }
			onChange={ ( value ) => {
				// User is selected.
				users.forEach( ( user ) => {
					if ( user.id === value ) {
						onUserSelected( user );
					}
				} );
				setSelectedOption( '' );
			} }
			onFilterValueChange={ ( newQuery ) => setQuery( newQuery ) }
		/>
	);
};

/**
 * User Select components.
 */

const UserSelectorComponent = ( { post, onUserChange } ) => {
	const [ users, setUsers ] = useState( [] );

	useEffect( () => {
		const fetchUsers = async () => {
			try {
				const response = await apiFetch( {
					path: `/kvm/v1/authors/of/${ post }`,
				} );
				setUsers( response );
			} catch ( error ) {
				// eslint-disable-next-line no-undef
				alert( error.message || 'Error' );
			}
		};
		fetchUsers();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	useEffect( () => {
		onUserChange( users );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ users ] );

	const removeUser = ( user ) => {
		const newUsers = [];
		users.map( ( u ) => {
			if ( u.id !== user.id ) {
				newUsers.push( u );
			}
			return u;
		} );
		setUsers( newUsers );
	};

	return (
		<>
			{ users.length > 0 ? (
				<div className="kvm-user-token-wrapper">
					{
						users.map( ( user ) => {
							return <UserToken key={ user.id } user={ user }
								onUserDelete={ ( u ) => removeUser( u ) } />;
						} )
					}
				</div>
			) : (
				<div className="notice notice-error notice-alt">
					<p>{ KvmUserSelector.nouser }</p>
				</div>
			) }
			<UserComboBox onUserSelected={ ( user ) => {
				const userIds = users.map( ( u ) => u.id );
				if ( userIds.includes( user.id ) ) {
					return;
				}
				setUsers( users.concat( [ user ] ) );
			} } />
		</>
	);
};

// Export components.
const kvm = window.kvm || {};
kvm.UserSelectorComponent = UserSelectorComponent;
window.kvm = kvm;
