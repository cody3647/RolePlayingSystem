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
 * Class RolePlayingSystem Post Module
 *
 * Events and functions for characters and tags in Posts
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
	
	/**
	 * Sets if the board is in_character or not
	 *
	 * Event triggered in controller/Post.controller.php
	 *
	 * @param array $board_info
	 */
	
	public function prepare(&$board_info)
	{
		$this->_inCharacter = $board_info['in_character'];
		if ($this->_inCharacter)
			$this->_get_characters();
	}
	
	/**
	 * Gets the characters from either the current member or from the member who originally posted.
	 *
	 * @param array $board_info
	 */
	
	protected function _get_characters()
	{
		global $context;
		
		$db = database();

		$msgID = $this->_req->getQuery('msg', 'intval',0);
		
		$request = $db->query('', '
			SELECT c.id_character, c.name, c.id_member, if(c.id_character = m.id_character, 1, 0) AS current_character, c.approved
			FROM {db_prefix}rps_characters as c
			LEFT JOIN {db_prefix}messages AS m ON id_msg = {int:msg_id}
			WHERE c.retired = 0 AND c.id_member = ' . ( empty($msgID) ? '{int:member_id}' : 'm.id_member' ),
			array(
				'member_id' => $context['user']['id'],
				'msg_id' => $msgID,
			)
		);
		while ($row = $db->fetch_assoc($request))
		{
			$context['characters'][$row['id_character']] = array(
				'name' => $row['name'],
				'approved' => $row['approved'],
			);
			if ($row['current_character'])
				$context['character'] = $row['id_character'];
		}
		$db->free_result($request);
	}
	
	/**
	 * Get the RPS additons to save
	 *
	 * Event triggered in controller/Post.controller.php
	 *
	 * @param array $context
	 */
	
	public function prepare_modifying(&$context)
	{
		
		if ($this->_req->__isset('character'))
			$context['character'] = $this->_req->getPost('character', 'intval', 0);
		if ($this->_req->__isset('date'))
			$context['date'] = $this->_req->getPost('date', 'strval', '');
		if ($this->_req->__isset('tags'))
			$context['tags'] = $this->_req->getPost('tags', 'strval', '');
	}	

	/**
	 * Add form items for the Role Playing System
	 * 
	 * Event triggered in controller/Post.controller.php
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
		//'RolePlayingSystem/jquery-ui.css', 
		loadCSSFile(array('RolePlayingSystem/jquery-ui.theme.css', 'RolePlayingSystem/jquery-ui.structure.css'));
		
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
	 * Event triggered in controller/Post.controller.php
     *
     * @param ErrorContext $post_errors
     * @param $topic
     */
	public function before_save_post(&$post_errors, &$topic)
	{
		if ($this->_inCharacter && empty($_POST['character']))
			$post_errors->addError('no_character_ic', null, 'RolePlayingSystem');
		
		if ($this->_inCharacter && empty($_POST['date']) && empty($topic))
			$post_errors->addError('no_date_ic', null, 'RolePlayingSystem');

	}
	
	/**
     * Adds columns and parameters to the message query
	 *
	 * Called in subs/Post.subs.php createPost()
     *
     * @param array $msgOptions
     * @param array $topicOptions
     * @param array $posterOptions
     * @param array $message_columns
     * @param array $message_parameters
     */
	
	public static function integrate_before_create_post( &$msgOptions, &$topicOptions, &$posterOptions, &$message_columns, &$message_parameters)
	{
		$req = HttpReq::instance();
		$charID = $req->getPost('character', 'intval', 0);
		$message_columns['id_character'] = 'int';
		$message_parameters['id_character'] = &$charID;
		$posterOptions['id_character'] = &$charID;
	}
	
	/**
     * Adds columns and parameters to the topic query
	 *
	 * Called in subs/Post.subs.php createPost()
     *
     * @param array $msgOptions
     * @param array $topicOptions
     * @param array $posterOptions
     * @param array $topic_columns
     * @param array $topic_parameters
     */
	
	public static function integrate_before_create_topic(&$msgOptions, &$topicOptions, &$posterOptions, &$topic_columns, &$topic_parameters)
	{
		$req = HttpReq::instance();
		
		if ( $req->__isset('date') )
		{
			var_dump($req->getPost('date', '', 0));
			$topic_columns['date_tag'] = 'date';
			$topic_parameters['date_tag'] = $req->getPost('date', '', 0);
		}
	}
	
	/**
     * Adds columns and parameters to the modify message query
	 *
	 * Called in subs/Post.subs.php modifyPost()
     *
	 * @param array $messasge_columns
     * @param array $update_parameters
     * @param array $msgOptions
     * @param array $topicOptions
     * @param array $posterOptions
	 * @param array $messageInts
     */
	
	public static function integrate_before_modify_post(&$messages_columns, &$update_parameters, &$msgOptions, &$topicOptions, &$posterOptions, &$messageInts)
	{
		$messageInts[] = 'id_character';
		if ($this->_req->__isset('character'))
		{
			$messages_columns['id_character'] = $this->_req->getPost('character', 'intval');
		}
			
	}
	
	/**
     * Increments Character's post count and saves tags
	 *
	 * Event triggered in controller/Post.controller.php
     *
     * @param int $topic
     * @param array $posterOptions
	 * @param array $msgOptions
     */
	
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
