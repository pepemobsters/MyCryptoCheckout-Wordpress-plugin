<?php

namespace mycryptocheckout;

use Exception;

trait autosettlement_trait
{
	/**
		@brief		Return the autosettlements collection.
		@since		2019-02-21 19:29:10
	**/
	public function autosettlements()
	{
		if ( isset( $this->__autosettlements ) )
			return $this->__autosettlements;

		$this->__autosettlements = autosettlements\Autosettlements::load();
		return $this->__autosettlements;
	}

	/**
		@brief		Administer the autosettlement settings.
		@since		2019-02-21 18:58:44
	**/
	public function autosettlement_admin()
	{
		$form = $this->form();
		$form->id( 'autosettlements' );
		$r = '';

		$table = $this->table();
		$table->css_class( 'autosettlements' );

		$table->bulk_actions()
			->form( $form )
			// Bulk action for autosettlement settings
			->add( __( 'Delete', 'mycryptocheckout' ), 'delete' )
			// Bulk action for autosettlement settings
			->add( __( 'Disable', 'mycryptocheckout' ), 'disable' )
			// Bulk action for autosettlement settings
			->add( __( 'Enable', 'mycryptocheckout' ), 'enable' )
			// Bulk action for autosettlement settings
			->add( __( 'Test', 'mycryptocheckout' ), 'test' )
			;

		// Assemble the autosettlements
		$row = $table->head()->row();
		$table->bulk_actions()->cb( $row );
		// Table column name
		$row->th( 'type' )->text( __( 'Type', 'mycryptocheckout' ) );
		// Table column name
		$row->th( 'details' )->text( __( 'Details', 'mycryptocheckout' ) );

		$autosettlements = $this->autosettlements();

		foreach( $autosettlements as $index => $autosettlement )
		{
			$row = $table->body()->row();
			$row->data( 'index', $index );
			$table->bulk_actions()->cb( $row, $index );

			// Address
			$url = add_query_arg( [
				'tab' => 'autosettlement_edit',
				'autosettlement_id' => $index,
			] );
			$url = sprintf( '<a href="%s" title="%s">%s</a>',
				$url,
				__( 'Edit this autosettlement', 'mycryptocheckout' ),
				$autosettlements->get_types_as_options()[ $autosettlement->get_type() ]
			);
			$row->td( 'type' )->text( $url );

			// Details
			$details = $autosettlement->get_details();
			$details = implode( "\n", $details );
			$row->td( 'details' )->text( wpautop( $details ) );
		}

		$fs = $form->fieldset( 'fs_add_new' );
		// Fieldset legend
		$fs->legend->label( __( 'Add new autosettlement', 'mycryptocheckout' ) );

		$autosettlement_type = $fs->select( 'autosettlement_type' )
			->css_class( 'autosettlement_type' )
			->description( __( 'Which type of autosettlement do you wish to add?', 'mycryptocheckout' ) )
			// Input label
			->label( __( 'Type', 'mycryptocheckout' ) )
			->opts( $autosettlements->get_types_as_options() );

		$save = $form->primary_button( 'save' )
			->value( __( 'Save settings', 'mycryptocheckout' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$reshow = false;

			if ( $table->bulk_actions()->pressed() )
			{
				$ids = $table->bulk_actions()->get_rows();
				switch ( $table->bulk_actions()->get_action() )
				{
					case 'delete':
						foreach( $ids as $id )
							$autosettlements->forget( $id );
						$autosettlements->save();
						$r .= $this->info_message_box()->_( __( 'The selected wallets have been deleted.', 'mycryptocheckout' ) );
					break;
					case 'disable':
						foreach( $ids as $id )
						{
							$autosettlement = $autosettlements->get( $id );
							$autosettlement->set_enabled( false );
						}
						$autosettlements->save();
						$r .= $this->info_message_box()->_( __( 'The selected wallets have been disabled.', 'mycryptocheckout' ) );
					break;
					case 'enable':
						foreach( $ids as $id )
						{
							$autosettlement = $autosettlements->get( $id );
							$autosettlement->set_enabled( true );
						}
						$autosettlements->save();
						$r .= $this->info_message_box()->_( __( 'The selected wallets have been enabled.', 'mycryptocheckout' ) );
					break;
					case 'test':
						foreach( $ids as $id )
						{
							$autosettlement = $autosettlements->get( $id );
							try
							{
								$message = sprintf( 'Success for %s: %s', $autosettlement->get_type(), $autosettlement->test() );
								$r .= $this->info_message_box()->_( $message );
							}
							catch( Exception $e )
							{
								$message = sprintf( 'Fail for %s: %s', $autosettlement->get_type(), $e->getMessage() );
								$r .= $this->error_message_box()->_( $message );
							}
						}
					break;
				}
				$reshow = true;
			}

			if ( $save->pressed() )
			{
				try
				{
					$autosettlement = $autosettlements->new_autosettlement();
					$autosettlement->set_type( $autosettlement_type->get_filtered_post_value() );

					$index = $autosettlements->add( $autosettlement );
					$autosettlements->save();

					$r .= $this->info_message_box()->_( __( 'Settings saved!', 'mycryptocheckout' ) );
					$reshow = true;
				}
				catch ( Exception $e )
				{
					$r .= $this->error_message_box()->_( $e->getMessage() );
				}
			}

			if ( $reshow )
			{
				echo $r;
				$_POST = [];
				$function = __FUNCTION__;
				echo $this->$function();
				return;
			}
		}

		$r .= wpautop( __( 'The table below shows the autosettlements that have been set up. To edit an autosettlement, click the type.', 'mycryptocheckout' ) );

		$r .= $this->h2( __( 'Autosettlements', 'mycryptocheckout' ) );

		$r .= $form->open_tag();
		$r .= $table;
		$r .= $form->close_tag();
		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}

	/**
		@brief		Edit this autosettlement setting.
		@since		2019-02-21 22:47:10
	**/
	public function autosettlement_edit( $id )
	{
		$autosettlements = $this->autosettlements();
		if ( ! $autosettlements->has( $id ) )
		{
			echo 'Invalid ID!';
			return;
		}
		$autosettlement = $autosettlements->get( $id );

		$form = $this->form();
		$form->id( 'autosettlement_edit' );
		$r = '';

		switch( $autosettlement->get_type() )
		{
			case 'bittrex':
				$bittrex_api_key = $form->text( 'bittrex_api_key' )
					->description( __( 'The limited API key of your Bittrex account.', 'mycryptocheckout' ) )
					// Input label
					->label( __( 'Bittrex API key', 'mycryptocheckout' ) )
					->size( 32 )
					->maxlength( 32 )
					->trim()
					->value( $autosettlement->get( 'bittrex_api_key' ) );
				$bittrex_api_secret = $form->text( 'bittrex_api_secret' )
					->description( __( 'The secret text associated to this API key.', 'mycryptocheckout' ) )
					// Input label
					->label( __( 'Bittrex secret', 'mycryptocheckout' ) )
					->size( 32 )
					->trim()
					->value( $autosettlement->get( 'bittrex_api_secret' ) );
			break;
		}

		// Which currencies to apply this autosettlement on.
		$fs = $form->fieldset( 'fs_currencies' );
		// Fieldset legend
		$fs->legend->label( __( 'Currencies', 'mycryptocheckout' ) );

		$currencies_input = $fs->select( 'currencies' )
			->description( __( 'The currencies to be autosettled. If no currencies are select, these settings will be applied to all of them. Hold the ctrl or shift key to select multiple currecies.', 'mycryptocheckout' ) )
			// Input label
			->label( __( 'Currencies to autosettle', 'mycryptocheckout' ) )
			->multiple()
			->size( 20 )
			->value( $autosettlement->get_currencies() );
		$this->currencies()->add_to_select_options( $currencies_input );

		if ( $this->is_network && is_super_admin() )
			$autosettlement->add_network_fields( $form );

		$save = $form->primary_button( 'save' )
			->value( __( 'Save settings', 'mycryptocheckout' ) );

		if ( $form->is_posting() )
		{
			$form->post();
			$form->use_post_values();

			$reshow = false;

			if ( $save->pressed() )
			{
				try
				{
					switch( $autosettlement->get_type() )
					{
						case 'bittrex':
							$value = $bittrex_api_key->get_filtered_post_value();
							$autosettlement->set( 'bittrex_api_key', $value );
							$value = $bittrex_api_secret->get_filtered_post_value();
							$autosettlement->set( 'bittrex_api_secret', $value );
						break;
					}

					$autosettlement->set_currencies( $currencies_input->get_post_value() );

					$autosettlement->maybe_parse_network_form_post( $form );

					$autosettlements->save();

					$r .= $this->info_message_box()->_( __( 'Settings saved!', 'mycryptocheckout' ) );
					$reshow = true;
				}
				catch ( Exception $e )
				{
					$r .= $this->error_message_box()->_( $e->getMessage() );
				}
			}

			if ( $reshow )
			{
				echo $r;
				$_POST = [];
				$function = __FUNCTION__;
				echo $this->$function( $id );
				return;
			}
		}

		$r .= $form->open_tag();
		$r .= $form->display_form_table();
		$r .= $form->close_tag();

		echo $r;
	}
}
