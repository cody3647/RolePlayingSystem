<?php

/**
 * Integration of tags and characters to Post controller.
 *
 * @package Role Playing System
 * @version 1.0
 * @author Cody Williams <williams.c@gmail.com>
 * @copyright Cody Williams
 * @license BSD http://opensource.org/licenses/BSD-3-Clause
 */

use ElkArte\Errors\ErrorContext;

/**
 * Class Drafts_Post_Module
 *
 * Events and functions for post based drafts
 */
class RolePlayingSystem_Post_Module extends ElkArte\sources\modules\Abstract_Module
{
	/**
	 * @var Event_Manager
	 */
	protected static $_eventsManager = null;
	
	protected $_inCharacter = false;

	/**
	 * {@inheritdoc }
	 */
	public static function hooks(\Event_Manager $eventsManager)
	{
		global $modSettings, $board_info;
		
		$return = array();
		if (!empty($modSettings['rps_enabled']) )
		{
			self::$_eventsManager = $eventsManager;

			$return = array(
				array('prepare_post', array('RolePlayingSystem_Post_Module', 'prepare'), array('board_info', 'context')),
				array('prepare_save_post', array('RolePlayingSystem_Post_Module', 'prepare'), array('board_info')),
				array('prepare_modifying', array('RolePlayingSystem_Post_Module', 'prepare_modifying'), array('context')),
				array('finalize_post_form', array('RolePlayingSystem_Post_Module', 'finalize_post_form'), array( 'template_layers' )),
				array('before_save_post', array('RolePlayingSystem_Post_Module', 'before_save_post'), array('post_errors','topic',)),
				array('after_save_post', array('RolePlayingSystem_Post_Module', 'after_save_post'), array('topic', 'posterOptions', 'msgOptions')),
			);
		}

		return $return;
	}
	
	public function prepare(&$board_info)
	{
		$this->_inCharacter = $board_info['in_character'];
		if ($this->_inCharacter)
			$this->_get_characters();
	}
	
	
	public function prepare_modifying(&$context)
	{
		
		if (isset($_REQUEST['character']))
			$context['character'] = $_REQUEST['character'];
		if (isset($_REQUEST['date']))
			$context['date'] = $_REQUEST['date'];
		if (isset($_REQUEST['tags']))
			$context['tags'] = $_REQUEST['tags'];
	}
	
	protected function _get_characters($memID = 0)
	{
		global $context;
		
		$db = database();

		$msgID = isset( $_REQUEST['msg'] ) ? (int) $_REQUEST['msg'] : 0;
		
		$request = $db->query('', '
			SELECT c.id_character, c.name, c.id_member, if(c.id_character = m.id_character, 1, 0) AS current_character
			FROM elkarte_rps_characters as c
			LEFT JOIN elkarte_messages AS m ON id_msg = {int:msg_id}
			WHERE c.id_member = ' . ( empty($msgID) ? '{int:member_id}' : 'm.id_member' ),
			array(
				'member_id' => $context['user']['id'],
				'msg_id' => $msgID,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$context['characters'][$row['id_character']] = array(
				'name' => $row['name'],
			);
			if ($row['current_character'])
				$context['character'] = $row['id_character'];
		}
		$db->free_result($request);
	}
	

	/**
	 * Add form items for the Role Playing System
	 *
	 * What it does:
	 * - Loads a subtemplate from RolePlayingSystem to add a character
	 * 		select box, a date input, and a tag input.
	 *
	 * @param Template_Layers $template_layers
	 */
	public function finalize_post_form( &$template_layers )
	{
		global $context, $txt, $modSettings, $scripturl;
		
		loadTemplate('RolePlayingSystem');
		
		$modSettings['jquery_include_ui'] = true;
		loadCSSFile(array('RolePlayingSystem/jquery-ui.css', 'RolePlayingSystem/jquery-ui.theme.css', 'RolePlayingSystem/jquery-ui.structure.css'));
		
		$template_layers->addAfter('rps_post','postarea');
		$token = createToken('post-rps-tags');

		addInlineJavascript('

			$( function() {
				function split( val ) {
				  return val.split( /,\s*/ );
				}
				function extractLast( term ) {
				  return split( term ).pop();
				}
	
				$( "#post_tags" )
				// dont navigate away from the field on tab when selecting an item
				.on( "keydown", function( event ) {
					if ( event.keyCode === $.ui.keyCode.TAB &&
					$( this ).autocomplete( "instance" ).menu.active ) {
						event.preventDefault();
					}
				})
				.autocomplete({
					minLength: 3,
					source: function( request, response ) {
						$.getJSON( "' . $scripturl . '?action=tags;api=json;", {
						term: extractLast( request.term )
						}, response );
					},
					focus: function() {
					// prevent value inserted on focus
					return false;
					},
					select: function( event, ui ) {
						var terms = split( this.value );
						// remove the current input
						terms.pop();
						// add the selected item
						terms.push( ui.item.value );
						// add placeholder to get the comma-and-space at the end
						terms.push( "" );
						this.value = terms.join( ", " );
						return false;
					},
					delay: 500,
					appendTo:".post_tags",
				});
			} );
			',true);
	
	}
	
	/**
	 * Checks if a character is set on an in character board
	 *
	 * @param ErrorContext $post_errors
	 * @param array        $topic_info
	 *
	 */
	public function before_save_post(&$post_errors, &$topic)
	{
		if ($this->_inCharacter && empty($_POST['character']))
			$post_errors->addError('no_character_ic', null, 'RolePlayingSystem');
		
		if ($this->_inCharacter && empty($_POST['date']) && empty($topic))
			$post_errors->addError('no_date_ic', null, 'RolePlayingSystem');

	}
	
	public static function integrate_before_create_post( &$msgOptions, &$topicOptions, &$posterOptions, &$message_columns, &$message_parameters)
	{
		$charID = isset($_REQUEST['character']) ? (int) $_REQUEST['character'] : 0;
		$message_columns['id_character'] = 'int';
		$message_parameters['id_character'] = &$charID;
		$posterOptions['id_character'] = &$charID;
	}
	
	public static function integrate_before_create_topic(&$msgOptions, &$topicOptions, &$posterOptions, &$topic_columns, &$topic_parameters)
	{
		if (isset($_REQUEST['date']))
		{
			$topic_columns['date_tag'] = 'date';
			$topic_parameters['date_tag'] = $_REQUEST['date'];
		}
	}
	
	public static function integrate_before_modify_post(&$messages_columns, &$update_parameters, &$msgOptions, &$topicOptions, &$posterOptions, &$messageInts)
	{
		$messageInts[] = 'id_character';
		if (isset($_REQUEST['character']))
			$messages_columns['id_character'] = (int) $_REQUEST['character'];
			
	}
	
	public function after_save_post(&$topic, &$posterOptions, &$msgOptions)
	{
		$timestamp = time();
		
		$input_tags = $this->_req->getPost('tags', 'trim|Util::htmlspecialchars[ENT_QUOTES]');
		
		require_once(SUBSDIR . '/Tags.subs.php');
		save_tags($input_tags, $topic, $posterOptions['id'], $timestamp);
		
		if (!empty($posterOptions['update_post_count']) && !empty($posterOptions['id_character']))
		{
			$db = database();
			$db->query('', '
				UPDATE {db_prefix}rps_characters
				SET posts = posts + 1,
					last_active = {int:timestamp}
				WHERE id_character = {int:id_character}',
				array(
					'id_character' => $posterOptions['id_character'],
					'timestamp' => $timestamp,
				)
			);
		}
	}

}
